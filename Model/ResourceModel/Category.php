<?php
/**
 * Magendoo Faq Category Resource Model
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model\ResourceModel;

use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Model\AbstractModel;
use Magento\Framework\Model\ResourceModel\Db\AbstractDb;
use Magento\Framework\Model\ResourceModel\Db\Context;
use Magento\Framework\Stdlib\DateTime\DateTime;

/**
 * FAQ Category Resource Model
 */
class Category extends AbstractDb
{
    /**
     * Category table
     */
    public const TABLE_NAME = 'magendoo_faq_category';

    /**
     * Category store relation table
     */
    public const TABLE_CATEGORY_STORE = 'magendoo_faq_category_store';

    /**
     * Category customer group relation table
     */
    public const TABLE_CATEGORY_CUSTOMER_GROUP = 'magendoo_faq_category_customer_group';

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
        $this->_init(self::TABLE_NAME, 'category_id');
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
        $this->loadCustomerGroupIds($object);

        return parent::_afterLoad($object);
    }

    /**
     * Get category ID by URL key and store ID
     *
     * @param string $urlKey
     * @param int $storeId
     * @return int|false
     * @throws LocalizedException
     */
    public function getByUrlKey(string $urlKey, int $storeId): int|false
    {
        $connection = $this->getConnection();

        // LEFT JOIN so a category with no store relation (e.g. created via API
        // without explicit store_ids) is still findable. Filter by matching
        // store OR "all stores" (0) OR no relation at all.
        $select = $connection->select()
            ->from(['main' => $this->getMainTable()], 'category_id')
            ->joinLeft(
                ['store' => $this->getTable(self::TABLE_CATEGORY_STORE)],
                'main.category_id = store.category_id',
                []
            )
            ->where('main.url_key = ?', $urlKey)
            ->where('main.status = ?', 1)
            ->where('store.store_id IS NULL OR store.store_id IN (?)', [0, $storeId])
            ->limit(1);

        $result = $connection->fetchOne($select);

        return $result ? (int) $result : false;
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
        $table = $this->getTable(self::TABLE_CATEGORY_STORE);
        $categoryId = (int) $object->getId();

        // Delete existing relations
        $connection->delete($table, ['category_id = ?' => $categoryId]);

        // Insert new relations
        if (!empty($storeIds)) {
            $data = [];
            foreach ((array) $storeIds as $storeId) {
                $data[] = [
                    'category_id' => $categoryId,
                    'store_id' => (int) $storeId,
                ];
            }
            $connection->insertMultiple($table, $data);
        }
    }

    /**
     * Load store IDs for category
     *
     * @param AbstractModel $object
     * @return void
     * @throws LocalizedException
     */
    private function loadStoreIds(AbstractModel $object): void
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::TABLE_CATEGORY_STORE), 'store_id')
            ->where('category_id = ?', (int) $object->getId());

        $storeIds = $connection->fetchCol($select);
        $object->setData('store_ids', array_map('intval', $storeIds));
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
        $table = $this->getTable(self::TABLE_CATEGORY_CUSTOMER_GROUP);
        $categoryId = (int) $object->getId();

        // Delete existing relations
        $connection->delete($table, ['category_id = ?' => $categoryId]);

        // Insert new relations
        if (!empty($customerGroupIds)) {
            $data = [];
            foreach ((array) $customerGroupIds as $groupId) {
                $data[] = [
                    'category_id' => $categoryId,
                    'customer_group_id' => (int) $groupId,
                ];
            }
            $connection->insertMultiple($table, $data);
        }
    }

    /**
     * Load customer group IDs for category
     *
     * @param AbstractModel $object
     * @return void
     * @throws LocalizedException
     */
    private function loadCustomerGroupIds(AbstractModel $object): void
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::TABLE_CATEGORY_CUSTOMER_GROUP), 'customer_group_id')
            ->where('category_id = ?', (int) $object->getId());

        $groupIds = $connection->fetchCol($select);
        $object->setData('customer_group_ids', array_map('intval', $groupIds));
    }

    /**
     * Look up store IDs linked to a category (public accessor used by UI data providers).
     *
     * @param int $categoryId
     * @return int[]
     */
    public function lookupStoreIds(int $categoryId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::TABLE_CATEGORY_STORE), 'store_id')
            ->where('category_id = ?', $categoryId);

        return array_map('intval', $connection->fetchCol($select));
    }

    /**
     * Look up customer group IDs linked to a category.
     *
     * @param int $categoryId
     * @return int[]
     */
    public function lookupCustomerGroupIds(int $categoryId): array
    {
        $connection = $this->getConnection();
        $select = $connection->select()
            ->from($this->getTable(self::TABLE_CATEGORY_CUSTOMER_GROUP), 'customer_group_id')
            ->where('category_id = ?', $categoryId);

        return array_map('intval', $connection->fetchCol($select));
    }
}
