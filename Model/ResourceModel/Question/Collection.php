<?php
/**
 * Magendoo Faq Question Collection
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model\ResourceModel\Question;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Model\Question;
use Magendoo\Faq\Model\ResourceModel\Question as ResourceQuestion;

/**
 * FAQ Question Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'question_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'magendoo_faq_question_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'faq_question_collection';

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(Question::class, ResourceQuestion::class);
    }

    /**
     * Add store filter
     *
     * @param int $storeId
     * @return $this
     */
    public function addStoreFilter(int $storeId): static
    {
        // LEFT JOIN so questions with no explicit store relation (e.g. created
        // via REST API without store_ids) still appear.
        $this->getSelect()->joinLeft(
            ['q_store' => $this->getTable('magendoo_faq_question_store')],
            'main_table.question_id = q_store.question_id',
            []
        )->where('q_store.store_id IS NULL OR q_store.store_id IN (?)', [0, $storeId])
         ->group('main_table.question_id');

        return $this;
    }

    /**
     * Add active filter (status = answered)
     *
     * @return $this
     */
    public function addActiveFilter(): static
    {
        $this->addFieldToFilter('status', QuestionInterface::STATUS_ANSWERED);
        return $this;
    }

    /**
     * Add category filter
     *
     * @param int $categoryId
     * @return $this
     */
    public function addCategoryFilter(int $categoryId): static
    {
        $this->getSelect()->join(
            ['q_cat' => $this->getTable('magendoo_faq_question_category')],
            'main_table.question_id = q_cat.question_id',
            []
        )->where('q_cat.category_id = ?', $categoryId);

        return $this;
    }

    /**
     * Add product filter
     *
     * @param int $productId
     * @return $this
     */
    public function addProductFilter(int $productId): static
    {
        $this->getSelect()->join(
            ['q_prod' => $this->getTable('magendoo_faq_question_product')],
            'main_table.question_id = q_prod.question_id',
            []
        )->where('q_prod.product_id = ?', $productId);

        return $this;
    }

    /**
     * Add visibility filter
     *
     * @param string $visibility
     * @return $this
     */
    public function addVisibilityFilter(string $visibility): static
    {
        $this->addFieldToFilter('visibility', $visibility);
        return $this;
    }

    /**
     * Add search filter (searches in title, short_answer, full_answer)
     *
     * @param string $queryText
     * @return $this
     */
    public function addSearchFilter(string $queryText): static
    {
        $this->getSelect()->where(
            'main_table.title LIKE ? OR main_table.short_answer LIKE ? OR main_table.full_answer LIKE ?',
            '%' . $queryText . '%',
            '%' . $queryText . '%',
            '%' . $queryText . '%'
        );

        return $this;
    }

    /**
     * Add customer group filter (strict — only rows linked to this exact group).
     *
     * @param int $groupId
     * @return $this
     */
    public function addCustomerGroupFilter(int $groupId): static
    {
        $this->getSelect()->join(
            ['q_group' => $this->getTable('magendoo_faq_question_customer_group')],
            'main_table.question_id = q_group.question_id',
            []
        )->where('q_group.customer_group_id = ?', $groupId);

        return $this;
    }

    /**
     * Add customer-group visibility filter (lenient).
     *
     * Shows questions that either:
     *  - have NO rows in the customer_group junction (visible to everyone), OR
     *  - have a row matching the given group ID.
     *
     * @param int $groupId Current customer's group ID.
     * @return $this
     */
    public function addCustomerGroupVisibilityFilter(int $groupId): static
    {
        $junction = $this->getTable('magendoo_faq_question_customer_group');
        $noRestriction = "NOT EXISTS (SELECT 1 FROM {$junction} qcg WHERE qcg.question_id = main_table.question_id)";
        $matchesGroup = "EXISTS (SELECT 1 FROM {$junction} qcg WHERE qcg.question_id = main_table.question_id AND qcg.customer_group_id = " . (int) $groupId . ')';
        $this->getSelect()->where("({$noRestriction}) OR ({$matchesGroup})");

        return $this;
    }
}
