<?php
/**
 * Magendoo Faq Home Block
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Block\Faq;

use Magendoo\Faq\Api\Data\CategoryInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magendoo\Faq\Ui\DataProvider\Form\CategoryDataProvider;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * FAQ Home Page Block
 */
class Home extends Template
{
    /**
     * @var CategoryCollectionFactory
     */
    private CategoryCollectionFactory $categoryCollectionFactory;

    /**
     * @var FaqHelper
     */
    private FaqHelper $helper;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @param Context $context
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param FaqHelper $helper
     * @param CustomerSession $customerSession
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        CategoryCollectionFactory $categoryCollectionFactory,
        FaqHelper $helper,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->helper = $helper;
        $this->customerSession = $customerSession;
        parent::__construct($context, $data);
    }

    /**
     * Get enabled categories for the current store
     *
     * @return \Magendoo\Faq\Model\ResourceModel\Category\Collection
     */
    public function getCategories(): \Magendoo\Faq\Model\ResourceModel\Category\Collection
    {
        $collection = $this->categoryCollectionFactory->create();
        $collection->addFieldToFilter('status', CategoryInterface::STATUS_ENABLED);

        $storeId = (int) $this->_storeManager->getStore()->getId();
        $collection->addStoreFilter($storeId);
        $collection->addCustomerGroupVisibilityFilter((int) $this->customerSession->getCustomerGroupId());

        $sortBy = $this->helper->getSortCategoriesBy();
        if ($sortBy === 'name') {
            $collection->setOrder('name', 'ASC');
        } else {
            $collection->setOrder('position', 'ASC');
        }

        return $collection;
    }

    /**
     * Get search URL
     *
     * @return string
     */
    public function getSearchUrl(): string
    {
        return $this->getUrl('faq/question/search');
    }

    /**
     * Get category URL
     *
     * @param CategoryInterface $category
     * @return string
     */
    public function getCategoryUrl(CategoryInterface $category): string
    {
        $urlKey = $category->getUrlKey();
        if ($urlKey) {
            return $this->getBaseUrl() . $this->helper->buildUrlPath($urlKey);
        }

        return $this->getUrl('faq/category/view', ['id' => $category->getCategoryId()]);
    }

    /**
     * Build the media URL for a category icon
     *
     * The column stores a bare filename written by the icon upload controller into
     * pub/media/<CategoryDataProvider::ICON_MEDIA_PATH>, so the storefront has to build the
     * URL rather than treat the stored value as one. A value that already looks absolute is
     * passed through, which keeps any icon set before that convention existed working.
     *
     * @param CategoryInterface $category
     * @return string
     */
    public function getCategoryIconUrl(CategoryInterface $category): string
    {
        $icon = (string) $category->getIcon();
        if ($icon === '') {
            return '';
        }

        if (preg_match('#^(https?:)?//#i', $icon) === 1) {
            return $icon;
        }

        /** @var \Magento\Store\Model\Store $store */
        $store = $this->_storeManager->getStore();
        $mediaUrl = $store->getBaseUrl(\Magento\Framework\UrlInterface::URL_TYPE_MEDIA);

        return $mediaUrl . CategoryDataProvider::ICON_MEDIA_PATH . '/' . ltrim($icon, '/');
    }

    /**
     * Check if search box is enabled
     *
     * @return bool
     */
    public function isSearchBoxEnabled(): bool
    {
        return $this->helper->isSearchBoxEnabled();
    }

    /**
     * Get FAQ title
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->helper->getTitle();
    }
}
