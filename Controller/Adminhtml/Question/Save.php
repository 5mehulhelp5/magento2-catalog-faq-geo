<?php
/**
 * Magendoo Faq Question Save Controller
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Controller\Adminhtml\Question;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\FilterManager;
use Magento\Store\Model\ScopeInterface;
use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Api\QuestionManagementInterface;
use Magendoo\Faq\Api\QuestionRepositoryInterface;
use Magendoo\Faq\Model\QuestionFactory;

/**
 * Save question controller
 */
class Save extends Action implements HttpPostActionInterface
{
    /**
     * Authorization resource
     */
    public const ADMIN_RESOURCE = 'Magendoo_Faq::question_edit';

    /**
     * Config path gating customer answer notifications (see Model\Email\Sender)
     */
    private const XML_PATH_USER_NOTIFICATIONS_ENABLED = 'magendoo_faq/user_notifications/enabled';

    /**
     * @var QuestionRepositoryInterface
     */
    protected QuestionRepositoryInterface $questionRepository;

    /**
     * @var QuestionManagementInterface
     */
    protected QuestionManagementInterface $questionManagement;

    /**
     * @var QuestionFactory
     */
    protected QuestionFactory $questionFactory;

    /**
     * @var FilterManager
     */
    protected FilterManager $filterManager;

    /**
     * @var DataPersistorInterface
     */
    protected DataPersistorInterface $dataPersistor;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @param Context $context
     * @param QuestionRepositoryInterface $questionRepository
     * @param QuestionManagementInterface $questionManagement
     * @param QuestionFactory $questionFactory
     * @param FilterManager $filterManager
     * @param DataPersistorInterface $dataPersistor
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        Context $context,
        QuestionRepositoryInterface $questionRepository,
        QuestionManagementInterface $questionManagement,
        QuestionFactory $questionFactory,
        FilterManager $filterManager,
        DataPersistorInterface $dataPersistor,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->questionRepository = $questionRepository;
        $this->questionManagement = $questionManagement;
        $this->questionFactory = $questionFactory;
        $this->filterManager = $filterManager;
        $this->dataPersistor = $dataPersistor;
        $this->scopeConfig = $scopeConfig;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $questionId = isset($data['question_id']) ? (int) $data['question_id'] : null;

        try {
            if ($questionId) {
                /** @var \Magendoo\Faq\Model\Question $question */
                $question = $this->questionRepository->getById($questionId);
            } else {
                $question = $this->questionFactory->create();
            }

            // Populate question data
            $question->setTitle($data['title'] ?? '');
            $question->setShortAnswer($data['short_answer'] ?? null);
            $question->setFullAnswer($data['full_answer'] ?? null);
            $question->setStatus($data['status'] ?? QuestionInterface::STATUS_PENDING);
            $question->setVisibility($data['visibility'] ?? QuestionInterface::VISIBILITY_NONE);
            $question->setPosition((int) ($data['position'] ?? 0));
            $question->setIsShowFullAnswer(!empty($data['is_show_full_answer']));
            $question->setSenderName($data['sender_name'] ?? null);
            $question->setSenderEmail($data['sender_email'] ?? null);
            $question->setMetaTitle($data['meta_title'] ?? null);
            $question->setMetaDescription($data['meta_description'] ?? null);
            $question->setNoindex(!empty($data['noindex']));
            $question->setNofollow(!empty($data['nofollow']));
            $question->setCanonicalUrl($data['canonical_url'] ?? null);
            $question->setExcludeSitemap(!empty($data['exclude_sitemap']));
            $question->setHideDirectUrl(!empty($data['hide_direct_url']));

            // Auto-generate url_key from title if empty
            $urlKey = $data['url_key'] ?? '';
            if (empty($urlKey)) {
                $urlKey = $this->filterManager->translitUrl($data['title'] ?? '');
            }
            $question->setUrlKey($urlKey);

            // Handle store_ids from form data
            if (isset($data['store_ids'])) {
                $question->setData('store_ids', $data['store_ids']);
            }

            // Handle category_ids from form data (multiselect => array of strings).
            if (isset($data['category_ids'])) {
                $categoryIds = is_array($data['category_ids'])
                    ? $data['category_ids']
                    : array_filter(array_map('trim', explode(',', (string) $data['category_ids'])));
                $question->setData('category_ids', array_map('intval', $categoryIds));
            }

            // Handle product_ids from form data. The admin form uses a comma-
            // separated text input ("1,24,57"); the REST API may pass an array.
            if (isset($data['product_ids'])) {
                $productIds = is_array($data['product_ids'])
                    ? $data['product_ids']
                    : array_filter(array_map('trim', explode(',', (string) $data['product_ids'])));
                $question->setData('product_ids', array_map('intval', $productIds));
            }

            // Handle tag assignments (multiselect => array of strings). An
            // absent key means the merchant cleared the selection, matching
            // the checkbox convention above. The value is set under both keys
            // the save pipeline reads: the resource model persists 'tag_ids'
            // and the repository relation pass-through carries 'tags' (see
            // \Magendoo\Faq\Model\QuestionRepository::save()).
            $tagIds = array_map(
                'intval',
                array_filter(
                    (array) ($data['tag_ids'] ?? []),
                    static fn ($tagId) => $tagId !== '' && $tagId !== null
                )
            );
            $question->setData('tag_ids', $tagIds);
            $question->setData('tags', $tagIds);

            // Handle customer group restrictions (multiselect => array of
            // strings). An empty selection means "visible to all groups"; the
            // filter must not drop group 0 (NOT LOGGED IN), which is a valid
            // selectable group.
            $customerGroupIds = array_map(
                'intval',
                array_filter(
                    (array) ($data['customer_group_ids'] ?? []),
                    static fn ($groupId) => $groupId !== '' && $groupId !== null
                )
            );
            $question->setData('customer_group_ids', $customerGroupIds);

            if (isset($data['customer_id'])) {
                $question->setCustomerId($data['customer_id'] ? (int) $data['customer_id'] : null);
            }

            $question = $this->questionRepository->save($question);

            // Send answer notification email if requested
            if (!empty($data['send_email'])) {
                $this->sendAnswerNotification($question);
            }

            $this->messageManager->addSuccessMessage(__('The question has been saved.'));
            $this->dataPersistor->clear('faq_question');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['question_id' => $question->getQuestionId()]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the question.'));
        }

        // Keep the submitted values so the form DataProvider can restore
        // them after the redirect (see
        // \Magento\Cms\Controller\Adminhtml\Page\Save::execute()).
        $this->dataPersistor->set('faq_question', $data);

        // Redirect back with data
        $redirectParams = ['_current' => true, '_use_forward' => false];
        if ($questionId) {
            $redirectParams['question_id'] = $questionId;
        }

        return $resultRedirect->setPath('*/*/edit', $redirectParams);
    }

    /**
     * Send the answer notification email and report an honest outcome.
     *
     * Model\Email\Sender returns false without throwing when notifications
     * are disabled, when the sender/template config or the recipient address
     * is missing, or when the transport fails — so the boolean result must
     * be checked before claiming the email was sent.
     *
     * @param QuestionInterface $question
     * @return void
     */
    private function sendAnswerNotification(QuestionInterface $question): void
    {
        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_USER_NOTIFICATIONS_ENABLED, ScopeInterface::SCOPE_STORE)) {
            $this->messageManager->addWarningMessage(
                __(
                    'The notification email was not sent: user notifications are disabled in'
                    . ' configuration (Stores > Configuration > Magendoo Extensions > FAQ > User Notifications).'
                )
            );
            return;
        }

        if (!$question->getSenderEmail()) {
            $this->messageManager->addWarningMessage(
                __('The notification email was not sent: the question has no sender email address.')
            );
            return;
        }

        try {
            if ($this->questionManagement->sendAnswerNotification((int) $question->getQuestionId())) {
                $this->messageManager->addSuccessMessage(__('Answer notification email has been sent.'));
            } else {
                $this->messageManager->addErrorMessage(
                    __(
                        'The question was saved but the notification email could not be sent.'
                        . ' Check the FAQ email sender and template configuration and the error log.'
                    )
                );
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('The question was saved but the notification email failed: %1', $e->getMessage())
            );
        }
    }
}
