<?php
/**
 * Magendoo Faq Question Suggest Controller
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Controller\Question;

use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;

/**
 * FAQ search autocomplete suggestions controller
 */
class Suggest implements HttpGetActionInterface
{
    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var JsonFactory
     */
    protected JsonFactory $jsonFactory;

    /**
     * @var QuestionCollectionFactory
     */
    protected QuestionCollectionFactory $questionCollectionFactory;

    /**
     * @var FaqHelper
     */
    protected FaqHelper $faqHelper;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var CustomerSession
     */
    protected CustomerSession $customerSession;

    /**
     * @param RequestInterface $request
     * @param JsonFactory $jsonFactory
     * @param QuestionCollectionFactory $questionCollectionFactory
     * @param FaqHelper $faqHelper
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     */
    public function __construct(
        RequestInterface $request,
        JsonFactory $jsonFactory,
        QuestionCollectionFactory $questionCollectionFactory,
        FaqHelper $faqHelper,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession
    ) {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->questionCollectionFactory = $questionCollectionFactory;
        $this->faqHelper = $faqHelper;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute(): \Magento\Framework\Controller\Result\Json
    {
        $result = $this->jsonFactory->create();

        if (!$this->faqHelper->isEnabled()) {
            return $result->setData([]);
        }

        $query = trim((string) $this->request->getParam('q'));

        if (mb_strlen($query) < 2) {
            return $result->setData([]);
        }

        $collection = $this->questionCollectionFactory->create();
        $collection->addActiveFilter();
        $collection->addVisibilityFilter(QuestionInterface::VISIBILITY_PUBLIC);

        $storeId = (int) $this->storeManager->getStore()->getId();
        $collection->addStoreFilter($storeId);
        $collection->addCustomerGroupVisibilityFilter((int) $this->customerSession->getCustomerGroupId());
        $collection->addSearchFilter($query);
        $collection->setPageSize(5);

        $suggestions = [];
        foreach ($collection as $question) {
            $suggestions[] = [
                'title' => $question->getTitle(),
                'url' => $this->buildQuestionUrl($question),
            ];
        }

        return $result->setData($suggestions);
    }

    /**
     * Build question URL
     *
     * @param QuestionInterface $question
     * @return string
     */
    private function buildQuestionUrl(QuestionInterface $question): string
    {
        $baseUrl = $this->storeManager->getStore()->getBaseUrl();
        $urlKey = $question->getUrlKey();

        if ($urlKey) {
            return $baseUrl . $this->faqHelper->getUrlPrefix() . '/' . $urlKey;
        }

        return $baseUrl . 'faq/question/view/id/' . $question->getQuestionId();
    }
}
