<?php
/**
 * Magendoo Faq Tag Resource Model
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
 * FAQ Tag Resource Model
 */
class Tag extends AbstractDb
{
    /**
     * Tag table
     */
    public const TABLE_NAME = 'magendoo_faq_tag';

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
        $this->_init(self::TABLE_NAME, 'tag_id');
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

        parent::_beforeSave($object);
    }
}
