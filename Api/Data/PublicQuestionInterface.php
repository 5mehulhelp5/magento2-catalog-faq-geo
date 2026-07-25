<?php
/**
 * Magendoo Faq Public Question Interface
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Api\Data;

/**
 * Storefront-safe projection of an FAQ question.
 *
 * Used as the declared return type of the anonymous web API routes: the web API
 * output processor serialises the getters of the declared type only, so fields
 * that must never leave the store unauthenticated (sender name, sender email,
 * customer id, moderation status and visibility) are not part of this contract.
 * The full record stays available through QuestionInterface on the
 * ACL-protected admin routes.
 *
 * @api
 */
interface PublicQuestionInterface
{
    /**
     * Get question ID
     *
     * @return int|null
     */
    public function getQuestionId(): ?int;

    /**
     * Get title
     *
     * @return string
     */
    public function getTitle(): string;

    /**
     * Get URL key
     *
     * @return string|null
     */
    public function getUrlKey(): ?string;

    /**
     * Get short answer
     *
     * @return string|null
     */
    public function getShortAnswer(): ?string;

    /**
     * Get full answer
     *
     * @return string|null
     */
    public function getFullAnswer(): ?string;

    /**
     * Get position
     *
     * @return int
     */
    public function getPosition(): int;

    /**
     * Get is show full answer
     *
     * @return bool
     */
    public function getIsShowFullAnswer(): bool;

    /**
     * Get positive rating
     *
     * @return int
     */
    public function getPositiveRating(): int;

    /**
     * Get negative rating
     *
     * @return int
     */
    public function getNegativeRating(): int;

    /**
     * Get average rating
     *
     * @return float
     */
    public function getAverageRating(): float;

    /**
     * Get view count
     *
     * @return int
     */
    public function getViewCount(): int;

    /**
     * Get meta title
     *
     * @return string|null
     */
    public function getMetaTitle(): ?string;

    /**
     * Get meta description
     *
     * @return string|null
     */
    public function getMetaDescription(): ?string;

    /**
     * Get noindex
     *
     * @return bool
     */
    public function getNoindex(): bool;

    /**
     * Get nofollow
     *
     * @return bool
     */
    public function getNofollow(): bool;

    /**
     * Get canonical URL
     *
     * @return string|null
     */
    public function getCanonicalUrl(): ?string;

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Get updated at
     *
     * @return string|null
     */
    public function getUpdatedAt(): ?string;
}
