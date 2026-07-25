<?php
/**
 * Magendoo Faq Search Log Model
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model;

use Magento\Framework\Model\AbstractModel;
use Magendoo\Faq\Model\ResourceModel\SearchLog as ResourceSearchLog;

/**
 * FAQ Search Log Model
 */
class SearchLog extends AbstractModel
{
    /**
     * Constants for field names
     */
    public const LOG_ID = 'log_id';
    public const QUERY_TEXT = 'query_text';
    public const STORE_ID = 'store_id';
    public const RESULTS_COUNT = 'results_count';
    public const CREATED_AT = 'created_at';

    /**
     * @var string
     */
    protected $_eventPrefix = 'magendoo_faq_search_log';

    /**
     * @var string
     */
    protected $_eventObject = 'faq_search_log';

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(ResourceSearchLog::class);
    }

    /**
     * Get log ID
     *
     * @return int|null
     */
    public function getLogId(): ?int
    {
        $id = $this->getData(self::LOG_ID);
        return $id ? (int) $id : null;
    }

    /**
     * Set log ID
     *
     * @param int $logId
     * @return $this
     */
    public function setLogId(int $logId): static
    {
        return $this->setData(self::LOG_ID, $logId);
    }

    /**
     * Get query text
     *
     * @return string|null
     */
    public function getQueryText(): ?string
    {
        return $this->getData(self::QUERY_TEXT);
    }

    /**
     * Set query text
     *
     * @param string $queryText
     * @return $this
     */
    public function setQueryText(string $queryText): static
    {
        return $this->setData(self::QUERY_TEXT, $queryText);
    }

    /**
     * Get store ID
     *
     * @return int|null
     */
    public function getStoreId(): ?int
    {
        $id = $this->getData(self::STORE_ID);
        return $id ? (int) $id : null;
    }

    /**
     * Set store ID
     *
     * @param int $storeId
     * @return $this
     */
    public function setStoreId(int $storeId): static
    {
        return $this->setData(self::STORE_ID, $storeId);
    }

    /**
     * Get results count
     *
     * @return int
     */
    public function getResultsCount(): int
    {
        return (int) $this->getData(self::RESULTS_COUNT);
    }

    /**
     * Set results count
     *
     * @param int $resultsCount
     * @return $this
     */
    public function setResultsCount(int $resultsCount): static
    {
        return $this->setData(self::RESULTS_COUNT, $resultsCount);
    }

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * Set created at
     *
     * @param string|null $createdAt
     * @return $this
     */
    public function setCreatedAt(?string $createdAt): static
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
