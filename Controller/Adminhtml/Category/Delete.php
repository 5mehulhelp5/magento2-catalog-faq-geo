<?php
/**
 * Magendoo Faq Category Delete Controller
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magendoo\Faq\Api\CategoryRepositoryInterface;

/**
 * Delete category controller
 */
class Delete extends Action implements HttpPostActionInterface
{
    /**
     * Authorization resource
     */
    public const ADMIN_RESOURCE = 'Magendoo_Faq::category_delete';

    /**
     * @var CategoryRepositoryInterface
     */
    protected CategoryRepositoryInterface $categoryRepository;

    /**
     * @param Context $context
     * @param CategoryRepositoryInterface $categoryRepository
     */
    public function __construct(
        Context $context,
        CategoryRepositoryInterface $categoryRepository
    ) {
        $this->categoryRepository = $categoryRepository;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        $categoryId = (int) $this->getRequest()->getParam('category_id');

        if (!$categoryId) {
            $this->messageManager->addErrorMessage(__('We can\'t find a category to delete.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $this->categoryRepository->deleteById($categoryId);
            $this->messageManager->addSuccessMessage(__('The category has been deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
