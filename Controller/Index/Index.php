<?php
/**
 * Magendoo Faq Index Controller
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Controller\Index;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\View\Result\PageFactory;
use Magendoo\Faq\Helper\Data as FaqHelper;

/**
 * FAQ home page controller
 */
class Index implements HttpGetActionInterface
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
     * @var FaqHelper
     */
    protected FaqHelper $faqHelper;

    /**
     * @param PageFactory $resultPageFactory
     * @param ResultFactory $resultFactory
     * @param FaqHelper $faqHelper
     */
    public function __construct(
        PageFactory $resultPageFactory,
        ResultFactory $resultFactory,
        FaqHelper $faqHelper
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->resultFactory = $resultFactory;
        $this->faqHelper = $faqHelper;
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

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();

        $title = $this->faqHelper->getTitle();
        $resultPage->getConfig()->getTitle()->set($title);

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
                    'label' => $title,
                    'title' => $title
                ]);
            }
        }

        return $resultPage;
    }
}
