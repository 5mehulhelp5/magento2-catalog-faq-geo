<?php
/**
 * Magendoo Faq Frontend Router
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Controller;

use Magento\Framework\App\ActionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Request\Http as HttpRequest;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\HttpInterface as HttpResponseInterface;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\App\RouterInterface;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\ResourceModel\Category as CategoryResource;
use Magendoo\Faq\Model\ResourceModel\Question as QuestionResource;
use Magendoo\Faq\Model\ResourceModel\Tag as TagResource;

/**
 * FAQ custom URL router
 *
 * Every page has exactly one canonical request path; the non-canonical variants
 * (missing/extra URL suffix, trailing slash when so configured) are answered with a
 * 301 to the canonical form instead of a duplicate 200, mirroring
 * Magento\UrlRewrite\Controller\Router::processRedirect().
 */
class Router implements RouterInterface
{
    /**
     * Trailing slash removal config path (see etc/adminhtml/system.xml)
     */
    private const XML_PATH_REMOVE_TRAILING_SLASH = 'magendoo_faq/seo/remove_trailing_slash';

    /**
     * @var ActionFactory
     */
    protected ActionFactory $actionFactory;

    /**
     * @var FaqHelper
     */
    protected FaqHelper $faqHelper;

    /**
     * @var CategoryResource
     */
    protected CategoryResource $categoryResource;

    /**
     * @var QuestionResource
     */
    protected QuestionResource $questionResource;

    /**
     * @var TagResource
     */
    protected TagResource $tagResource;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var ResponseInterface
     */
    protected ResponseInterface $response;

    /**
     * @var UrlInterface
     */
    protected UrlInterface $url;

    /**
     * @var ScopeConfigInterface
     */
    protected ScopeConfigInterface $scopeConfig;

    /**
     * @param ActionFactory $actionFactory
     * @param FaqHelper $faqHelper
     * @param CategoryResource $categoryResource
     * @param QuestionResource $questionResource
     * @param TagResource $tagResource
     * @param StoreManagerInterface $storeManager
     * @param ResponseInterface $response
     * @param UrlInterface $url
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        ActionFactory $actionFactory,
        FaqHelper $faqHelper,
        CategoryResource $categoryResource,
        QuestionResource $questionResource,
        TagResource $tagResource,
        StoreManagerInterface $storeManager,
        ResponseInterface $response,
        UrlInterface $url,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->actionFactory = $actionFactory;
        $this->faqHelper = $faqHelper;
        $this->categoryResource = $categoryResource;
        $this->questionResource = $questionResource;
        $this->tagResource = $tagResource;
        $this->storeManager = $storeManager;
        $this->response = $response;
        $this->url = $url;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * Match the request to FAQ routes
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ActionInterface|null
     */
    public function match(RequestInterface $request): ?\Magento\Framework\App\ActionInterface
    {
        if (!$request instanceof HttpRequest || !$this->faqHelper->isEnabled()) {
            return null;
        }

        $rawPathInfo = (string) $request->getPathInfo();
        $pathInfo = trim($rawPathInfo, '/');
        $urlPrefix = $this->faqHelper->getUrlPrefix();

        if (!$pathInfo || !$urlPrefix || !str_starts_with($pathInfo, $urlPrefix)) {
            return null;
        }

        $suffix = $this->faqHelper->isUrlSuffixEnabled() ? $this->faqHelper->getUrlSuffix() : '';

        // Enforce a real path-segment boundary after the prefix so e.g. "faqs/..." is
        // not claimed by prefix "faq". The one exception is "<prefix><suffix>"
        // ("faq.html"), which would otherwise 200 as a duplicate of the FAQ home page.
        $remainder = substr($pathInfo, strlen($urlPrefix));
        if ($remainder !== '' && !str_starts_with($remainder, '/')) {
            if ($suffix !== '' && $remainder === $suffix) {
                return $this->redirect($request, $urlPrefix);
            }
            return null;
        }

        $hasTrailingSlash = str_ends_with($rawPathInfo, '/');
        $stripSlash = $hasTrailingSlash && $this->isRemoveTrailingSlashEnabled();

        $path = ltrim($remainder, '/');

        // Strip URL suffix if configured
        $hadSuffix = false;
        if ($suffix !== '' && str_ends_with($path, $suffix)) {
            $path = substr($path, 0, -strlen($suffix));
            $hadSuffix = true;
        }

        // FAQ home page — canonical form carries no suffix
        if ($path === '') {
            if ($hadSuffix || $stripSlash) {
                return $this->redirect($request, $urlPrefix);
            }
            $request->setModuleName('faq')
                ->setControllerName('index')
                ->setActionName('index');
            return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
        }

        // Search page — a virtual route like the home page, canonical without suffix
        if ($path === 'search') {
            if ($hadSuffix || $stripSlash) {
                return $this->redirect($request, $urlPrefix . '/search');
            }
            $request->setModuleName('faq')
                ->setControllerName('question')
                ->setActionName('search');
            return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
        }

        // Entity pages carry the suffix when one is configured
        $needsSuffixRedirect = $suffix !== '' && !$hadSuffix;
        $canonicalPath = $urlPrefix . '/' . $path . $suffix;

        $segments = explode('/', $path);

        // Tag page: tag/{url-key}
        if (count($segments) === 2 && $segments[0] === 'tag') {
            $tagUrlKey = $segments[1];
            $tagId = $this->lookupTagId($tagUrlKey);
            if ($tagId) {
                if ($needsSuffixRedirect || $stripSlash) {
                    return $this->redirect($request, $canonicalPath);
                }
                $request->setModuleName('faq')
                    ->setControllerName('tag')
                    ->setActionName('view')
                    ->setParam('id', $tagId);
                return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
            }
        }

        $storeId = (int) $this->storeManager->getStore()->getId();

        // Single segment: category URL key
        if (count($segments) === 1) {
            $categoryUrlKey = $segments[0];
            $categoryId = $this->categoryResource->getByUrlKey($categoryUrlKey, $storeId);
            if ($categoryId) {
                if ($needsSuffixRedirect || $stripSlash) {
                    return $this->redirect($request, $canonicalPath);
                }
                $request->setModuleName('faq')
                    ->setControllerName('category')
                    ->setActionName('view')
                    ->setParam('id', $categoryId);
                return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
            }
        }

        // Two segments: category/question URL keys
        if (count($segments) === 2) {
            $categoryUrlKey = $segments[0];
            $questionUrlKey = $segments[1];
            $categoryId = $this->categoryResource->getByUrlKey($categoryUrlKey, $storeId);
            $questionId = $this->questionResource->getByUrlKey($questionUrlKey, $storeId);
            if ($categoryId && $questionId) {
                // The question must actually belong to the category; without this check
                // every category × question combination returns a 200 copy of the same
                // page (N×M duplicate content).
                if (!in_array($categoryId, $this->questionResource->lookupCategoryIds($questionId), true)) {
                    return null;
                }
                if ($needsSuffixRedirect || $stripSlash) {
                    return $this->redirect($request, $canonicalPath);
                }
                $request->setModuleName('faq')
                    ->setControllerName('question')
                    ->setActionName('view')
                    ->setParam('id', $questionId)
                    ->setParam('category_id', $categoryId);
                return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
            }
        }

        return null;
    }

    /**
     * Issue a 301 redirect to the canonical FAQ request path
     *
     * Mirrors Magento\UrlRewrite\Controller\Router::redirect().
     *
     * @param HttpRequest $request
     * @param string $canonicalPath Store-relative request path without leading slash
     * @return \Magento\Framework\App\ActionInterface
     */
    private function redirect(
        HttpRequest $request,
        string $canonicalPath
    ): \Magento\Framework\App\ActionInterface {
        $target = $this->url->getUrl('', ['_direct' => $canonicalPath, '_query' => $request->getParams()]);
        if ($this->response instanceof HttpResponseInterface) {
            $this->response->setRedirect($target, 301);
        }
        $request->setDispatched(true);

        return $this->actionFactory->create(\Magento\Framework\App\Action\Redirect::class);
    }

    /**
     * Check whether trailing slashes should be redirected away
     *
     * @return bool
     */
    private function isRemoveTrailingSlashEnabled(): bool
    {
        return $this->scopeConfig->isSetFlag(
            self::XML_PATH_REMOVE_TRAILING_SLASH,
            ScopeInterface::SCOPE_STORE
        );
    }

    /**
     * Look up tag ID by URL key
     *
     * @param string $urlKey
     * @return int|null
     */
    private function lookupTagId(string $urlKey): ?int
    {
        $connection = $this->tagResource->getConnection();
        $select = $connection->select()
            ->from($this->tagResource->getMainTable(), ['tag_id'])
            ->where('url_key = ?', $urlKey)
            ->limit(1);
        $tagId = $connection->fetchOne($select);

        return $tagId ? (int) $tagId : null;
    }
}
