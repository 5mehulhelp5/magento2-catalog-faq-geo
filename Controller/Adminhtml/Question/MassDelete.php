<?php
/**
 * Magendoo Faq Question Mass Delete Controller
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
use Magento\Ui\Component\MassAction\Filter;
use Magendoo\Faq\Api\QuestionRepositoryInterface;
use Magendoo\Faq\Model\ResourceModel\Question\CollectionFactory;

/**
 * Mass delete questions controller
 */
class MassDelete extends Action implements HttpPostActionInterface
{
    /**
     * Authorization resource
     */
    public const ADMIN_RESOURCE = 'Magendoo_Faq::question_delete';

    /**
     * @var Filter
     */
    protected Filter $filter;

    /**
     * @var CollectionFactory
     */
    protected CollectionFactory $collectionFactory;

    /**
     * @var QuestionRepositoryInterface
     */
    protected QuestionRepositoryInterface $questionRepository;

    /**
     * @param Context $context
     * @param Filter $filter
     * @param CollectionFactory $collectionFactory
     * @param QuestionRepositoryInterface $questionRepository
     */
    public function __construct(
        Context $context,
        Filter $filter,
        CollectionFactory $collectionFactory,
        QuestionRepositoryInterface $questionRepository
    ) {
        $this->filter = $filter;
        $this->collectionFactory = $collectionFactory;
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

        try {
            $collection = $this->filter->getCollection($this->collectionFactory->create());
            $questionsDeleted = 0;

            foreach ($collection->getAllIds() as $questionId) {
                $this->questionRepository->deleteById((int) $questionId);
                $questionsDeleted++;
            }

            if ($questionsDeleted) {
                $this->messageManager->addSuccessMessage(
                    __('A total of %1 question(s) have been deleted.', $questionsDeleted)
                );
            }
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        }

        return $resultRedirect->setPath('*/*/');
    }
}
