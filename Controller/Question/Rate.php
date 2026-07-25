<?php
/**
 * Magendoo Faq Question Rate Controller
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Controller\Question;

use Magendoo\Faq\Api\QuestionManagementInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Psr\Log\LoggerInterface;

/**
 * AJAX question rating controller
 */
class Rate implements HttpPostActionInterface
{
    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var JsonFactory
     */
    protected JsonFactory $jsonFactory;

    /**
     * @var QuestionManagementInterface
     */
    protected QuestionManagementInterface $questionManagement;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @param RequestInterface $request
     * @param JsonFactory $jsonFactory
     * @param QuestionManagementInterface $questionManagement
     * @param LoggerInterface $logger
     */
    public function __construct(
        RequestInterface $request,
        JsonFactory $jsonFactory,
        QuestionManagementInterface $questionManagement,
        LoggerInterface $logger
    ) {
        $this->request = $request;
        $this->jsonFactory = $jsonFactory;
        $this->questionManagement = $questionManagement;
        $this->logger = $logger;
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $result = $this->jsonFactory->create();

        $questionId = (int) $this->request->getParam('question_id');
        $voteType = (string) $this->request->getParam('vote_type');

        if (!$questionId || !$voteType) {
            return $result->setData([
                'success' => false,
                'message' => __('Invalid request parameters.')
            ]);
        }

        try {
            // Identity is resolved inside the service so every entry point shares one guard.
            $this->questionManagement->rateQuestion($questionId, $voteType);
            return $result->setData([
                'success' => true,
                'message' => __('Thank you for your feedback!')
            ]);
        } catch (LocalizedException $e) {
            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);
        } catch (\Exception $e) {
            // Never surface driver-level detail to an anonymous caller.
            $this->logger->error('FAQ rating failed: ' . $e->getMessage());
            return $result->setData([
                'success' => false,
                'message' => __('Your vote could not be recorded.')
            ]);
        }
    }
}
