<?php
/**
 * Magendoo Faq Question Submit Controller
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Controller\Question;

use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Api\Data\QuestionInterfaceFactory;
use Magendoo\Faq\Api\QuestionManagementInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Data\Form\FormKey\Validator as FormKeyValidator;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Message\ManagerInterface as MessageManagerInterface;

/**
 * Submit a new question from frontend
 *
 * Thin HTTP adapter around QuestionManagementInterface::submitQuestion(): all
 * field validation, the guest-submission gate, the GDPR consent check, identity
 * resolution and slug generation live in the service so the storefront form and
 * the web API route enforce the same rules.
 */
class Submit implements HttpPostActionInterface
{
    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var ResultFactory
     */
    protected ResultFactory $resultFactory;

    /**
     * @var FormKeyValidator
     */
    protected FormKeyValidator $formKeyValidator;

    /**
     * @var MessageManagerInterface
     */
    protected MessageManagerInterface $messageManager;

    /**
     * @var QuestionInterfaceFactory
     */
    protected QuestionInterfaceFactory $questionFactory;

    /**
     * @var QuestionManagementInterface
     */
    protected QuestionManagementInterface $questionManagement;

    /**
     * @var CustomerSession
     */
    protected CustomerSession $customerSession;

    /**
     * @var FaqHelper
     */
    protected FaqHelper $faqHelper;

    /**
     * @param RequestInterface $request
     * @param ResultFactory $resultFactory
     * @param FormKeyValidator $formKeyValidator
     * @param MessageManagerInterface $messageManager
     * @param QuestionInterfaceFactory $questionFactory
     * @param QuestionManagementInterface $questionManagement
     * @param CustomerSession $customerSession
     * @param FaqHelper $faqHelper
     */
    public function __construct(
        RequestInterface $request,
        ResultFactory $resultFactory,
        FormKeyValidator $formKeyValidator,
        MessageManagerInterface $messageManager,
        QuestionInterfaceFactory $questionFactory,
        QuestionManagementInterface $questionManagement,
        CustomerSession $customerSession,
        FaqHelper $faqHelper
    ) {
        $this->request = $request;
        $this->resultFactory = $resultFactory;
        $this->formKeyValidator = $formKeyValidator;
        $this->messageManager = $messageManager;
        $this->questionFactory = $questionFactory;
        $this->questionManagement = $questionManagement;
        $this->customerSession = $customerSession;
        $this->faqHelper = $faqHelper;
    }

    /**
     * Execute action
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        /** @var \Magento\Framework\Controller\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);

        // Accept POST only
        if (!$this->request->isPost()) {
            return $resultRedirect->setRefererUrl();
        }

        // Validate form key
        if (!$this->formKeyValidator->validate($this->request)) {
            $this->messageManager->addErrorMessage(__('Invalid form key. Please refresh the page.'));
            return $resultRedirect->setRefererUrl();
        }

        // The service enforces the guest-submission setting too; this check only
        // exists to give guests a redirect to the login page instead of an error.
        if (!$this->customerSession->isLoggedIn() && !$this->faqHelper->isGuestQuestionAllowed()) {
            $this->messageManager->addErrorMessage(__('Please log in to submit a question.'));
            return $resultRedirect->setPath('customer/account/login');
        }

        $productIdParam = $this->request->getParam('product_id');
        $productId = $productIdParam !== null && $productIdParam !== '' ? (int) $productIdParam : null;

        try {
            /** @var QuestionInterface $question */
            $question = $this->questionFactory->create();
            $question->setTitle(trim((string) $this->request->getParam('title')));
            $question->setSenderName(trim((string) $this->request->getParam('sender_name')));
            $question->setSenderEmail(trim((string) $this->request->getParam('sender_email')));

            // Link product if provided — handled by ResourceModel\Question::saveProductRelation on save
            if ($productId !== null && $productId > 0) {
                $question->setData('product_ids', [$productId]);
            }

            $this->questionManagement->submitQuestion(
                $question,
                (bool) $this->request->getParam('gdpr_consent')
            );

            $this->messageManager->addSuccessMessage(
                __('Your question has been submitted and will be reviewed by our team.')
            );
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
            return $resultRedirect->setRefererUrl();
        } catch (\Exception $e) {
            $this->messageManager->addErrorMessage(
                __('Something went wrong while submitting your question. Please try again.')
            );
            return $resultRedirect->setRefererUrl();
        }

        // Redirect back to the referring page on success. setRefererUrl() routes
        // through the validated referer (internal URLs only, base URL fallback),
        // matching the error branches above.
        return $resultRedirect->setRefererUrl();
    }
}
