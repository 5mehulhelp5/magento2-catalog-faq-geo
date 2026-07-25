<?php
/**
 * Magendoo Faq Question View Controller
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
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Api\QuestionManagementInterface;
use Magendoo\Faq\Api\QuestionRepositoryInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\ResourceModel\Question as QuestionResource;

/**
 * FAQ question view page controller
 */
class View implements HttpGetActionInterface
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
     * @var QuestionRepositoryInterface
     */
    protected QuestionRepositoryInterface $questionRepository;

    /**
     * @var QuestionManagementInterface
     */
    protected QuestionManagementInterface $questionManagement;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var CustomerSession
     */
    protected CustomerSession $customerSession;

    /**
     * @var FaqHelper
     */
    protected FaqHelper $faqHelper;

    /**
     * @var QuestionResource
     */
    protected QuestionResource $questionResource;

    /**
     * @param PageFactory $resultPageFactory
     * @param ResultFactory $resultFactory
     * @param RequestInterface $request
     * @param QuestionRepositoryInterface $questionRepository
     * @param QuestionManagementInterface $questionManagement
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     * @param FaqHelper $faqHelper
     * @param QuestionResource $questionResource
     */
    public function __construct(
        PageFactory $resultPageFactory,
        ResultFactory $resultFactory,
        RequestInterface $request,
        QuestionRepositoryInterface $questionRepository,
        QuestionManagementInterface $questionManagement,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        FaqHelper $faqHelper,
        QuestionResource $questionResource
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->questionRepository = $questionRepository;
        $this->questionManagement = $questionManagement;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        $this->faqHelper = $faqHelper;
        $this->questionResource = $questionResource;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $questionId = (int) $this->request->getParam('id');

        if (!$questionId || !$this->faqHelper->isEnabled()) {
            return $this->forward404();
        }

        try {
            $question = $this->questionRepository->getById($questionId);
        } catch (NoSuchEntityException $e) {
            return $this->forward404();
        }

        // Check status - only answered questions are visible
        if ($question->getStatus() !== QuestionInterface::STATUS_ANSWERED) {
            return $this->forward404();
        }

        // Check visibility
        $visibility = $question->getVisibility();
        if ($visibility === QuestionInterface::VISIBILITY_NONE) {
            return $this->forward404();
        }

        // For logged_in visibility, check if customer is logged in
        if ($visibility === QuestionInterface::VISIBILITY_LOGGED_IN && !$this->customerSession->isLoggedIn()) {
            return $this->forward404();
        }

        // Check customer group restriction. Every listing hides a
        // group-restricted question (Collection::addCustomerGroupVisibilityFilter),
        // so its direct URL must not stay readable either. An empty relation
        // means unrestricted, mirroring the collection predicate; guests carry
        // the NOT LOGGED IN group id on the session.
        $allowedGroupIds = $this->questionResource->lookupCustomerGroupIds($questionId);
        if ($allowedGroupIds !== []) {
            $currentGroupId = (int) $this->customerSession->getCustomerGroupId();
            if (!in_array($currentGroupId, $allowedGroupIds, true)) {
                return $this->forward404();
            }
        }

        // Check store assignment
        $storeIds = $question->getData('store_ids');
        if ($storeIds && is_array($storeIds)) {
            $currentStoreId = (int) $this->storeManager->getStore()->getId();
            if (!in_array(0, $storeIds) && !in_array($currentStoreId, $storeIds)) {
                return $this->forward404();
            }
        }

        // Increment view count
        try {
            $this->questionManagement->incrementViewCount($questionId);
        } catch (\Exception $e) {
            // Silently fail - view count is not critical
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        // Set page title
        $pageTitle = $question->getTitle();
        $resultPage->getConfig()->getTitle()->set($pageTitle);

        // Set meta information
        if ($question->getMetaTitle()) {
            $resultPage->getConfig()->getTitle()->set($question->getMetaTitle());
        }
        if ($question->getMetaDescription()) {
            $resultPage->getConfig()->setDescription($question->getMetaDescription());
        }

        // Set robots meta. Emit both axes so the output is always a complete
        // directive (INDEX,FOLLOW / NOINDEX,FOLLOW / etc.) whenever either
        // flag is set on the question.
        if ($question->getNoindex() || $question->getNofollow()) {
            $robots = [
                $question->getNoindex() ? 'NOINDEX' : 'INDEX',
                $question->getNofollow() ? 'NOFOLLOW' : 'FOLLOW',
            ];
            $resultPage->getConfig()->setRobots(implode(',', $robots));
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
                $breadcrumbs->addCrumb('question', [
                    'label' => $question->getTitle(),
                    'title' => $question->getTitle()
                ]);
            }
        }

        return $resultPage;
    }

    /**
     * Forward to 404 page
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    protected function forward404(): \Magento\Framework\Controller\ResultInterface
    {
        /** @var \Magento\Framework\Controller\Result\Forward $resultForward */
        $resultForward = $this->resultFactory->create(ResultFactory::TYPE_FORWARD);
        $resultForward->forward('noroute');
        return $resultForward;
    }
}
