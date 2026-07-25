<?php
/**
 * Magendoo Faq Category View Controller
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Controller\Category;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magendoo\Faq\Api\CategoryRepositoryInterface;
use Magendoo\Faq\Api\Data\CategoryInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;

/**
 * FAQ category view page controller
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
     * @var CategoryRepositoryInterface
     */
    protected CategoryRepositoryInterface $categoryRepository;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var FaqHelper
     */
    protected FaqHelper $faqHelper;

    /**
     * @param PageFactory $resultPageFactory
     * @param ResultFactory $resultFactory
     * @param RequestInterface $request
     * @param CategoryRepositoryInterface $categoryRepository
     * @param StoreManagerInterface $storeManager
     * @param FaqHelper $faqHelper
     */
    public function __construct(
        PageFactory $resultPageFactory,
        ResultFactory $resultFactory,
        RequestInterface $request,
        CategoryRepositoryInterface $categoryRepository,
        StoreManagerInterface $storeManager,
        FaqHelper $faqHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->categoryRepository = $categoryRepository;
        $this->storeManager = $storeManager;
        $this->faqHelper = $faqHelper;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $categoryId = (int) $this->request->getParam('id');

        if (!$categoryId || !$this->faqHelper->isEnabled()) {
            return $this->forward404();
        }

        try {
            $category = $this->categoryRepository->getById($categoryId);
        } catch (NoSuchEntityException $e) {
            return $this->forward404();
        }

        // Check if category is enabled
        if ($category->getStatus() !== CategoryInterface::STATUS_ENABLED) {
            return $this->forward404();
        }

        // Check store assignment
        $storeIds = $category->getData('store_ids');
        if ($storeIds && is_array($storeIds)) {
            $currentStoreId = (int) $this->storeManager->getStore()->getId();
            if (!in_array(0, $storeIds) && !in_array($currentStoreId, $storeIds)) {
                return $this->forward404();
            }
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        // Set page title
        $pageTitle = $category->getPageTitle() ?: $category->getName();
        $resultPage->getConfig()->getTitle()->set($pageTitle);

        // Set meta information
        if ($category->getMetaTitle()) {
            $resultPage->getConfig()->getTitle()->set($category->getMetaTitle());
        }
        if ($category->getMetaDescription()) {
            $resultPage->getConfig()->setDescription($category->getMetaDescription());
        }

        // Set robots meta. Emit both axes so the output is always a complete
        // directive whenever either flag is set on the category.
        if ($category->getNoindex() || $category->getNofollow()) {
            $robots = [
                $category->getNoindex() ? 'NOINDEX' : 'INDEX',
                $category->getNofollow() ? 'NOFOLLOW' : 'FOLLOW',
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
                $breadcrumbs->addCrumb('category', [
                    'label' => $category->getName(),
                    'title' => $category->getName()
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
