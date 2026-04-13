<?php
/**
 * Magendoo Faq Search Block
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Block\Faq;

use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * FAQ Search Results Block
 */
class Search extends Template
{
    /**
     * @var QuestionCollectionFactory
     */
    private QuestionCollectionFactory $questionCollectionFactory;

    /**
     * @var FaqHelper
     */
    private FaqHelper $helper;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @param Context $context
     * @param QuestionCollectionFactory $questionCollectionFactory
     * @param FaqHelper $helper
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        QuestionCollectionFactory $questionCollectionFactory,
        FaqHelper $helper,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->questionCollectionFactory = $questionCollectionFactory;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        parent::__construct($context, $data);
    }

    /**
     * Get search query
     *
     * @return string
     */
    public function getSearchQuery(): string
    {
        return (string) $this->getRequest()->getParam('q', '');
    }

    /**
     * Get search results
     *
     * @return \Magendoo\Faq\Model\ResourceModel\Question\Collection
     */
    public function getResults(): \Magendoo\Faq\Model\ResourceModel\Question\Collection
    {
        $query = $this->getSearchQuery();
        $collection = $this->questionCollectionFactory->create();

        $collection->addFieldToFilter('visibility', QuestionInterface::VISIBILITY_PUBLIC);
        $collection->addFieldToFilter('status', QuestionInterface::STATUS_ANSWERED);

        $storeId = (int) $this->storeManager->getStore()->getId();
        $collection->addStoreFilter($storeId);
        $collection->addCustomerGroupVisibilityFilter((int) $this->customerSession->getCustomerGroupId());

        if ($query !== '') {
            $collection->addSearchFilter($query);
        }

        $pageSize = $this->helper->getQuestionsPerSearchPage();
        if ($pageSize > 0) {
            $collection->setPageSize($pageSize);
            $currentPage = (int) $this->getRequest()->getParam('p', 1);
            $collection->setCurPage($currentPage);
        }

        return $collection;
    }

    /**
     * Get no results text
     *
     * @return string
     */
    public function getNoResultsText(): string
    {
        return $this->helper->getNoResultsText();
    }
}
