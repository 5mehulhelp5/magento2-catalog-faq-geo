<?php
/**
 * Magendoo Faq Question Interface
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
 * FAQ Question Interface
 *
 * Extends the storefront-safe PublicQuestionInterface with the moderation and
 * submitter fields (status, visibility, sender name/email, customer id) that
 * are only serialised on ACL-protected routes.
 *
 * @api
 */
interface QuestionInterface extends PublicQuestionInterface, ExtensibleDataInterface
{
    /** Status constants */
    public const STATUS_PENDING = 'pending';
    public const STATUS_ANSWERED = 'answered';
    public const STATUS_REJECTED = 'rejected';

    /** Visibility constants */
    public const VISIBILITY_NONE = 'none';
    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_LOGGED_IN = 'logged_in';

    /** Constants for field names */
    public const QUESTION_ID = 'question_id';
    public const TITLE = 'title';
    public const URL_KEY = 'url_key';
    public const SHORT_ANSWER = 'short_answer';
    public const FULL_ANSWER = 'full_answer';
    public const STATUS = 'status';
    public const VISIBILITY = 'visibility';
    public const POSITION = 'position';
    public const IS_SHOW_FULL_ANSWER = 'is_show_full_answer';
    public const SENDER_NAME = 'sender_name';
    public const SENDER_EMAIL = 'sender_email';
    public const CUSTOMER_ID = 'customer_id';
    public const CONSENT_GIVEN_AT = 'consent_given_at';
    public const CONSENT_TEXT = 'consent_text';
    public const POSITIVE_RATING = 'positive_rating';
    public const NEGATIVE_RATING = 'negative_rating';
    public const AVERAGE_RATING = 'average_rating';
    public const VIEW_COUNT = 'view_count';
    public const META_TITLE = 'meta_title';
    public const META_DESCRIPTION = 'meta_description';
    public const NOINDEX = 'noindex';
    public const NOFOLLOW = 'nofollow';
    public const CANONICAL_URL = 'canonical_url';
    public const EXCLUDE_SITEMAP = 'exclude_sitemap';
    public const HIDE_DIRECT_URL = 'hide_direct_url';
    public const CREATED_AT = 'created_at';
    public const UPDATED_AT = 'updated_at';

    /**
     * Set question ID
     *
     * @param int $questionId
     * @return $this
     */
    public function setQuestionId(int $questionId): static;

    /**
     * Set title
     *
     * @param string $title
     * @return $this
     */
    public function setTitle(string $title): static;

    /**
     * Set URL key
     *
     * @param string|null $urlKey
     * @return $this
     */
    public function setUrlKey(?string $urlKey): static;

    /**
     * Set short answer
     *
     * @param string|null $shortAnswer
     * @return $this
     */
    public function setShortAnswer(?string $shortAnswer): static;

    /**
     * Set full answer
     *
     * @param string|null $fullAnswer
     * @return $this
     */
    public function setFullAnswer(?string $fullAnswer): static;

    /**
     * Get status
     *
     * @return string
     */
    public function getStatus(): string;

    /**
     * Set status
     *
     * @param string $status
     * @return $this
     */
    public function setStatus(string $status): static;

    /**
     * Get visibility
     *
     * @return string
     */
    public function getVisibility(): string;

    /**
     * Set visibility
     *
     * @param string $visibility
     * @return $this
     */
    public function setVisibility(string $visibility): static;

    /**
     * Set position
     *
     * @param int $position
     * @return $this
     */
    public function setPosition(int $position): static;

    /**
     * Set is show full answer
     *
     * @param bool $isShowFullAnswer
     * @return $this
     */
    public function setIsShowFullAnswer(bool $isShowFullAnswer): static;

    /**
     * Get sender name
     *
     * @return string|null
     */
    public function getSenderName(): ?string;

    /**
     * Set sender name
     *
     * @param string|null $senderName
     * @return $this
     */
    public function setSenderName(?string $senderName): static;

    /**
     * Get sender email
     *
     * @return string|null
     */
    public function getSenderEmail(): ?string;

    /**
     * Set sender email
     *
     * @param string|null $senderEmail
     * @return $this
     */
    public function setSenderEmail(?string $senderEmail): static;

    /**
     * Get customer ID
     *
     * @return int|null
     */
    public function getCustomerId(): ?int;

    /**
     * Set customer ID
     *
     * @param int|null $customerId
     * @return $this
     */
    public function setCustomerId(?int $customerId): static;

    /**
     * Get the UTC timestamp the GDPR consent was given, null when none was recorded
     *
     * @return string|null
     */
    public function getConsentGivenAt(): ?string;

    /**
     * Set the UTC timestamp the GDPR consent was given
     *
     * @param string|null $consentGivenAt
     * @return $this
     */
    public function setConsentGivenAt(?string $consentGivenAt): static;

    /**
     * Get the snapshot of the consent wording shown at submission time
     *
     * @return string|null
     */
    public function getConsentText(): ?string;

    /**
     * Set the snapshot of the consent wording shown at submission time
     *
     * @param string|null $consentText
     * @return $this
     */
    public function setConsentText(?string $consentText): static;

    /**
     * Set positive rating
     *
     * @param int $positiveRating
     * @return $this
     */
    public function setPositiveRating(int $positiveRating): static;

    /**
     * Set negative rating
     *
     * @param int $negativeRating
     * @return $this
     */
    public function setNegativeRating(int $negativeRating): static;

    /**
     * Set average rating
     *
     * @param float $averageRating
     * @return $this
     */
    public function setAverageRating(float $averageRating): static;

    /**
     * Set view count
     *
     * @param int $viewCount
     * @return $this
     */
    public function setViewCount(int $viewCount): static;

    /**
     * Set meta title
     *
     * @param string|null $metaTitle
     * @return $this
     */
    public function setMetaTitle(?string $metaTitle): static;

    /**
     * Set meta description
     *
     * @param string|null $metaDescription
     * @return $this
     */
    public function setMetaDescription(?string $metaDescription): static;

    /**
     * Set noindex
     *
     * @param bool $noindex
     * @return $this
     */
    public function setNoindex(bool $noindex): static;

    /**
     * Set nofollow
     *
     * @param bool $nofollow
     * @return $this
     */
    public function setNofollow(bool $nofollow): static;

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
     * Get hide direct URL
     *
     * @return bool
     */
    public function getHideDirectUrl(): bool;

    /**
     * Set hide direct URL
     *
     * @param bool $hideDirectUrl
     * @return $this
     */
    public function setHideDirectUrl(bool $hideDirectUrl): static;

    /**
     * Set created at
     *
     * @param string|null $createdAt
     * @return $this
     */
    public function setCreatedAt(?string $createdAt): static;

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
     * @return \Magendoo\Faq\Api\Data\QuestionExtensionInterface|null
     */
    public function getExtensionAttributes(): ?\Magendoo\Faq\Api\Data\QuestionExtensionInterface;

    /**
     * Set extension attributes
     *
     * @param \Magendoo\Faq\Api\Data\QuestionExtensionInterface $extensionAttributes
     * @return $this
     */
    public function setExtensionAttributes(
        \Magendoo\Faq\Api\Data\QuestionExtensionInterface $extensionAttributes
    ): static;
}
