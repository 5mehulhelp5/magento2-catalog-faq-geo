<?php
/**
 * Magendoo Faq Question Repository Interface
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Api;

use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Api\Data\QuestionSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * FAQ Question repository interface
 *
 * @api
 */
interface QuestionRepositoryInterface
{
    /**
     * Save question
     *
     * @param \Magendoo\Faq\Api\Data\QuestionInterface $question
     * @return \Magendoo\Faq\Api\Data\QuestionInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(QuestionInterface $question): QuestionInterface;

    /**
     * Get question by ID
     *
     * @param int $questionId
     * @return \Magendoo\Faq\Api\Data\QuestionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $questionId): QuestionInterface;

    /**
     * Get question by URL key and store ID
     *
     * @param string $urlKey
     * @param int $storeId
     * @return \Magendoo\Faq\Api\Data\QuestionInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByUrlKey(string $urlKey, int $storeId): QuestionInterface;

    /**
     * Retrieve questions matching the specified criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magendoo\Faq\Api\Data\QuestionSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): QuestionSearchResultsInterface;

    /**
     * Delete question
     *
     * @param \Magendoo\Faq\Api\Data\QuestionInterface $question
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(QuestionInterface $question): bool;

    /**
     * Delete question by ID
     *
     * @param int $questionId
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteById(int $questionId): bool;
}
