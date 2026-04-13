<?php
/**
 * Magendoo Faq Structured Data Block
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Block\Faq;

use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Api\QuestionRepositoryInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Renders FAQPage JSON-LD structured data for the question view and category
 * view pages. Auto-loads the relevant question(s) based on the current route
 * so it can live in head.additional without depending on the main content
 * block's data.
 */
class StructuredData extends Template
{
    /**
     * @var FaqHelper
     */
    private FaqHelper $helper;

    /**
     * @var QuestionRepositoryInterface
     */
    private QuestionRepositoryInterface $questionRepository;

    /**
     * @var QuestionCollectionFactory
     */
    private QuestionCollectionFactory $questionCollectionFactory;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * Cache so toHtml() doesn't hit the DB twice on one render.
     *
     * @var QuestionInterface[]|null
     */
    private ?array $questionsCache = null;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @param Context $context
     * @param FaqHelper $helper
     * @param QuestionRepositoryInterface $questionRepository
     * @param QuestionCollectionFactory $questionCollectionFactory
     * @param StoreManagerInterface $storeManager
     * @param LoggerInterface $logger
     * @param CustomerSession $customerSession
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        FaqHelper $helper,
        QuestionRepositoryInterface $questionRepository,
        QuestionCollectionFactory $questionCollectionFactory,
        StoreManagerInterface $storeManager,
        LoggerInterface $logger,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->questionRepository = $questionRepository;
        $this->questionCollectionFactory = $questionCollectionFactory;
        $this->storeManager = $storeManager;
        $this->logger = $logger;
        $this->customerSession = $customerSession;
        parent::__construct($context, $data);
    }

    /**
     * Build the JSON-LD string for the current page. Returns an empty string
     * if no questions can be resolved — the template renders nothing in that
     * case so we avoid polluting the <head> on unrelated pages.
     *
     * @return string
     */
    public function getStructuredDataJson(): string
    {
        $questions = $this->resolveQuestions();
        if (empty($questions)) {
            return '';
        }

        $mainEntity = [];
        foreach ($questions as $question) {
            $mainEntity[] = [
                '@type' => 'Question',
                'name' => (string) $question->getTitle(),
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text' => $this->buildAnswerText($question),
                ],
            ];
        }

        $data = [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => $mainEntity,
        ];

        $json = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return $json !== false ? $json : '';
    }

    /**
     * Allow an upstream block to force-set the questions (legacy API kept
     * for future composition; the auto-loader above handles the common case).
     *
     * @param QuestionInterface[] $questions
     * @return $this
     */
    public function setQuestions(array $questions): static
    {
        $this->questionsCache = array_values($questions);
        return $this;
    }

    /**
     * Load questions based on the current full action name.
     *
     * @return QuestionInterface[]
     */
    private function resolveQuestions(): array
    {
        if ($this->questionsCache !== null) {
            return $this->questionsCache;
        }

        $fullActionName = $this->getRequest()->getFullActionName();
        $id = (int) $this->getRequest()->getParam('id');

        if ($id <= 0) {
            return $this->questionsCache = [];
        }

        if ($fullActionName === 'faq_question_view') {
            return $this->questionsCache = $this->loadSingleQuestion($id);
        }

        if ($fullActionName === 'faq_category_view') {
            return $this->questionsCache = $this->loadCategoryQuestions($id);
        }

        return $this->questionsCache = [];
    }

    /**
     * @param int $questionId
     * @return QuestionInterface[]
     */
    private function loadSingleQuestion(int $questionId): array
    {
        try {
            $question = $this->questionRepository->getById($questionId);
        } catch (NoSuchEntityException $e) {
            return [];
        } catch (\Exception $e) {
            $this->logger->warning('FAQ structured data: failed to load question ' . $questionId . ': ' . $e->getMessage());
            return [];
        }

        if ((string) $question->getVisibility() !== QuestionInterface::VISIBILITY_PUBLIC) {
            return [];
        }

        if ((string) $question->getStatus() !== QuestionInterface::STATUS_ANSWERED) {
            return [];
        }

        return [$question];
    }

    /**
     * @param int $categoryId
     * @return QuestionInterface[]
     */
    private function loadCategoryQuestions(int $categoryId): array
    {
        try {
            $storeId = (int) $this->storeManager->getStore()->getId();
        } catch (\Exception $e) {
            return [];
        }

        $collection = $this->questionCollectionFactory->create();
        $collection->addActiveFilter()
            ->addVisibilityFilter(QuestionInterface::VISIBILITY_PUBLIC)
            ->addCategoryFilter($categoryId)
            ->addStoreFilter($storeId)
            ->addCustomerGroupVisibilityFilter((int) $this->customerSession->getCustomerGroupId())
            ->setOrder('position', 'ASC');

        $items = [];
        foreach ($collection as $question) {
            $items[] = $question;
        }

        return $items;
    }

    /**
     * Prefer full_answer, fall back to short_answer. Always strip HTML.
     *
     * @param QuestionInterface $question
     * @return string
     */
    private function buildAnswerText(QuestionInterface $question): string
    {
        $full = (string) $question->getFullAnswer();
        if ($full !== '') {
            return trim((string) strip_tags($full));
        }

        $short = (string) $question->getShortAnswer();
        return trim((string) strip_tags($short));
    }
}
