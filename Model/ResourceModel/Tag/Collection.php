<?php
/**
 * Magendoo Faq Tag Collection
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model\ResourceModel\Tag;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Magendoo\Faq\Model\Tag;
use Magendoo\Faq\Model\ResourceModel\Tag as ResourceTag;

/**
 * FAQ Tag Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'tag_id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'magendoo_faq_tag_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'faq_tag_collection';

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(Tag::class, ResourceTag::class);
    }
}
