<?php
/**
 * Magendoo Faq Category View Block
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Block\Faq\Category;

use Magendoo\Faq\Api\CategoryRepositoryInterface;
use Magendoo\Faq\Api\Data\CategoryInterface;
use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * FAQ Category View Block
 */
class View extends Template
{
    /**
     * @var QuestionCollectionFactory
     */
    private QuestionCollectionFactory $questionCollectionFactory;

    /**
     * @var CategoryRepositoryInterface
     */
    private CategoryRepositoryInterface $categoryRepository;

    /**
     * @var FaqHelper
     */
    private FaqHelper $helper;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var CategoryInterface|null
     */
    private ?CategoryInterface $category = null;

    /**
     * @param Context $context
     * @param QuestionCollectionFactory $questionCollectionFactory
     * @param CategoryRepositoryInterface $categoryRepository
     * @param FaqHelper $helper
     * @param CustomerSession $customerSession
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        QuestionCollectionFactory $questionCollectionFactory,
        CategoryRepositoryInterface $categoryRepository,
        FaqHelper $helper,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->questionCollectionFactory = $questionCollectionFactory;
        $this->categoryRepository = $categoryRepository;
        $this->helper = $helper;
        $this->customerSession = $customerSession;
        parent::__construct($context, $data);
    }

    /**
     * Get current category
     *
     * @return CategoryInterface|null
     */
    public function getCategory(): ?CategoryInterface
    {
        if ($this->category === null) {
            $categoryId = (int) $this->getRequest()->getParam('id');
            if ($categoryId) {
                try {
                    $this->category = $this->categoryRepository->getById($categoryId);
                } catch (NoSuchEntityException $e) {
                    $this->category = null;
                }
            }
        }

        return $this->category;
    }

    /**
     * Get questions for current category and store
     *
     * @return \Magendoo\Faq\Model\ResourceModel\Question\Collection
     */
    public function getQuestions(): \Magendoo\Faq\Model\ResourceModel\Question\Collection
    {
        $collection = $this->questionCollectionFactory->create();
        $collection->addFieldToFilter('visibility', QuestionInterface::VISIBILITY_PUBLIC);
        $collection->addFieldToFilter('status', QuestionInterface::STATUS_ANSWERED);

        $category = $this->getCategory();
        if ($category) {
            $collection->addCategoryFilter((int) $category->getCategoryId());
        }

        $storeId = (int) $this->_storeManager->getStore()->getId();
        $collection->addStoreFilter($storeId);
        $collection->addCustomerGroupVisibilityFilter((int) $this->customerSession->getCustomerGroupId());

        $sortBy = $this->helper->getSortQuestionsBy();
        if ($sortBy === 'name' || $sortBy === 'title') {
            $collection->setOrder('title', 'ASC');
        } else {
            $collection->setOrder('position', 'ASC');
        }

        $pageSize = $this->helper->getQuestionsPerCategoryPage();
        if ($pageSize > 0) {
            $collection->setPageSize($pageSize);
            $currentPage = (int) $this->getRequest()->getParam('p', 1);
            $collection->setCurPage($currentPage);
        }

        return $collection;
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
     * Get pager HTML
     *
     * @return string
     */
    public function getPagerHtml(): string
    {
        $pagerBlock = $this->getChildBlock('faq.category.pager');
        if ($pagerBlock) {
            return $pagerBlock->toHtml();
        }

        /** @var \Magento\Theme\Block\Html\Pager $pager */
        $pager = $this->getLayout()->createBlock(
            \Magento\Theme\Block\Html\Pager::class,
            'faq.category.pager'
        );

        $pager->setAvailableLimit([$this->helper->getQuestionsPerCategoryPage() => $this->helper->getQuestionsPerCategoryPage()]);
        $pager->setCollection($this->getQuestions());

        return $pager->toHtml();
    }

    /**
     * Get answer preview for a question
     *
     * @param QuestionInterface $question
     * @return string
     */
    public function getAnswerPreview(QuestionInterface $question): string
    {
        $behavior = $this->helper->getShortAnswerBehavior();

        if ($behavior === 'short_answer' && $question->getShortAnswer()) {
            return $question->getShortAnswer();
        }

        $fullAnswer = $question->getFullAnswer();
        if (!$fullAnswer) {
            return '';
        }

        $limit = $this->helper->getAnswerLengthLimit();
        $plainText = strip_tags($fullAnswer);

        if (mb_strlen($plainText) <= $limit) {
            return $plainText;
        }

        return mb_substr($plainText, 0, $limit) . '...';
    }
}
