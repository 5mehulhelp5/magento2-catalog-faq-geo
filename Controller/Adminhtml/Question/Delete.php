<?php
/**
 * Magendoo Faq Question Delete Controller
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Controller\Adminhtml\Question;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magendoo\Faq\Api\QuestionRepositoryInterface;

/**
 * Delete question controller
 */
class Delete extends Action implements HttpPostActionInterface
{
    /**
     * Authorization resource
     */
    public const ADMIN_RESOURCE = 'Magendoo_Faq::question_delete';

    /**
     * @var QuestionRepositoryInterface
     */
    protected QuestionRepositoryInterface $questionRepository;

    /**
     * @param Context $context
     * @param QuestionRepositoryInterface $questionRepository
     */
    public function __construct(
        Context $context,
        QuestionRepositoryInterface $questionRepository
    ) {
        $this->questionRepository = $questionRepository;
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

        $questionId = (int) $this->getRequest()->getParam('question_id');

        if (!$questionId) {
            $this->messageManager->addErrorMessage(__('We can\'t find a question to delete.'));
            return $resultRedirect->setPath('*/*/');
        }

        try {
            $this->questionRepository->deleteById($questionId);
            $this->messageManager->addSuccessMessage(__('The question has been deleted.'));
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
