<?php
/**
 * Magendoo Faq Search Block
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Block\Faq;

use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\Question as QuestionModel;
use Magendoo\Faq\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * FAQ Search Results Block
 */
class Search extends Template implements IdentityInterface
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
     * @var \Magendoo\Faq\Model\ResourceModel\Question\Collection|null
     */
    private ?\Magendoo\Faq\Model\ResourceModel\Question\Collection $results = null;

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
        if ($this->results === null) {
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

            $this->results = $collection;
        }

        return $this->results;
    }

    /**
     * Return identifiers for produced content
     *
     * The results page is served non-cacheable (see faq_question_search.xml), so
     * these identities are not collected today; they are still declared so the
     * block stays correct if the page ever becomes cacheable again.
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        $identities = [[QuestionModel::CACHE_TAG]];
        foreach ($this->getResults() as $question) {
            if ($question instanceof IdentityInterface) {
                $identities[] = $question->getIdentities();
            }
        }

        return array_merge([], ...$identities);
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
