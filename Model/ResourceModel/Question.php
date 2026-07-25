<?php
/**
 * Magendoo Faq Question Resource Model
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model\ResourceModel;

use Magendoo\Faq\Api\Data\QuestionInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * FAQ Question Resource Model
 */
class Question extends AbstractDb
{
    /**
     * Question table
     */
    public const TABLE_NAME = 'magendoo_faq_question';

    /**
     * Question store relation table
     */
    public const TABLE_QUESTION_STORE = 'magendoo_faq_question_store';

    /**
     * Question category relation table
     */
    public const TABLE_QUESTION_CATEGORY = 'magendoo_faq_question_category';

    /**
     * Question product relation table
     */
    public const TABLE_QUESTION_PRODUCT = 'magendoo_faq_question_product';

    /**
     * Question tag relation table
     */
    public const TABLE_QUESTION_TAG = 'magendoo_faq_question_tag';

    /**
     * Question customer group relation table
     */
    public const TABLE_QUESTION_CUSTOMER_GROUP = 'magendoo_faq_question_customer_group';

    /**
     * @var DateTime
     */
    protected DateTime $dateTime;

    /**
     * @param Context $context
     * @param DateTime $dateTime
     * @param string|null $connectionName
     */
    public function __construct(
        Context $context,
        DateTime $dateTime,
        ?string $connectionName = null
    ) {
        $this->dateTime = $dateTime;
        parent::__construct($context, $connectionName);
    }

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(self::TABLE_NAME, 'question_id');
    }

    /**
     * @inheritdoc
     *
     * @throws LocalizedException
     */
    protected function _beforeSave(AbstractModel $object): void
    {
        if (!$object->getId()) {
            $object->setCreatedAt($this->dateTime->gmtDate());
        }
        $object->setUpdatedAt($this->dateTime->gmtDate());

        parent::_beforeSave($object);
    }

    /**
     * @inheritdoc
     *
     * @throws LocalizedException
     */
    protected function _afterSave(AbstractModel $object): AbstractDb
    {
        $this->saveStoreRelation($object);
        $this->saveCategoryRelation($object);
        $this->saveProductRelation($object);
        $this->saveTagRelation($object);
        $this->saveCustomerGroupRelation($object);

        return parent::_afterSave($object);
    }

    /**
     * @inheritdoc
     *
     * @throws LocalizedException
     */
    protected function _afterLoad(AbstractModel $object): AbstractDb
    {
        $this->loadStoreIds($object);
        $this->loadCategoryIds($object);
        $this->loadProductIds($object);
        $this->loadTagIds($object);
        $this->loadCustomerGroupIds($object);

        return parent::_afterLoad($object);
    }

    /**
     * Get question ID by URL key and store ID
     *
     * Only answered questions with a storefront-visible visibility resolve:
     * "public" always, "logged_in" only when the caller is an authenticated
     * customer, "none" never. The login state travels as a parameter (safe
     * default: anonymous) because a resource model must not depend on
     * session state — every caller resolves it in its own context.
     *
     * @param string $urlKey
     * @param int $storeId
     * @param bool $isLoggedIn Whether the caller is an authenticated customer
     * @return int|false
     * @throws LocalizedException
     */
    public function getByUrlKey(string $urlKey, int $storeId, bool $isLoggedIn = false): int|false
    {
        $connection = $this->getConnection();

        $visibilities = [QuestionInterface::VISIBILITY_PUBLIC];
        if ($isLoggedIn) {
            $visibilities[] = QuestionInterface::VISIBILITY_LOGGED_IN;
        }

        // LEFT JOIN so a question with no store relation (e.g. created via API
        // without explicit store_ids) is still findable.
        $select = $connection->select()
            ->from(['main' => $this->getMainTable()], 'question_id')
            ->joinLeft(
                ['store' => $this->getTable(self::TABLE_QUESTION_STORE)],
                'main.question_id = store.question_id',
                []
            )
            ->where('main.url_key = ?', $urlKey)
            ->where('main.status = ?', QuestionInterface::STATUS_ANSWERED)
            ->where('main.visibility IN (?)', $visibilities)
            ->where('store.store_id IS NULL OR store.store_id IN (?)', [0, $storeId])
            ->limit(1);

        $result = $connection->fetchOne($select);

        return $result ? (int) $result : false;
    }

    /**
     * Increment view count for a question
     *
     * @param int $questionId
     * @return void
     * @throws LocalizedException
     */
    public function incrementViewCount(int $questionId): void
    {
        $connection = $this->getConnection();
        $connection->update(
            $this->getMainTable(),
            ['view_count' => new \Zend_Db_Expr('view_count + 1')],
            ['question_id = ?' => $questionId]
        );
    }

    /**
     * Save store relations
     *
     * @param AbstractModel $object
     * @return void
     * @throws LocalizedException
     */
    private function saveStoreRelation(AbstractModel $object): void
    {
        $storeIds = $object->getData('store_ids');
        if ($storeIds === null) {
            return;
        }

        $connection = $this->getConnection();
        $table = $this->getTable(self::TABLE_QUESTION_STORE);
        $questionId = (int) $object->getId();

        $connection->delete($table, ['question_id = ?' => $questionId]);

        if (!empty($storeIds)) {
            $data = [];
            foreach ((array) $storeIds as $storeId) {
                $data[] = [
                    'question_id' => $questionId,
                    'store_id' => (int) $storeId,
                ];
            }
            $connection->insertMultiple($table, $data);
        }
    }

    /**
     * Load store IDs for question
     *
     * @param AbstractModel $object
     * @return void
     * @throws LocalizedException
     */
    private function loadStoreIds(AbstractModel $object): void
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::TABLE_QUESTION_STORE), 'store_id')
            ->where('question_id = ?', (int) $object->getId());

        $storeIds = $connection->fetchCol($select);
        $object->setData('store_ids', array_map('intval', $storeIds));
    }

    /**
     * Save category relations
     *
     * @param AbstractModel $object
     * @return void
     * @throws LocalizedException
     */
    private function saveCategoryRelation(AbstractModel $object): void
    {
        $categoryIds = $object->getData('category_ids');
        if ($categoryIds === null) {
            return;
        }

        $connection = $this->getConnection();
        $table = $this->getTable(self::TABLE_QUESTION_CATEGORY);
        $questionId = (int) $object->getId();

        $connection->delete($table, ['question_id = ?' => $questionId]);

        if (!empty($categoryIds)) {
            $data = [];
            foreach ((array) $categoryIds as $categoryId) {
                $data[] = [
                    'question_id' => $questionId,
                    'category_id' => (int) $categoryId,
                ];
            }
            $connection->insertMultiple($table, $data);
        }
    }

    /**
     * Load category IDs for question
     *
     * @param AbstractModel $object
     * @return void
     * @throws LocalizedException
     */
    private function loadCategoryIds(AbstractModel $object): void
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::TABLE_QUESTION_CATEGORY), 'category_id')
            ->where('question_id = ?', (int) $object->getId());

        $categoryIds = $connection->fetchCol($select);
        $object->setData('category_ids', array_map('intval', $categoryIds));
    }

    /**
     * Save product relations
     *
     * @param AbstractModel $object
     * @return void
     * @throws LocalizedException
     */
    private function saveProductRelation(AbstractModel $object): void
    {
        $productIds = $object->getData('product_ids');
        if ($productIds === null) {
            return;
        }

        $connection = $this->getConnection();
        $table = $this->getTable(self::TABLE_QUESTION_PRODUCT);
        $questionId = (int) $object->getId();

        $connection->delete($table, ['question_id = ?' => $questionId]);

        if (!empty($productIds)) {
            $data = [];
            foreach ((array) $productIds as $productId) {
                $data[] = [
                    'question_id' => $questionId,
                    'product_id' => (int) $productId,
                ];
            }
            $connection->insertMultiple($table, $data);
        }
    }

    /**
     * Load product IDs for question
     *
     * @param AbstractModel $object
     * @return void
     * @throws LocalizedException
     */
    private function loadProductIds(AbstractModel $object): void
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::TABLE_QUESTION_PRODUCT), 'product_id')
            ->where('question_id = ?', (int) $object->getId());

        $productIds = $connection->fetchCol($select);
        $object->setData('product_ids', array_map('intval', $productIds));
    }

    /**
     * Look up store IDs linked to a question (public accessor used by UI data providers).
     *
     * @param int $questionId
     * @return int[]
     */
    public function lookupStoreIds(int $questionId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::TABLE_QUESTION_STORE), 'store_id')
            ->where('question_id = ?', $questionId);

        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * Look up category IDs linked to a question.
     *
     * @param int $questionId
     * @return int[]
     */
    public function lookupCategoryIds(int $questionId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::TABLE_QUESTION_CATEGORY), 'category_id')
            ->where('question_id = ?', $questionId);

        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * Look up product IDs linked to a question.
     *
     * @param int $questionId
     * @return int[]
     */
    public function lookupProductIds(int $questionId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::TABLE_QUESTION_PRODUCT), 'product_id')
            ->where('question_id = ?', $questionId);

        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * Look up tag IDs linked to a question.
     *
     * @param int $questionId
     * @return int[]
     */
    public function lookupTagIds(int $questionId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::TABLE_QUESTION_TAG), 'tag_id')
            ->where('question_id = ?', $questionId);

        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * Look up customer group IDs linked to a question.
     *
     * @param int $questionId
     * @return int[]
     */
    public function lookupCustomerGroupIds(int $questionId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::TABLE_QUESTION_CUSTOMER_GROUP), 'customer_group_id')
            ->where('question_id = ?', $questionId);

        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * Save tag relations
     *
     * @param AbstractModel $object
     * @return void
     * @throws LocalizedException
     */
    private function saveTagRelation(AbstractModel $object): void
    {
        // The repository save path transfers incoming tag assignments under
        // the 'tags' key (see \Magendoo\Faq\Model\QuestionRepository::save()),
        // while _afterLoad() hydrates 'tag_ids' from the junction table. An
        // explicitly set 'tags' key must win, otherwise a repository save
        // would silently re-persist the values loaded from the junction and
        // the submitted assignment would never reach the table.
        $tagIds = $object->hasData('tags') ? $object->getData('tags') : $object->getData('tag_ids');
        if ($tagIds === null) {
            return;
        }

        $connection = $this->getConnection();
        $table = $this->getTable(self::TABLE_QUESTION_TAG);
        $questionId = (int) $object->getId();

        $connection->delete($table, ['question_id = ?' => $questionId]);

        if (!empty($tagIds)) {
            $data = [];
            foreach ((array) $tagIds as $tagId) {
                $data[] = [
                    'question_id' => $questionId,
                    'tag_id' => (int) $tagId,
                ];
            }
            $connection->insertMultiple($table, $data);
        }
    }

    /**
     * Load tag IDs for question
     *
     * @param AbstractModel $object
     * @return void
     * @throws LocalizedException
     */
    private function loadTagIds(AbstractModel $object): void
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::TABLE_QUESTION_TAG), 'tag_id')
            ->where('question_id = ?', (int) $object->getId());

        $tagIds = $connection->fetchCol($select);
        $object->setData('tag_ids', array_map('intval', $tagIds));
    }

    /**
     * Save customer group relations
     *
     * @param AbstractModel $object
     * @return void
     * @throws LocalizedException
     */
    private function saveCustomerGroupRelation(AbstractModel $object): void
    {
        $customerGroupIds = $object->getData('customer_group_ids');
        if ($customerGroupIds === null) {
            return;
        }

        $connection = $this->getConnection();
        $table = $this->getTable(self::TABLE_QUESTION_CUSTOMER_GROUP);
        $questionId = (int) $object->getId();

        $connection->delete($table, ['question_id = ?' => $questionId]);

        if (!empty($customerGroupIds)) {
            $data = [];
            foreach ((array) $customerGroupIds as $groupId) {
                $data[] = [
                    'question_id' => $questionId,
                    'customer_group_id' => (int) $groupId,
                ];
            }
            $connection->insertMultiple($table, $data);
        }
    }

    /**
     * Load customer group IDs for question
     *
     * @param AbstractModel $object
     * @return void
     * @throws LocalizedException
     */
    private function loadCustomerGroupIds(AbstractModel $object): void
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::TABLE_QUESTION_CUSTOMER_GROUP), 'customer_group_id')
            ->where('question_id = ?', (int) $object->getId());

        $groupIds = $connection->fetchCol($select);
        $object->setData('customer_group_ids', array_map('intval', $groupIds));
    }
}
