<?php
/**
 * Magendoo Faq Email Sender
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model\Email;

use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magento\Framework\App\Area;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\DataObject;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\Translate\Inline\StateInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Service responsible for dispatching FAQ-related email notifications.
 */
class Sender
{
    private const XML_PATH_USER_ENABLED = 'magendoo_faq/user_notifications/enabled';
    private const XML_PATH_USER_SENDER = 'magendoo_faq/user_notifications/email_sender';
    private const XML_PATH_USER_TEMPLATE = 'magendoo_faq/user_notifications/email_template';

    private const XML_PATH_ADMIN_ENABLED = 'magendoo_faq/admin_notifications/enabled';
    private const XML_PATH_ADMIN_SEND_TO = 'magendoo_faq/admin_notifications/send_to';
    private const XML_PATH_ADMIN_TEMPLATE = 'magendoo_faq/admin_notifications/email_template';

    /**
     * @param TransportBuilder $transportBuilder
     * @param StateInterface $inlineTranslation
     * @param ScopeConfigInterface $scopeConfig
     * @param StoreManagerInterface $storeManager
     * @param FaqHelper $faqHelper
     * @param LoggerInterface $logger
     */
    public function __construct(
        private TransportBuilder $transportBuilder,
        private StateInterface $inlineTranslation,
        private ScopeConfigInterface $scopeConfig,
        private StoreManagerInterface $storeManager,
        private FaqHelper $faqHelper,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Send admin notification when a new question is submitted.
     *
     * @param QuestionInterface $question
     * @return bool
     */
    public function sendAdminNotification(QuestionInterface $question): bool
    {
        $storeId = $this->resolveStoreId($question);

        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_ADMIN_ENABLED, ScopeInterface::SCOPE_STORE, $storeId)) {
            return false;
        }

        $sendTo = (string) $this->scopeConfig->getValue(
            self::XML_PATH_ADMIN_SEND_TO,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $template = (string) $this->scopeConfig->getValue(
            self::XML_PATH_ADMIN_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        // The recipient list is merchant configuration and may legitimately
        // hold several comma-separated addresses; validate each one.
        $recipients = [];
        foreach (explode(',', $sendTo) as $candidate) {
            $candidate = trim($candidate);
            if ($candidate !== '' && filter_var($candidate, FILTER_VALIDATE_EMAIL)) {
                $recipients[] = $candidate;
            }
        }

        if (!$recipients || $template === '') {
            return false;
        }

        // The admin template is declared area="adminhtml" in email_templates.xml
        // and must be rendered in that area.
        return $this->dispatch($template, 'general', $recipients, $question, $storeId, Area::AREA_ADMINHTML);
    }

    /**
     * Send customer notification when the admin answers a question.
     *
     * @param QuestionInterface $question
     * @return bool
     */
    public function sendAnswerNotification(QuestionInterface $question): bool
    {
        $storeId = $this->resolveStoreId($question);

        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_USER_ENABLED, ScopeInterface::SCOPE_STORE, $storeId)) {
            return false;
        }

        // sender_email is shopper-supplied data: it is exactly one address and
        // must never be split into a recipient list.
        $recipient = trim((string) $question->getSenderEmail());
        if ($recipient === '' || !filter_var($recipient, FILTER_VALIDATE_EMAIL)) {
            $this->logger->warning(
                'Magendoo FAQ: answer notification skipped, invalid recipient address for question '
                . (int) $question->getQuestionId()
            );
            return false;
        }

        $sender = (string) $this->scopeConfig->getValue(
            self::XML_PATH_USER_SENDER,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
        $template = (string) $this->scopeConfig->getValue(
            self::XML_PATH_USER_TEMPLATE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        if ($sender === '' || $template === '') {
            return false;
        }

        return $this->dispatch(
            $template,
            $sender,
            [$recipient],
            $question,
            $storeId,
            Area::AREA_FRONTEND,
            (string) $question->getSenderName()
        );
    }

    /**
     * Build and send the transactional email.
     *
     * Mirrors the transport shape of \Magento\Contact\Model\Mail::send() with
     * the store id resolved from the question instead of the current scope.
     *
     * @param string $template
     * @param string $sender
     * @param string[] $recipients
     * @param QuestionInterface $question
     * @param int $storeId
     * @param string $area
     * @param string|null $toName
     * @return bool
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    private function dispatch(
        string $template,
        string $sender,
        array $recipients,
        QuestionInterface $question,
        int $storeId,
        string $area,
        ?string $toName = null
    ): bool {
        try {
            $this->inlineTranslation->suspend();
            $store = $this->storeManager->getStore($storeId);

            $transport = $this->transportBuilder
                ->setTemplateIdentifier($template)
                ->setTemplateOptions([
                    'area' => $area,
                    'store' => $storeId,
                ])
                ->setTemplateVars([
                    'question' => $question,
                    'question_title' => $question->getTitle(),
                    'question_full_answer' => $question->getFullAnswer(),
                    'question_short_answer' => $question->getShortAnswer(),
                    'sender_name' => $question->getSenderName(),
                    'sender_email' => $question->getSenderEmail(),
                    'store' => $store,
                ])
                ->setFromByScope($sender, $storeId);

            foreach ($recipients as $recipient) {
                $transport->addTo($recipient, $toName ?? '');
            }

            $transport->getTransport()->sendMessage();

            return true;
        } catch (\Exception $e) {
            $this->logger->error('Magendoo FAQ email error: ' . $e->getMessage());
            return false;
        } finally {
            $this->inlineTranslation->resume();
        }
    }

    /**
     * Resolve the store the question belongs to.
     *
     * The senders can be triggered from the admin Save controller or the REST
     * notify route, where the "current" store is the admin store (0); template
     * rendering, sender identity and config reads must instead use the store
     * the question is assigned to.
     *
     * @param QuestionInterface $question
     * @return int
     */
    private function resolveStoreId(QuestionInterface $question): int
    {
        $storeIds = $question instanceof DataObject
            ? (array) $question->getData('store_ids')
            : [];

        foreach ($storeIds as $storeId) {
            $storeId = (int) $storeId;
            if ($storeId !== Store::DEFAULT_STORE_ID) {
                return $storeId;
            }
        }

        // Assigned to "All Store Views" (or loaded without the store relation):
        // fall back to the default store view, then to the current store.
        $defaultStore = $this->storeManager->getDefaultStoreView();
        if ($defaultStore !== null) {
            return (int) $defaultStore->getId();
        }

        return (int) $this->storeManager->getStore()->getId();
    }
}
