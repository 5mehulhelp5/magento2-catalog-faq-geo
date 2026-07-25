<?php
/**
 * Magendoo FAQ Categories List Widget Block
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Block\Widget;

use Magendoo\Faq\Api\Data\CategoryInterface;
use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\Category as CategoryModel;
use Magendoo\Faq\Model\Question as QuestionModel;
use Magendoo\Faq\Model\ResourceModel\Category\Collection;
use Magendoo\Faq\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magendoo\Faq\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Widget\Block\BlockInterface;

/**
 * FAQ Categories List Widget
 */
class CategoriesList extends Template implements BlockInterface, IdentityInterface
{
    /**
     * @var CategoryCollectionFactory
     */
    private CategoryCollectionFactory $categoryCollectionFactory;

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
     * @var Collection|null
     */
    private ?Collection $categories = null;

    /**
     * @var array<int, int>|null
     */
    private ?array $questionCounts = null;

    /**
     * @param Context $context
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param QuestionCollectionFactory $questionCollectionFactory
     * @param FaqHelper $helper
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        CategoryCollectionFactory $categoryCollectionFactory,
        QuestionCollectionFactory $questionCollectionFactory,
        FaqHelper $helper,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->questionCollectionFactory = $questionCollectionFactory;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        parent::__construct($context, $data);
    }

    /**
     * Get enabled FAQ categories
     *
     * @return Collection
     */
    public function getCategories(): Collection
    {
        if ($this->categories === null) {
            $collection = $this->categoryCollectionFactory->create();
            $collection->addActiveFilter();

            $storeId = (int) $this->storeManager->getStore()->getId();
            $collection->addStoreFilter($storeId);
            $collection->addCustomerGroupVisibilityFilter(
                (int) $this->customerSession->getCustomerGroupId()
            );

            $collection->setOrder('position', 'ASC');

            $this->categories = $collection;
        }

        return $this->categories;
    }

    /**
     * Return identifiers for produced content
     *
     * Union of the rendered categories' identities plus the bare category list tag;
     * when question counts are shown the bare question list tag is added too, since
     * any question save can change the displayed numbers.
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        $identities = [[CategoryModel::CACHE_TAG]];
        if ($this->showQuestionCount()) {
            $identities[] = [QuestionModel::CACHE_TAG];
        }

        foreach ($this->getCategories() as $category) {
            if ($category instanceof IdentityInterface) {
                $identities[] = $category->getIdentities();
            }
        }

        return array_merge([], ...$identities);
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
     * Check if question count should be shown
     *
     * @return bool
     */
    public function showQuestionCount(): bool
    {
        return (bool) $this->getData('show_question_count');
    }

    /**
     * Get count of public answered questions in a category
     *
     * @param CategoryInterface $category
     * @return int
     */
    public function getQuestionCount(CategoryInterface $category): int
    {
        $counts = $this->loadQuestionCounts();

        return $counts[(int) $category->getCategoryId()] ?? 0;
    }

    /**
     * Load per-category question counts in a single grouped query
     *
     * Mirrors the filters the per-category collection applied (answered,
     * public, current store or no store restriction, current customer group
     * or no group restriction) so the numbers match what a shopper can open.
     *
     * @return array<int, int>
     */
    private function loadQuestionCounts(): array
    {
        if ($this->questionCounts === null) {
            $this->questionCounts = [];

            $collection = $this->questionCollectionFactory->create();
            $connection = $collection->getConnection();
            $storeId = (int) $this->storeManager->getStore()->getId();
            $groupId = (int) $this->customerSession->getCustomerGroupId();
            $groupJunction = $collection->getTable('magendoo_faq_question_customer_group');
            $noRestriction =
                "NOT EXISTS (SELECT 1 FROM {$groupJunction} qcg WHERE qcg.question_id = q.question_id)";
            $matchesGroup = "EXISTS (SELECT 1 FROM {$groupJunction} qcg WHERE qcg.question_id = q.question_id"
                . ' AND qcg.customer_group_id = ' . $groupId . ')';

            $select = $connection->select()
                ->from(
                    ['q_cat' => $collection->getTable('magendoo_faq_question_category')],
                    [
                        'category_id',
                        'cnt' => new \Zend_Db_Expr('COUNT(DISTINCT q_cat.question_id)'),
                    ]
                )
                ->join(
                    ['q' => $collection->getTable('magendoo_faq_question')],
                    'q_cat.question_id = q.question_id',
                    []
                )
                ->joinLeft(
                    ['q_store' => $collection->getTable('magendoo_faq_question_store')],
                    'q.question_id = q_store.question_id',
                    []
                )
                ->where('q.status = ?', QuestionInterface::STATUS_ANSWERED)
                ->where('q.visibility = ?', QuestionInterface::VISIBILITY_PUBLIC)
                ->where('q_store.store_id IS NULL OR q_store.store_id IN (?)', [0, $storeId])
                ->where("({$noRestriction}) OR ({$matchesGroup})")
                ->group('q_cat.category_id');

            foreach ($connection->fetchAll($select) as $row) {
                $this->questionCounts[(int) $row['category_id']] = (int) $row['cnt'];
            }
        }

        return $this->questionCounts;
    }

    /**
     * Get category URL
     *
     * @param CategoryInterface $category
     * @return string
     */
    public function getCategoryUrl(CategoryInterface $category): string
    {
        $urlKey = $category->getUrlKey();
        if ($urlKey) {
            return $this->getBaseUrl() . $this->helper->buildUrlPath($urlKey);
        }

        return $this->getUrl('faq/category/view', ['id' => $category->getCategoryId()]);
    }
}
