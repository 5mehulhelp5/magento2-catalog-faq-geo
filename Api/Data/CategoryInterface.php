<?php
/**
 * Magendoo Faq Category Interface
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Api\Data;

use Magento\Framework\Api\ExtensibleDataInterface;

/**
 * FAQ Category Interface
 *
 * @api
 */
interface CategoryInterface extends ExtensibleDataInterface
{
    /** Status constants */
    public const STATUS_ENABLED = 1;
    public const STATUS_DISABLED = 0;

    /** Constants for field names */
    public const CATEGORY_ID = 'category_id';
    public const NAME = 'name';
    public const PAGE_TITLE = 'page_title';
    public const URL_KEY = 'url_key';
    public const DESCRIPTION = 'description';
    public const ICON = 'icon';
    public const POSITION = 'position';
    public const STATUS = 'status';
    public const META_TITLE = 'meta_title';
    public const META_DESCRIPTION = 'meta_description';
    public const NOINDEX = 'noindex';
    public const NOFOLLOW = 'nofollow';
    public const CANONICAL_URL = 'canonical_url';
    public const EXCLUDE_SITEMAP = 'exclude_sitemap';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * Get category ID
     *
     * @return int|null
     */
    public function getCategoryId(): ?int;

    /**
     * Set category ID
     *
     * @param int $categoryId
     * @return $this
     */
    public function setCategoryId(int $categoryId): static;

    /**
     * Get name
     *
     * @return string
     */
    public function getName(): string;

    /**
     * Set name
     *
     * @param string $name
     * @return $this
     */
    public function setName(string $name): static;

    /**
     * Get page title
     *
     * @return string|null
     */
    public function getPageTitle(): ?string;

    /**
     * Set page title
     *
     * @param string|null $pageTitle
     * @return $this
     */
    public function setPageTitle(?string $pageTitle): static;

    /**
     * Get URL key
     *
     * @return string|null
     */
    public function getUrlKey(): ?string;

    /**
     * Set URL key
     *
     * @param string|null $urlKey
     * @return $this
     */
    public function setUrlKey(?string $urlKey): static;

    /**
     * Get description
     *
     * @return string|null
     */
    public function getDescription(): ?string;

    /**
     * Set description
     *
     * @param string|null $description
     * @return $this
     */
    public function setDescription(?string $description): static;

    /**
     * Get icon
     *
     * @return string|null
     */
    public function getIcon(): ?string;

    /**
     * Set icon
     *
     * @param string|null $icon
     * @return $this
     */
    public function setIcon(?string $icon): static;

    /**
     * Get position
     *
     * @return int
     */
    public function getPosition(): int;

    /**
     * Set position
     *
     * @param int $position
     * @return $this
     */
    public function setPosition(int $position): static;

    /**
     * Get status
     *
     * @return int
     */
    public function getStatus(): int;

    /**
     * Set status
     *
     * @param int $status
     * @return $this
     */
    public function setStatus(int $status): static;

    /**
     * Get meta title
     *
     * @return string|null
     */
    public function getMetaTitle(): ?string;

    /**
     * Set meta title
     *
     * @param string|null $metaTitle
     * @return $this
     */
    public function setMetaTitle(?string $metaTitle): static;

    /**
     * Get meta description
     *
     * @return string|null
     */
    public function getMetaDescription(): ?string;

    /**
     * Set meta description
     *
     * @param string|null $metaDescription
     * @return $this
     */
    public function setMetaDescription(?string $metaDescription): static;

    /**
     * Get noindex
     *
     * @return bool
     */
    public function getNoindex(): bool;

    /**
     * Set noindex
     *
     * @param bool $noindex
     * @return $this
     */
    public function setNoindex(bool $noindex): static;

    /**
     * Get nofollow
     *
     * @return bool
     */
    public function getNofollow(): bool;

    /**
     * Set nofollow
     *
     * @param bool $nofollow
     * @return $this
     */
    public function setNofollow(bool $nofollow): static;

    /**
     * Get canonical URL
     *
     * @return string|null
     */
    public function getCanonicalUrl(): ?string;

    /**
     * Set canonical URL
     *
     * @param string|null $canonicalUrl
     * @return $this
     */
    public function setCanonicalUrl(?string $canonicalUrl): static;

    /**
     * Get exclude from sitemap
     *
     * @return bool
     */
    public function getExcludeSitemap(): bool;

    /**
     * Set exclude from sitemap
     *
     * @param bool $excludeSitemap
     * @return $this
     */
    public function setExcludeSitemap(bool $excludeSitemap): static;

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set created at
     *
     * @param string|null $createdAt
     * @return $this
     */
    public function setCreatedAt(?string $createdAt): static;

    /**
     * Get updated at
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;

    /**
     * Set updated at
     *
     * @param string|null $updatedAt
     * @return $this
     */
    public function setUpdatedAt(?string $updatedAt): static;

    /**
     * Get extension attributes
     *
     * @return \Magendoo\Faq\Api\Data\CategoryExtensionInterface|null
     */
    public function getExtensionAttributes(): ?\Magendoo\Faq\Api\Data\CategoryExtensionInterface;

    /**
     * Set extension attributes
     *
     * @param \Magendoo\Faq\Api\Data\CategoryExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(\Magendoo\Faq\Api\Data\CategoryExtensionInterface $extensionAttributes): static;
}
