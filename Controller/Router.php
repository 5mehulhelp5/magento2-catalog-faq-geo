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
use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\RouterInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\ResourceModel\Category as CategoryResource;
use Magendoo\Faq\Model\ResourceModel\Question as QuestionResource;
use Magendoo\Faq\Model\ResourceModel\Tag as TagResource;

/**
 * FAQ custom URL router
 */
class Router implements RouterInterface
{
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
     * @param ActionFactory $actionFactory
     * @param FaqHelper $faqHelper
     * @param CategoryResource $categoryResource
     * @param QuestionResource $questionResource
     * @param TagResource $tagResource
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        ActionFactory $actionFactory,
        FaqHelper $faqHelper,
        CategoryResource $categoryResource,
        QuestionResource $questionResource,
        TagResource $tagResource,
        StoreManagerInterface $storeManager
    ) {
        $this->actionFactory = $actionFactory;
        $this->faqHelper = $faqHelper;
        $this->categoryResource = $categoryResource;
        $this->questionResource = $questionResource;
        $this->tagResource = $tagResource;
        $this->storeManager = $storeManager;
    }

    /**
     * Match the request to FAQ routes
     *
     * @param RequestInterface $request
     * @return \Magento\Framework\App\ActionInterface|null
     */
    public function match(RequestInterface $request): ?\Magento\Framework\App\ActionInterface
    {
        if (!$this->faqHelper->isEnabled()) {
            return null;
        }

        $pathInfo = trim($request->getPathInfo(), '/');
        $urlPrefix = $this->faqHelper->getUrlPrefix();

        if (!$pathInfo || !str_starts_with($pathInfo, $urlPrefix)) {
            return null;
        }

        // Strip the URL prefix
        $path = substr($pathInfo, strlen($urlPrefix));
        $path = ltrim($path, '/');

        // Strip URL suffix if configured
        if ($this->faqHelper->isUrlSuffixEnabled()) {
            $suffix = $this->faqHelper->getUrlSuffix();
            if ($suffix && str_ends_with($path, $suffix)) {
                $path = substr($path, 0, -strlen($suffix));
            }
        }

        // FAQ home page
        if ($path === '' || $path === false) {
            $request->setModuleName('faq')
                ->setControllerName('index')
                ->setActionName('index');
            return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
        }

        // Search page
        if ($path === 'search') {
            $request->setModuleName('faq')
                ->setControllerName('question')
                ->setActionName('search');
            return $this->actionFactory->create(\Magento\Framework\App\Action\Forward::class);
        }

        $segments = explode('/', $path);

        // Tag page: tag/{url-key}
        if (count($segments) === 2 && $segments[0] === 'tag') {
            $tagUrlKey = $segments[1];
            $tagId = $this->lookupTagId($tagUrlKey);
            if ($tagId) {
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
