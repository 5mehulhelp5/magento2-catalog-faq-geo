<?php
/**
 * Magendoo Faq Question Model
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
use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Model\ResourceModel\Question as ResourceQuestion;

/**
 * FAQ Question Model
 */
class Question extends AbstractExtensibleModel implements QuestionInterface, IdentityInterface
{
    /**
     * Question cache tag
     */
    public const CACHE_TAG = 'magendoo_faq_question';

    /**
     * @var string
     */
    protected $_eventPrefix = 'magendoo_faq_question';

    /**
     * @var string
     */
    protected $_eventObject = 'faq_question';

    /**
     * @inheritdoc
     */
    protected function _construct(): void
    {
        $this->_init(ResourceQuestion::class);
    }

    /**
     * @inheritdoc
     */
    public function getIdentities(): array
    {
        $identities = [self::CACHE_TAG . '_' . $this->getId()];

        // Listing pages are tagged with the bare CACHE_TAG so that a NEW entity, a deleted one,
        // or one whose status, visibility or position changed shows up (or disappears) without waiting for the
        // page cache to expire. Without this, only pages already rendering this exact entity
        // would ever be purged, and a newly published entity would stay invisible.
        // Mirrors Magento\Catalog\Model\Category::getIdentities().
        if ($this->isObjectNew() || $this->isDeleted()) {
            $identities[] = self::CACHE_TAG;
        } else {
            foreach (['status', 'visibility', 'position'] as $field) {
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
    public function getQuestionId(): ?int
    {
        $id = $this->getData(self::QUESTION_ID);
        return $id ? (int) $id : null;
    }

    /**
     * @inheritdoc
     */
    public function setQuestionId(int $questionId): static
    {
        return $this->setData(self::QUESTION_ID, $questionId);
    }

    /**
     * @inheritdoc
     */
    public function getTitle(): string
    {
        return (string) $this->getData(self::TITLE);
    }

    /**
     * @inheritdoc
     */
    public function setTitle(string $title): static
    {
        return $this->setData(self::TITLE, $title);
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
    public function getShortAnswer(): ?string
    {
        return $this->getData(self::SHORT_ANSWER);
    }

    /**
     * @inheritdoc
     */
    public function setShortAnswer(?string $shortAnswer): static
    {
        return $this->setData(self::SHORT_ANSWER, $shortAnswer);
    }

    /**
     * @inheritdoc
     */
    public function getFullAnswer(): ?string
    {
        return $this->getData(self::FULL_ANSWER);
    }

    /**
     * @inheritdoc
     */
    public function setFullAnswer(?string $fullAnswer): static
    {
        return $this->setData(self::FULL_ANSWER, $fullAnswer);
    }

    /**
     * @inheritdoc
     */
    public function getStatus(): string
    {
        $status = $this->getData(self::STATUS);
        return $status ?: self::STATUS_PENDING;
    }

    /**
     * @inheritdoc
     */
    public function setStatus(string $status): static
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritdoc
     */
    public function getVisibility(): string
    {
        $visibility = $this->getData(self::VISIBILITY);
        return $visibility ?: self::VISIBILITY_NONE;
    }

    /**
     * @inheritdoc
     */
    public function setVisibility(string $visibility): static
    {
        return $this->setData(self::VISIBILITY, $visibility);
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
    public function getIsShowFullAnswer(): bool
    {
        return (bool) $this->getData(self::IS_SHOW_FULL_ANSWER);
    }

    /**
     * @inheritdoc
     */
    public function setIsShowFullAnswer(bool $isShowFullAnswer): static
    {
        return $this->setData(self::IS_SHOW_FULL_ANSWER, $isShowFullAnswer ? 1 : 0);
    }

    /**
     * @inheritdoc
     */
    public function getSenderName(): ?string
    {
        return $this->getData(self::SENDER_NAME);
    }

    /**
     * @inheritdoc
     */
    public function setSenderName(?string $senderName): static
    {
        return $this->setData(self::SENDER_NAME, $senderName);
    }

    /**
     * @inheritdoc
     */
    public function getSenderEmail(): ?string
    {
        return $this->getData(self::SENDER_EMAIL);
    }

    /**
     * @inheritdoc
     */
    public function setSenderEmail(?string $senderEmail): static
    {
        return $this->setData(self::SENDER_EMAIL, $senderEmail);
    }

    /**
     * @inheritdoc
     */
    public function getCustomerId(): ?int
    {
        $id = $this->getData(self::CUSTOMER_ID);
        return $id ? (int) $id : null;
    }

    /**
     * @inheritdoc
     */
    public function setCustomerId(?int $customerId): static
    {
        return $this->setData(self::CUSTOMER_ID, $customerId);
    }

    /**
     * @inheritdoc
     */
    public function getConsentGivenAt(): ?string
    {
        return $this->getData(self::CONSENT_GIVEN_AT);
    }

    /**
     * @inheritdoc
     */
    public function setConsentGivenAt(?string $consentGivenAt): static
    {
        return $this->setData(self::CONSENT_GIVEN_AT, $consentGivenAt);
    }

    /**
     * @inheritdoc
     */
    public function getConsentText(): ?string
    {
        return $this->getData(self::CONSENT_TEXT);
    }

    /**
     * @inheritdoc
     */
    public function setConsentText(?string $consentText): static
    {
        return $this->setData(self::CONSENT_TEXT, $consentText);
    }

    /**
     * @inheritdoc
     */
    public function getPositiveRating(): int
    {
        return (int) $this->getData(self::POSITIVE_RATING);
    }

    /**
     * @inheritdoc
     */
    public function setPositiveRating(int $positiveRating): static
    {
        return $this->setData(self::POSITIVE_RATING, $positiveRating);
    }

    /**
     * @inheritdoc
     */
    public function getNegativeRating(): int
    {
        return (int) $this->getData(self::NEGATIVE_RATING);
    }

    /**
     * @inheritdoc
     */
    public function setNegativeRating(int $negativeRating): static
    {
        return $this->setData(self::NEGATIVE_RATING, $negativeRating);
    }

    /**
     * @inheritdoc
     */
    public function getAverageRating(): float
    {
        return (float) $this->getData(self::AVERAGE_RATING);
    }

    /**
     * @inheritdoc
     */
    public function setAverageRating(float $averageRating): static
    {
        return $this->setData(self::AVERAGE_RATING, $averageRating);
    }

    /**
     * @inheritdoc
     */
    public function getViewCount(): int
    {
        return (int) $this->getData(self::VIEW_COUNT);
    }

    /**
     * @inheritdoc
     */
    public function setViewCount(int $viewCount): static
    {
        return $this->setData(self::VIEW_COUNT, $viewCount);
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
    public function getHideDirectUrl(): bool
    {
        return (bool) $this->getData(self::HIDE_DIRECT_URL);
    }

    /**
     * @inheritdoc
     */
    public function setHideDirectUrl(bool $hideDirectUrl): static
    {
        return $this->setData(self::HIDE_DIRECT_URL, $hideDirectUrl ? 1 : 0);
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
    public function getExtensionAttributes(): ?\Magendoo\Faq\Api\Data\QuestionExtensionInterface
    {
        return $this->_getExtensionAttributes();
    }

    /**
     * @inheritdoc
     */
    public function setExtensionAttributes(\Magendoo\Faq\Api\Data\QuestionExtensionInterface $extensionAttributes): static
    {
        return $this->_setExtensionAttributes($extensionAttributes);
    }
}
