<?php
/**
 * Magendoo Faq Sitemap Provider: Categories
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model\Sitemap\ItemProvider;

use Magendoo\Faq\Api\Data\CategoryInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Sitemap\Model\ItemProvider\ItemProviderInterface;
use Magento\Sitemap\Model\SitemapItemInterfaceFactory;
use Magento\Store\Model\ScopeInterface;

/**
 * Emits one sitemap entry per enabled FAQ category in the given store.
 */
class FaqCategory implements ItemProviderInterface
{
    private const XML_PATH_ADD_TO_SITEMAP = 'magendoo_faq/seo/add_to_sitemap';
    private const XML_PATH_FREQUENCY = 'magendoo_faq/seo/sitemap_frequency';
    private const XML_PATH_PRIORITY = 'magendoo_faq/seo/sitemap_priority';

    private const DEFAULT_FREQUENCY = 'weekly';
    private const DEFAULT_PRIORITY = '0.5';

    /**
     * @param CategoryCollectionFactory $collectionFactory
     * @param SitemapItemInterfaceFactory $itemFactory
     * @param ScopeConfigInterface $scopeConfig
     * @param FaqHelper $helper
     */
    public function __construct(
        private readonly CategoryCollectionFactory $collectionFactory,
        private readonly SitemapItemInterfaceFactory $itemFactory,
        private readonly ScopeConfigInterface $scopeConfig,
        private readonly FaqHelper $helper
    ) {
    }

    /**
     * @inheritDoc
     */
    public function getItems($storeId): array
    {
        if (!$this->scopeConfig->isSetFlag(self::XML_PATH_ADD_TO_SITEMAP, ScopeInterface::SCOPE_STORE, $storeId)) {
            return [];
        }

        $collection = $this->collectionFactory->create();
        $collection->addFieldToFilter('status', CategoryInterface::STATUS_ENABLED)
            ->addStoreFilter((int) $storeId)
            ->addFieldToFilter('exclude_sitemap', ['neq' => 1]);

        $frequency = (string) $this->scopeConfig->getValue(
            self::XML_PATH_FREQUENCY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: self::DEFAULT_FREQUENCY;
        $priority = (string) $this->scopeConfig->getValue(
            self::XML_PATH_PRIORITY,
            ScopeInterface::SCOPE_STORE,
            $storeId
        ) ?: self::DEFAULT_PRIORITY;

        $items = [];
        foreach ($collection as $category) {
            $urlKey = (string) $category->getUrlKey();
            if ($urlKey === '') {
                continue;
            }

            $items[] = $this->itemFactory->create([
                'url' => $this->helper->buildUrlPath($urlKey, (int) $storeId),
                'updatedAt' => (string) $category->getUpdatedAt(),
                'priority' => $priority,
                'changeFrequency' => $frequency,
                'images' => null,
            ]);
        }

        return $items;
    }
}
