<?php
/**
 * Magendoo Faq Category Model
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
use Magendoo\Faq\Api\Data\CategoryInterface;
use Magendoo\Faq\Model\ResourceModel\Category as ResourceCategory;

/**
 * FAQ Category Model
 */
class Category extends AbstractExtensibleModel implements CategoryInterface, IdentityInterface
{
    /**
     * Category cache tag
     */
    public const CACHE_TAG = 'magendoo_faq_category';

    /**
     * @var string
     */
    protected $_eventPrefix = 'magendoo_faq_category';

    /**
     * @var string
     */
    protected $_eventObject = 'faq_category';

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(ResourceCategory::class);
    }

    /**
     * @inheritdoc
     */
    public function getIdentities(): array
    {
        $identities = [self::CACHE_TAG . '_' . $this->getId()];

        // Listing pages are tagged with the bare CACHE_TAG so that a NEW entity, a deleted one,
        // or one whose status or position changed shows up (or disappears) without waiting for the
        // page cache to expire. Without this, only pages already rendering this exact entity
        // would ever be purged, and a newly published entity would stay invisible.
        // Mirrors Magento\Catalog\Model\Category::getIdentities().
        if ($this->isObjectNew() || $this->isDeleted()) {
            $identities[] = self::CACHE_TAG;
        } else {
            foreach (['status', 'position'] as $field) {
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
    public function getCategoryId(): ?int
    {
        $id = $this->getData(self::CATEGORY_ID);
        return $id ? (int) $id : null;
    }

    /**
     * @inheritdoc
     */
    public function setCategoryId(int $categoryId): static
    {
        return $this->setData(self::CATEGORY_ID, $categoryId);
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
    public function getPageTitle(): ?string
    {
        return $this->getData(self::PAGE_TITLE);
    }

    /**
     * @inheritdoc
     */
    public function setPageTitle(?string $pageTitle): static
    {
        return $this->setData(self::PAGE_TITLE, $pageTitle);
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
    public function getDescription(): ?string
    {
        return $this->getData(self::DESCRIPTION);
    }

    /**
     * @inheritdoc
     */
    public function setDescription(?string $description): static
    {
        return $this->setData(self::DESCRIPTION, $description);
    }

    /**
     * @inheritdoc
     */
    public function getIcon(): ?string
    {
        return $this->getData(self::ICON);
    }

    /**
     * @inheritdoc
     */
    public function setIcon(?string $icon): static
    {
        return $this->setData(self::ICON, $icon);
    }

    /**
     * @inheritdoc
     */
    public function getPosition(): int
    {
        return (int) $this->getData(self::POSITION);
    }

    /**
     * @inheritdoc
     */
    public function setPosition(int $position): static
    {
        return $this->setData(self::POSITION, $position);
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): int
    {
        return (int) $this->getData(self::STATUS);
    }

    /**
     * @inheritdoc
     */
    public function setStatus(int $status): static
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getMetaTitle(): ?string
    {
        return $this->getData(self::META_TITLE);
    }

    /**
     * @inheritdoc
     */
    public function setMetaTitle(?string $metaTitle): static
    {
        return $this->setData(self::META_TITLE, $metaTitle);
    }

    /**
     * @inheritdoc
     */
    public function getMetaDescription(): ?string
    {
        return $this->getData(self::META_DESCRIPTION);
    }

    /**
     * @inheritdoc
     */
    public function setMetaDescription(?string $metaDescription): static
    {
        return $this->setData(self::META_DESCRIPTION, $metaDescription);
    }

    /**
     * @inheritdoc
     */
    public function getNoindex(): bool
    {
        return (bool) $this->getData(self::NOINDEX);
    }

    /**
     * @inheritdoc
     */
    public function setNoindex(bool $noindex): static
    {
        return $this->setData(self::NOINDEX, $noindex ? 1 : 0);
    }

    /**
     * @inheritdoc
     */
    public function getNofollow(): bool
    {
        return (bool) $this->getData(self::NOFOLLOW);
    }

    /**
     * @inheritdoc
     */
    public function setNofollow(bool $nofollow): static
    {
        return $this->setData(self::NOFOLLOW, $nofollow ? 1 : 0);
    }

    /**
     * @inheritdoc
     */
    public function getCanonicalUrl(): ?string
    {
        return $this->getData(self::CANONICAL_URL);
    }

    /**
     * @inheritdoc
     */
    public function setCanonicalUrl(?string $canonicalUrl): static
    {
        return $this->setData(self::CANONICAL_URL, $canonicalUrl);
    }

    /**
     * @inheritdoc
     */
    public function getExcludeSitemap(): bool
    {
        return (bool) $this->getData(self::EXCLUDE_SITEMAP);
    }

    /**
     * @inheritdoc
     */
    public function setExcludeSitemap(bool $excludeSitemap): static
    {
        return $this->setData(self::EXCLUDE_SITEMAP, $excludeSitemap ? 1 : 0);
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

    /**
     * @inheritdoc
     */
    public function getUpdatedAt(): ?string
    {
        return $this->getData(self::UPDATED_AT);
    }

    /**
     * @inheritdoc
     */
    public function setUpdatedAt(?string $updatedAt): static
    {
        return $this->setData(self::UPDATED_AT, $updatedAt);
    }

    /**
     * @inheritdoc
     */
    public function getExtensionAttributes(): ?\Magendoo\Faq\Api\Data\CategoryExtensionInterface
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(\Magendoo\Faq\Api\Data\CategoryExtensionInterface $extensionAttributes): static
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
