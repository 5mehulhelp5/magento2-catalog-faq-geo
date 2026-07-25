<?php
/**
 * Magendoo Faq Tag Repository Interface
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Api;

use Magendoo\Faq\Api\Data\TagInterface;
use Magendoo\Faq\Api\Data\TagSearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * FAQ Tag repository interface
 *
 * @api
 */
interface TagRepositoryInterface
{
    /**
     * Save tag
     *
     * @param \Magendoo\Faq\Api\Data\TagInterface $tag
     * @return \Magendoo\Faq\Api\Data\TagInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(TagInterface $tag): TagInterface;

    /**
     * Get tag by ID
     *
     * @param int $tagId
     * @return \Magendoo\Faq\Api\Data\TagInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $tagId): TagInterface;

    /**
     * Retrieve tags matching the specified criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magendoo\Faq\Api\Data\TagSearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): TagSearchResultsInterface;

    /**
     * Delete tag
     *
     * @param \Magendoo\Faq\Api\Data\TagInterface $tag
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(TagInterface $tag): bool;

    /**
     * Delete tag by ID
     *
     * @param int $tagId
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteById(int $tagId): bool;
}
