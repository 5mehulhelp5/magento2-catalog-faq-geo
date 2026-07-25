<?php
/**
 * Magendoo Faq SearchLog Index Controller
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Controller\Adminhtml\SearchLog;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Search log grid controller
 */
class Index extends Action implements HttpGetActionInterface
{
    /**
     * Authorization resource
     */
    public const ADMIN_RESOURCE = 'Magendoo_Faq::search_log';

    /**
     * @param Context $context
     */
    public function __construct(
        Context $context
    ) {
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        /** @var \Magento\Backend\Model\View\Result\Page $resultPage */
        $resultPage = $this->resultFactory->create(ResultFactory::TYPE_PAGE);
        $resultPage->setActiveMenu('Magendoo_Faq::search_log');
        $resultPage->addBreadcrumb(__('FAQ'), __('FAQ'));
        $resultPage->addBreadcrumb(__('FAQ Search Terms'), __('FAQ Search Terms'));
        $resultPage->getConfig()->getTitle()->prepend(__('FAQ Search Terms'));

        return $resultPage;
    }
}
