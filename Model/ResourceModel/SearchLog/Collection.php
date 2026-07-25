<?php
/**
 * Magendoo Faq Search Log Collection
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model\ResourceModel\SearchLog;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magendoo\Faq\Model\SearchLog;
use Magendoo\Faq\Model\ResourceModel\SearchLog as ResourceSearchLog;

/**
 * FAQ Search Log Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'log_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'magendoo_faq_search_log_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'faq_search_log_collection';

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(SearchLog::class, ResourceSearchLog::class);
    }
}
