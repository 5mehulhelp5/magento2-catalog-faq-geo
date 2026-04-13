<?php
/**
 * Magendoo Faq Category Collection
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model\ResourceModel\Category;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magendoo\Faq\Model\Category;
use Magendoo\Faq\Model\ResourceModel\Category as ResourceCategory;

/**
 * FAQ Category Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'category_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'magendoo_faq_category_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'faq_category_collection';

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(Category::class, ResourceCategory::class);
    }

    /**
     * Add store filter
     *
     * @param int $storeId
     * @return $this
     */
    public function addStoreFilter(int $storeId): static
    {
        // LEFT JOIN so categories with no explicit store relation (e.g. created
        // via REST API without store_ids) still appear. Match: no relation OR
        // matching store OR "all stores" (store_id 0).
        $this->getSelect()->joinLeft(
            ['cat_store' => $this->getTable('magendoo_faq_category_store')],
            'main_table.category_id = cat_store.category_id',
            []
        )->where('cat_store.store_id IS NULL OR cat_store.store_id IN (?)', [0, $storeId])
         ->group('main_table.category_id');

        return $this;
    }

    /**
     * Add active filter
     *
     * @return $this
     */
    public function addActiveFilter(): static
    {
        $this->addFieldToFilter('status', 1);
        return $this;
    }

    /**
     * Add customer group filter
     *
     * @param int $groupId
     * @return $this
     */
    public function addCustomerGroupFilter(int $groupId): static
    {
        $this->getSelect()->join(
            ['cat_group' => $this->getTable('magendoo_faq_category_customer_group')],
            'main_table.category_id = cat_group.category_id',
            []
        )->where('cat_group.customer_group_id = ?', $groupId);

        return $this;
    }

    /**
     * Lenient visibility filter: show categories with NO group restriction
     * OR categories matching the given group ID.
     *
     * @param int $groupId
     * @return $this
     */
    public function addCustomerGroupVisibilityFilter(int $groupId): static
    {
        $junction = $this->getTable('magendoo_faq_category_customer_group');
        $noRestriction = "NOT EXISTS (SELECT 1 FROM {$junction} ccg WHERE ccg.category_id = main_table.category_id)";
        $matchesGroup = "EXISTS (SELECT 1 FROM {$junction} ccg WHERE ccg.category_id = main_table.category_id AND ccg.customer_group_id = " . (int) $groupId . ')';
        $this->getSelect()->where("({$noRestriction}) OR ({$matchesGroup})");

        return $this;
    }
}
