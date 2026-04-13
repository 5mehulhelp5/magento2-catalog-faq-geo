<?php
/**
 * Magendoo Faq Tag View Controller
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Controller\Tag;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magento\Store\Model\StoreManagerInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\ResourceModel\Tag as TagResource;
use Magendoo\Faq\Model\TagFactory;

/**
 * FAQ tag view page controller
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
     * @var FaqHelper
     */
    protected FaqHelper $faqHelper;

    /**
     * @var TagFactory
     */
    protected TagFactory $tagFactory;

    /**
     * @var TagResource
     */
    protected TagResource $tagResource;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @param PageFactory $resultPageFactory
     * @param ResultFactory $resultFactory
     * @param RequestInterface $request
     * @param FaqHelper $faqHelper
     * @param TagFactory $tagFactory
     * @param TagResource $tagResource
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        PageFactory $resultPageFactory,
        ResultFactory $resultFactory,
        RequestInterface $request,
        FaqHelper $faqHelper,
        TagFactory $tagFactory,
        TagResource $tagResource,
        StoreManagerInterface $storeManager
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultFactory = $resultFactory;
        $this->request = $request;
        $this->faqHelper = $faqHelper;
        $this->tagFactory = $tagFactory;
        $this->tagResource = $tagResource;
        $this->storeManager = $storeManager;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $tagId = (int) $this->request->getParam('id');

        if (!$tagId || !$this->faqHelper->isEnabled()) {
            return $this->forward404();
        }

        $tag = $this->tagFactory->create();
        $this->tagResource->load($tag, $tagId);

        if (!$tag->getTagId()) {
            return $this->forward404();
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        // Set page title
        $resultPage->getConfig()->getTitle()->set($tag->getName());

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
                $breadcrumbs->addCrumb('tag', [
                    'label' => __('Tag: %1', $tag->getName()),
                    'title' => __('Tag: %1', $tag->getName())
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
