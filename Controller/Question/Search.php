<?php
/**
 * Magendoo Faq Question Search Controller
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Controller\Question;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magendoo\Faq\Api\QuestionManagementInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magento\Store\Model\StoreManagerInterface;

/**
 * FAQ search results page controller
 */
class Search implements HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    protected PageFactory $resultPageFactory;

    /**
     * @var ResultFactory
     */
    protected ResultFactory $resultFactory;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var FaqHelper
     */
    protected FaqHelper $faqHelper;

    /**
     * @var QuestionManagementInterface
     */
    protected QuestionManagementInterface $questionManagement;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @param PageFactory $resultPageFactory
     * @param ResultFactory $resultFactory
     * @param RequestInterface $request
     * @param FaqHelper $faqHelper
     * @param QuestionManagementInterface $questionManagement
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        PageFactory $resultPageFactory,
        ResultFactory $resultFactory,
        RequestInterface $request,
        FaqHelper $faqHelper,
        QuestionManagementInterface $questionManagement,
        StoreManagerInterface $storeManager
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->faqHelper = $faqHelper;
        $this->questionManagement = $questionManagement;
        $this->storeManager = $storeManager;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        if (!$this->faqHelper->isEnabled()) {
            /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
            $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
            $resultForward->forward('noroute');
            return $resultForward;
        }

        $query = trim((string) $this->request->getParam('q'));

        // If empty query, redirect to FAQ home
        if (empty($query)) {
            /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
            $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
            return $resultRedirect->setPath('faq');
        }

        // Delegating to QuestionManagement::searchQuestions here is what
        // writes the row into magendoo_faq_search_log — the Search block
        // deliberately stays dumb and re-runs the collection query for
        // pagination. Errors are swallowed so a logging hiccup doesn't
        // break the user-facing search page.
        //
        // This only counts correctly because faq_question_search.xml marks
        // the results block cacheable="false": on a full page cache hit this
        // controller never executes, so the layout must stay non-cacheable
        // for the search log to see repeat searches.
        try {
            $storeId = (int) $this->storeManager->getStore()->getId();
            $this->questionManagement->searchQuestions($query, $storeId);
        } catch (\Exception $e) {
            // Intentionally ignored — logging is best-effort.
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $pageTitle = __('Search Results for: %1', $query);
        $resultPage->getConfig()->getTitle()->set($pageTitle);

        // Set robots for search results
        $robotsConfig = $this->faqHelper->getRobotsSearchResults();
        if ($robotsConfig) {
            $resultPage->getConfig()->setRobots($robotsConfig);
        }

        // Add breadcrumbs
        if ($this->faqHelper->isBreadcrumbsEnabled()) {
            $breadcrumbs = $resultPage->getLayout()->getBlock('breadcrumbs');
            if ($breadcrumbs) {
                $breadcrumbs->addCrumb('home', [
                    'label' => __('Home'),
                    'title' => __('Home'),
                    'link' => $this->faqHelper->getBaseUrl()
                ]);
                $breadcrumbs->addCrumb('faq', [
                    'label' => $this->faqHelper->getTitle(),
                    'title' => $this->faqHelper->getTitle(),
                    'link' => $this->faqHelper->getFaqUrl()
                ]);
                $breadcrumbs->addCrumb('search', [
                    'label' => __('Search Results'),
                    'title' => __('Search Results')
                ]);
            }
        }

        return $resultPage;
    }
}
