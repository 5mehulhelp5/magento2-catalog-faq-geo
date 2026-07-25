<?php
/**
 * Magendoo Faq Tag Model
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model;

use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Model\AbstractExtensibleModel;
use Magendoo\Faq\Api\Data\TagInterface;
use Magendoo\Faq\Model\ResourceModel\Tag as ResourceTag;

/**
 * FAQ Tag Model
 */
class Tag extends AbstractExtensibleModel implements TagInterface, IdentityInterface
{
    /**
     * Tag cache tag
     */
    public const CACHE_TAG = 'magendoo_faq_tag';

    /**
     * @var string
     */
    protected $_eventPrefix = 'magendoo_faq_tag';

    /**
     * @var string
     */
    protected $_eventObject = 'faq_tag';

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(ResourceTag::class);
    }

    /**
     * @inheritdoc
     */
    public function getIdentities(): array
    {
        $identities = [self::CACHE_TAG . '_' . $this->getId()];

        // Listing pages are tagged with the bare CACHE_TAG so that a NEW entity, a deleted one,
        // or one whose name or url_key changed shows up (or disappears) without waiting for the
        // page cache to expire. Without this, only pages already rendering this exact entity
        // would ever be purged, and a newly published entity would stay invisible.
        // Mirrors Magento\Catalog\Model\Category::getIdentities().
        if ($this->isObjectNew() || $this->isDeleted()) {
            $identities[] = self::CACHE_TAG;
        } else {
            foreach (['name', 'url_key'] as $field) {
                if ($this->dataHasChangedFor($field)) {
                    $identities[] = self::CACHE_TAG;
                    break;
                }
            }
        }

        return array_unique($identities);
    }

    /**
     * @inheritdoc
     */
    public function getTagId(): ?int
    {
        $id = $this->getData(self::TAG_ID);
        return $id ? (int) $id : null;
    }

    /**
     * @inheritdoc
     */
    public function setTagId(int $tagId): static
    {
        return $this->setData(self::TAG_ID, $tagId);
    }

    /**
     * @inheritdoc
     */
    public function getName(): string
    {
        return (string) $this->getData(self::NAME);
    }

    /**
     * @inheritdoc
     */
    public function setName(string $name): static
    {
        return $this->setData(self::NAME, $name);
    }

    /**
     * @inheritdoc
     */
    public function getUrlKey(): ?string
    {
        return $this->getData(self::URL_KEY);
    }

    /**
     * @inheritdoc
     */
    public function setUrlKey(?string $urlKey): static
    {
        return $this->setData(self::URL_KEY, $urlKey);
    }

    /**
     * @inheritdoc
     */
    public function getCreatedAt(): ?string
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setCreatedAt(?string $createdAt): static
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }
}
