<?php
/**
 * Magendoo FAQ Questions List Widget Block
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Block\Widget;

use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\ResourceModel\Question\Collection;
use Magendoo\Faq\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Widget\Block\BlockInterface;

/**
 * FAQ Questions List Widget
 */
class QuestionsList extends Template implements BlockInterface
{
    /**
     * Default question count
     */
    private const DEFAULT_QUESTION_COUNT = 5;

    /**
     * Default answer preview length
     */
    private const DEFAULT_ANSWER_PREVIEW_LENGTH = 200;

    /**
     * @var QuestionCollectionFactory
     */
    private QuestionCollectionFactory $questionCollectionFactory;

    /**
     * @var FaqHelper
     */
    private FaqHelper $helper;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @param Context $context
     * @param QuestionCollectionFactory $questionCollectionFactory
     * @param FaqHelper $helper
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        QuestionCollectionFactory $questionCollectionFactory,
        FaqHelper $helper,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->questionCollectionFactory = $questionCollectionFactory;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        parent::__construct($context, $data);
    }

    /**
     * Get FAQ questions collection
     *
     * @return Collection
     */
    public function getQuestions(): Collection
    {
        $collection = $this->questionCollectionFactory->create();
        $collection->addActiveFilter();
        $collection->addVisibilityFilter(QuestionInterface::VISIBILITY_PUBLIC);

        $storeId = (int) $this->storeManager->getStore()->getId();
        $collection->addStoreFilter($storeId);
        $collection->addCustomerGroupVisibilityFilter(
            (int) $this->customerSession->getCustomerGroupId()
        );

        $categoryId = (int) $this->getData('category_id');
        if ($categoryId) {
            $collection->addCategoryFilter($categoryId);
        }

        $limit = (int) $this->getData('question_count');
        if ($limit <= 0) {
            $limit = self::DEFAULT_QUESTION_COUNT;
        }

        $collection->setOrder('position', 'ASC');
        $collection->setPageSize($limit);
        $collection->setCurPage(1);

        return $collection;
    }

    /**
     * Get block title
     *
     * @return string
     */
    public function getBlockTitle(): string
    {
        return (string) $this->getData('title');
    }

    /**
     * Check if answer preview should be shown
     *
     * @return bool
     */
    public function showAnswer(): bool
    {
        return (bool) $this->getData('show_answer');
    }

    /**
     * Get question URL
     *
     * @param QuestionInterface $question
     * @return string
     */
    public function getQuestionUrl(QuestionInterface $question): string
    {
        $urlKey = $question->getUrlKey();
        if ($urlKey) {
            return $this->getBaseUrl() . $this->helper->buildUrlPath($urlKey);
        }

        return $this->getUrl('faq/question/view', ['id' => $question->getQuestionId()]);
    }

    /**
     * Get answer preview text
     *
     * @param QuestionInterface $question
     * @return string
     */
    public function getAnswerPreview(QuestionInterface $question): string
    {
        $shortAnswer = $question->getShortAnswer();
        if ($shortAnswer !== null && $shortAnswer !== '') {
            return $shortAnswer;
        }

        $fullAnswer = $question->getFullAnswer();
        if ($fullAnswer === null || $fullAnswer === '') {
            return '';
        }

        $text = strip_tags($fullAnswer);
        if (mb_strlen($text) <= self::DEFAULT_ANSWER_PREVIEW_LENGTH) {
            return $text;
        }

        return mb_substr($text, 0, self::DEFAULT_ANSWER_PREVIEW_LENGTH) . '...';
    }
}
