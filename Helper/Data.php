<?php
/**
 * Magendoo Faq Helper Data
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * FAQ Helper
 */
class Data extends AbstractHelper
{
    /** General config paths */
    private const XML_PATH_ENABLED = 'magendoo_faq/general/enabled';
    private const XML_PATH_TITLE = 'magendoo_faq/general/title';
    private const XML_PATH_URL_PREFIX = 'magendoo_faq/general/url_prefix';
    private const XML_PATH_ALLOW_GUEST_QUESTIONS = 'magendoo_faq/general/allow_guest_questions';

    /** Navigation config paths */
    private const XML_PATH_SHOW_BREADCRUMBS = 'magendoo_faq/navigation/show_breadcrumbs';
    private const XML_PATH_SHOW_SEARCH_BOX = 'magendoo_faq/navigation/show_search_box';
    private const XML_PATH_SORT_CATEGORIES_BY = 'magendoo_faq/navigation/sort_categories_by';
    private const XML_PATH_SORT_QUESTIONS_BY = 'magendoo_faq/navigation/sort_questions_by';
    private const XML_PATH_ANSWER_LENGTH_LIMIT = 'magendoo_faq/navigation/answer_length_limit';
    private const XML_PATH_SHORT_ANSWER_BEHAVIOR = 'magendoo_faq/navigation/short_answer_behavior';
    private const XML_PATH_QUESTIONS_PER_CATEGORY_PAGE = 'magendoo_faq/navigation/questions_per_category_page';
    private const XML_PATH_QUESTIONS_PER_SEARCH_PAGE = 'magendoo_faq/navigation/questions_per_search_page';
    private const XML_PATH_NO_RESULTS_TEXT = 'magendoo_faq/navigation/no_results_text';
    private const XML_PATH_TAGS_LIMIT = 'magendoo_faq/navigation/tags_limit';

    /** Product page config paths */
    private const XML_PATH_PRODUCT_ENABLED = 'magendoo_faq/product_page/enabled';
    private const XML_PATH_PRODUCT_TAB_NAME = 'magendoo_faq/product_page/tab_name';
    private const XML_PATH_PRODUCT_TAB_POSITION = 'magendoo_faq/product_page/tab_position';
    private const XML_PATH_PRODUCT_SHOW_ASK_BUTTON = 'magendoo_faq/product_page/show_ask_button';
    private const XML_PATH_PRODUCT_QUESTIONS_LIMIT = 'magendoo_faq/product_page/questions_limit';

    /** Rating config paths */
    private const XML_PATH_RATING_ENABLED = 'magendoo_faq/rating/enabled';
    private const XML_PATH_RATING_TYPE = 'magendoo_faq/rating/type';
    private const XML_PATH_RATING_ALLOW_GUEST = 'magendoo_faq/rating/allow_guest_rating';

    /** Admin notifications config paths */
    private const XML_PATH_ADMIN_NOTIFICATIONS_ENABLED = 'magendoo_faq/admin_notifications/enabled';

    /** Social config paths */
    private const XML_PATH_SOCIAL_ENABLED = 'magendoo_faq/social/enabled';
    private const XML_PATH_SOCIAL_NETWORKS = 'magendoo_faq/social/networks';

    /** SEO config paths */
    private const XML_PATH_URL_SUFFIX_ENABLED = 'magendoo_faq/seo/url_suffix_enabled';
    private const XML_PATH_URL_SUFFIX = 'magendoo_faq/seo/url_suffix';
    private const XML_PATH_STRUCTURED_DATA_ENABLED = 'magendoo_faq/seo/structured_data_enabled';
    private const XML_PATH_ROBOTS_SEARCH_RESULTS = 'magendoo_faq/seo/robots_search_results';

    /** GDPR config paths */
    private const XML_PATH_GDPR_ENABLED = 'magendoo_faq/gdpr/enabled';
    private const XML_PATH_GDPR_TEXT = 'magendoo_faq/gdpr/consent_text';

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager
    ) {
        $this->storeManager = $storeManager;
        parent::__construct($context);
    }

    /**
     * Check if FAQ module is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get FAQ title
     *
     * @param int|null $storeId
     * @return string
     */
    public function getTitle(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_TITLE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get URL prefix
     *
     * @param int|null $storeId
     * @return string
     */
    public function getUrlPrefix(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_URL_PREFIX,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if breadcrumbs should be shown
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isShowBreadcrumbs(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_BREADCRUMBS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if search box is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isSearchBoxEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SHOW_SEARCH_BOX,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get sort categories by setting
     *
     * @param int|null $storeId
     * @return string
     */
    public function getSortCategoriesBy(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_SORT_CATEGORIES_BY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get sort questions by setting
     *
     * @param int|null $storeId
     * @return string
     */
    public function getSortQuestionsBy(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_SORT_QUESTIONS_BY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get answer length limit
     *
     * @param int|null $storeId
     * @return int
     */
    public function getAnswerLengthLimit(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_ANSWER_LENGTH_LIMIT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get short answer behavior
     *
     * @param int|null $storeId
     * @return string
     */
    public function getShortAnswerBehavior(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_SHORT_ANSWER_BEHAVIOR,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get questions per category page
     *
     * @param int|null $storeId
     * @return int
     */
    public function getQuestionsPerCategoryPage(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_QUESTIONS_PER_CATEGORY_PAGE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get questions per search page
     *
     * @param int|null $storeId
     * @return int
     */
    public function getQuestionsPerSearchPage(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_QUESTIONS_PER_SEARCH_PAGE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get no results text
     *
     * @param int|null $storeId
     * @return string
     */
    public function getNoResultsText(?int $storeId = null): string
    {
        $text = $this->scopeConfig->getValue(
            self::XML_PATH_NO_RESULTS_TEXT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $text ? (string) $text : (string) __('No results found for your search query.');
    }

    /**
     * Check if rating is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isRatingEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_RATING_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get rating type
     *
     * @param int|null $storeId
     * @return string
     */
    public function getRatingType(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_RATING_TYPE,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check whether guests (not-logged-in visitors) are allowed to rate questions.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isGuestRatingAllowed(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_RATING_ALLOW_GUEST,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get the maximum number of tags shown in the tag cloud (0 = no limit).
     *
     * @param int|null $storeId
     * @return int
     */
    public function getTagsLimit(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_TAGS_LIMIT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check whether admin notifications for new questions are enabled.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isAdminNotificationEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ADMIN_NOTIFICATIONS_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if product page FAQ is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isProductPageEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PRODUCT_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get product tab name
     *
     * @param int|null $storeId
     * @return string
     */
    public function getProductTabName(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_PRODUCT_TAB_NAME,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if ask question button on product page is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isProductAskQuestionEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_PRODUCT_SHOW_ASK_BUTTON,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get product questions limit
     *
     * @param int|null $storeId
     * @return int
     */
    public function getProductQuestionsLimit(?int $storeId = null): int
    {
        return (int) $this->scopeConfig->getValue(
            self::XML_PATH_PRODUCT_QUESTIONS_LIMIT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if URL suffix is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isUrlSuffixEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_URL_SUFFIX_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get URL suffix
     *
     * @param int|null $storeId
     * @return string
     */
    public function getUrlSuffix(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_URL_SUFFIX,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check if structured data is enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isStructuredDataEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_STRUCTURED_DATA_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Build full FAQ URL path for a given url_key
     *
     * @param string $urlKey
     * @param int|null $storeId
     * @return string
     */
    public function buildUrlPath(string $urlKey, ?int $storeId = null): string
    {
        $prefix = $this->getUrlPrefix($storeId);
        $path = $prefix . '/' . $urlKey;

        if ($this->isUrlSuffixEnabled($storeId)) {
            $path .= $this->getUrlSuffix($storeId);
        }

        return $path;
    }

    /**
     * Alias for isShowBreadcrumbs() for controller compatibility.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isBreadcrumbsEnabled(?int $storeId = null): bool
    {
        return $this->isShowBreadcrumbs($storeId);
    }

    /**
     * Get the current store base URL.
     *
     * @return string
     */
    public function getBaseUrl(): string
    {
        return $this->storeManager->getStore()->getBaseUrl();
    }

    /**
     * Get the FAQ landing page URL.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getFaqUrl(?int $storeId = null): string
    {
        return $this->getBaseUrl() . $this->getUrlPrefix($storeId);
    }

    /**
     * Get the robots meta tag value for the FAQ search results page.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getRobotsSearchResults(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_ROBOTS_SEARCH_RESULTS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check whether unregistered customers are allowed to submit questions.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isGuestQuestionAllowed(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_ALLOW_GUEST_QUESTIONS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check whether the GDPR consent is enabled.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isGdprEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_GDPR_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get GDPR consent text.
     *
     * @param int|null $storeId
     * @return string
     */
    public function getGdprText(?int $storeId = null): string
    {
        return (string) $this->scopeConfig->getValue(
            self::XML_PATH_GDPR_TEXT,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Check whether social share buttons are enabled.
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isSocialEnabled(?int $storeId = null): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_SOCIAL_ENABLED,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }

    /**
     * Get the list of enabled social networks.
     *
     * @param int|null $storeId
     * @return string[]
     */
    public function getSocialNetworks(?int $storeId = null): array
    {
        $value = (string) $this->scopeConfig->getValue(
            self::XML_PATH_SOCIAL_NETWORKS,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        return $value === '' ? [] : array_filter(explode(',', $value));
    }
}
