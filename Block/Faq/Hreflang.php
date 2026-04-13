<?php
/**
 * Magendoo Faq Hreflang Tags Block
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Block\Faq;

use Magendoo\Faq\Helper\Data as FaqHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Emits `<link rel="alternate" hreflang="..." href="..."/>` tags for every
 * active store view, helping Google serve the correct language version of
 * FAQ pages to visitors from different locales.
 *
 * Placed in `head.additional` via layout XML and gated by the
 * `magendoo_faq/seo/hreflang_enabled` config flag.
 */
class Hreflang extends Template
{
    private FaqHelper $helper;
    private StoreManagerInterface $storeManager;

    public function __construct(
        Context $context,
        FaqHelper $helper,
        StoreManagerInterface $storeManager,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        parent::__construct($context, $data);
    }

    /**
     * Build an array of hreflang link data for the current FAQ page.
     *
     * @return array<int, array{hreflang: string, href: string}>
     */
    public function getHreflangLinks(): array
    {
        if (!$this->helper->isEnabled()) {
            return [];
        }

        // Derive the path suffix from the current request (e.g. "faq/shipping-faq")
        $pathInfo = trim((string) $this->getRequest()->getOriginalPathInfo(), '/');
        if ($pathInfo === '') {
            return [];
        }

        $links = [];
        $stores = $this->storeManager->getStores(false, true);

        foreach ($stores as $store) {
            if (!$store->getIsActive()) {
                continue;
            }

            $baseUrl = rtrim((string) $store->getBaseUrl(), '/');
            $href = $baseUrl . '/' . $pathInfo;

            // Use the store's locale as hreflang (e.g. "en_US" → "en-us").
            // Magento stores the locale in the `general/locale/code` config.
            $locale = $store->getConfig('general/locale/code') ?: 'en_US';
            $hreflang = str_replace('_', '-', strtolower($locale));

            $links[] = [
                'hreflang' => $hreflang,
                'href' => $href,
            ];
        }

        // Add x-default pointing at the current store's URL
        if (!empty($links)) {
            $currentBase = rtrim((string) $this->storeManager->getStore()->getBaseUrl(), '/');
            $links[] = [
                'hreflang' => 'x-default',
                'href' => $currentBase . '/' . $pathInfo,
            ];
        }

        return $links;
    }
}
