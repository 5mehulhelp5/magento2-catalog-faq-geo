<?php
/**
 * Magendoo Faq Question Management Interface
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Api;

use Magendoo\Faq\Api\Data\PublicQuestionInterface;
use Magendoo\Faq\Api\Data\PublicQuestionSearchResultsInterface;
use Magendoo\Faq\Api\Data\QuestionInterface;

/**
 * FAQ Question management interface
 *
 * Backs the anonymous storefront web API routes, so every method returns the
 * storefront-safe PublicQuestion projection and resolves caller identity
 * internally instead of trusting request-supplied identity fields.
 *
 * @api
 */
interface QuestionManagementInterface
{
    /**
     * Submit a new question from the storefront or the anonymous web API route.
     *
     * Validates required fields and the sender email format, enforces the
     * guest-submission setting and the GDPR consent requirement, resolves the
     * customer identity from the authenticated context and generates the URL
     * key server-side. Caller-supplied status, visibility, url_key, answers,
     * counters and customer_id are ignored.
     *
     * @param \Magendoo\Faq\Api\Data\QuestionInterface $question
     * @param bool $gdprConsent
     * @return \Magendoo\Faq\Api\Data\PublicQuestionInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function submitQuestion(QuestionInterface $question, bool $gdprConsent = false): PublicQuestionInterface;

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
     * @return \Magendoo\Faq\Api\Data\PublicQuestionSearchResultsInterface
     */
    public function getProductQuestions(int $productId, int $storeId): PublicQuestionSearchResultsInterface;

    /**
     * Get questions associated with a category
     *
     * @param int $categoryId
     * @param int $storeId
     * @return \Magendoo\Faq\Api\Data\PublicQuestionSearchResultsInterface
     */
    public function getCategoryQuestions(int $categoryId, int $storeId): PublicQuestionSearchResultsInterface;

    /**
     * Search questions by query text
     *
     * @param string $queryText
     * @param int $storeId
     * @return \Magendoo\Faq\Api\Data\PublicQuestionSearchResultsInterface
     */
    public function searchQuestions(string $queryText, int $storeId): PublicQuestionSearchResultsInterface;

    /**
     * Get a published question by URL key for storefront display
     *
     * @param string $urlKey
     * @param int $storeId
     * @return \Magendoo\Faq\Api\Data\PublicQuestionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getQuestionByUrlKey(string $urlKey, int $storeId): PublicQuestionInterface;

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
