<?php
/**
 * Magendoo Faq Tag Interface
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
 * FAQ Tag Interface
 *
 * @api
 */
interface TagInterface extends ExtensibleDataInterface
{
    /** Constants for field names */
    public const TAG_ID = 'tag_id';
    public const NAME = 'name';
    public const URL_KEY = 'url_key';
    public const CREATED_AT = 'created_at';

    /**
     * Get tag ID
     *
     * @return int|null
     */
    public function getTagId(): ?int;

    /**
     * Set tag ID
     *
     * @param int $tagId
     * @return $this
     */
    public function setTagId(int $tagId): static;

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
}
