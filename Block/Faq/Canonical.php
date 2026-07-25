<?php
/**
 * Magendoo Faq Canonical Link Block
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Block\Faq;

use Magendoo\Faq\Api\CategoryRepositoryInterface;
use Magendoo\Faq\Api\QuestionRepositoryInterface;
use Magendoo\Faq\Api\TagRepositoryInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;

/**
 * Registers the `<link rel="canonical">` page asset for FAQ pages.
 *
 * FAQ questions are reachable at several addresses (flat rewrite URL, virtual
 * category/question path, id-based URL); the canonical link names the flat pretty
 * URL as the single indexable one. The per-entity `canonical_url` field overrides
 * the computed default. Gated by `magendoo_faq/seo/use_canonical` (layout ifconfig
 * plus a defensive check here) and registered the same way core does it —
 * see \Magento\Catalog\Block\Category\View::_prepareLayout().
 *
 * The block intentionally has no template: its whole output is the page asset.
 */
class Canonical extends Template
{
    /**
     * Canonical tag config path (see etc/adminhtml/system.xml)
     */
    private const XML_PATH_USE_CANONICAL = 'magendoo_faq/seo/use_canonical';

    /**
     * @var FaqHelper
     */
    private FaqHelper $helper;

    /**
     * @var CategoryRepositoryInterface
     */
    private CategoryRepositoryInterface $categoryRepository;

    /**
     * @var QuestionRepositoryInterface
     */
    private QuestionRepositoryInterface $questionRepository;

    /**
     * @var TagRepositoryInterface
     */
    private TagRepositoryInterface $tagRepository;

    /**
     * @param Context $context
     * @param FaqHelper $helper
     * @param CategoryRepositoryInterface $categoryRepository
     * @param QuestionRepositoryInterface $questionRepository
     * @param TagRepositoryInterface $tagRepository
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        FaqHelper $helper,
        CategoryRepositoryInterface $categoryRepository,
        QuestionRepositoryInterface $questionRepository,
        TagRepositoryInterface $tagRepository,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->categoryRepository = $categoryRepository;
        $this->questionRepository = $questionRepository;
        $this->tagRepository = $tagRepository;
        parent::__construct($context, $data);
    }

    /**
     * Register the canonical link as a remote page asset
     *
     * @return $this
     */
    protected function _prepareLayout()
    {
        parent::_prepareLayout();

        $canonicalUrl = $this->getCanonicalUrl();
        if ($canonicalUrl !== null) {
            $this->pageConfig->addRemotePageAsset(
                $canonicalUrl,
                'canonical',
                ['attributes' => ['rel' => 'canonical']]
            );
        }

        return $this;
    }

    /**
     * Resolve the canonical URL for the current FAQ page
     *
     * @return string|null
     */
    public function getCanonicalUrl(): ?string
    {
        if (!$this->isCanonicalEnabled() || !$this->helper->isEnabled()) {
            return null;
        }

        $request = $this->getRequest();
        $fullActionName = $request instanceof \Magento\Framework\App\Request\Http
            ? $request->getFullActionName()
            : '';

        switch ($fullActionName) {
            case 'faq_index_index':
                $path = $this->helper->getUrlPrefix();
                return $path ? $this->helper->getBaseUrl() . $path : null;
            case 'faq_category_view':
                return $this->getEntityCanonicalUrl('category');
            case 'faq_question_view':
                return $this->getEntityCanonicalUrl('question');
            case 'faq_tag_view':
                return $this->getTagCanonicalUrl();
            default:
                return null;
        }
    }

    /**
     * Canonical URL for a category or question page
     *
     * The per-entity canonical_url field wins; the default is the entity's own
     * pretty URL (the same request path its URL rewrite is generated with).
     *
     * @param string $entityType
     * @return string|null
     */
    private function getEntityCanonicalUrl(string $entityType): ?string
    {
        $entityId = (int) $this->getRequest()->getParam('id');
        if (!$entityId) {
            return null;
        }

        try {
            $entity = $entityType === 'category'
                ? $this->categoryRepository->getById($entityId)
                : $this->questionRepository->getById($entityId);
        } catch (NoSuchEntityException $e) {
            return null;
        }

        $override = trim((string) $entity->getCanonicalUrl());
        if ($override !== '') {
            return $this->absolutize($override);
        }

        $urlKey = $entity->getUrlKey();
        if (!$urlKey) {
            return null;
        }

        return $this->helper->getBaseUrl() . $this->helper->buildUrlPath($urlKey);
    }

    /**
     * Canonical URL for a tag page
     *
     * @return string|null
     */
    private function getTagCanonicalUrl(): ?string
    {
        $tagId = (int) $this->getRequest()->getParam('id');
        if (!$tagId) {
            return null;
        }

        try {
            $tag = $this->tagRepository->getById($tagId);
        } catch (NoSuchEntityException $e) {
            return null;
        }

        $urlKey = $tag->getUrlKey();
        if (!$urlKey) {
            return null;
        }

        return $this->helper->getBaseUrl() . $this->helper->buildUrlPath('tag/' . $urlKey);
    }

    /**
     * Turn a merchant-entered canonical override into an absolute URL
     *
     * @param string $url Absolute URL or store-relative path
     * @return string
     */
    private function absolutize(string $url): string
    {
        if (preg_match('#^https?://#i', $url)) {
            return $url;
        }

        return $this->helper->getBaseUrl() . ltrim($url, '/');
    }

    /**
     * Check whether canonical tags are enabled
     *
     * @return bool
     */
    private function isCanonicalEnabled(): bool
    {
        return $this->_scopeConfig->isSetFlag(
            self::XML_PATH_USE_CANONICAL,
            ScopeInterface::SCOPE_STORE
        );
    }
}
