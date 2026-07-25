<?php
/**
 * Magendoo Faq Question Management Interface
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Api;

use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Api\Data\QuestionSearchResultsInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * FAQ Question management interface
 *
 * @api
 */
interface QuestionManagementInterface
{
    /**
     * Submit a new question from frontend
     *
     * @param \Magendoo\Faq\Api\Data\QuestionInterface $question
     * @return \Magendoo\Faq\Api\Data\QuestionInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function submitQuestion(QuestionInterface $question): QuestionInterface;

    /**
     * Rate a question (positive or negative)
     *
     * @param int $questionId
     * @param string $voteType
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function rateQuestion(int $questionId, string $voteType): bool;

    /**
     * Get questions associated with a product
     *
     * @param int $productId
     * @param int $storeId
     * @return \Magendoo\Faq\Api\Data\QuestionSearchResultsInterface
     */
    public function getProductQuestions(int $productId, int $storeId): QuestionSearchResultsInterface;

    /**
     * Get questions associated with a category
     *
     * @param int $categoryId
     * @param int $storeId
     * @return \Magendoo\Faq\Api\Data\QuestionSearchResultsInterface
     */
    public function getCategoryQuestions(int $categoryId, int $storeId): QuestionSearchResultsInterface;

    /**
     * Search questions by query text
     *
     * @param string $queryText
     * @param int $storeId
     * @return \Magendoo\Faq\Api\Data\QuestionSearchResultsInterface
     */
    public function searchQuestions(string $queryText, int $storeId): QuestionSearchResultsInterface;

    /**
     * Increment the view count for a question
     *
     * @param int $questionId
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function incrementViewCount(int $questionId): void;

    /**
     * Send answer notification email to question submitter
     *
     * @param int $questionId
     * @return bool
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function sendAnswerNotification(int $questionId): bool;
}
