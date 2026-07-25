<?php
/**
 * Magendoo Faq Category Repository Interface
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Api;

use Magendoo\Faq\Api\Data\CategoryInterface;
use Magendoo\Faq\Api\Data\CategorySearchResultsInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * FAQ Category repository interface
 *
 * @api
 */
interface CategoryRepositoryInterface
{
    /**
     * Save category
     *
     * @param \Magendoo\Faq\Api\Data\CategoryInterface $category
     * @return \Magendoo\Faq\Api\Data\CategoryInterface
     * @throws \Magento\Framework\Exception\CouldNotSaveException
     */
    public function save(CategoryInterface $category): CategoryInterface;

    /**
     * Get category by ID
     *
     * @param int $categoryId
     * @return \Magendoo\Faq\Api\Data\CategoryInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getById(int $categoryId): CategoryInterface;

    /**
     * Get category by URL key and store ID
     *
     * @param string $urlKey
     * @param int $storeId
     * @return \Magendoo\Faq\Api\Data\CategoryInterface
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getByUrlKey(string $urlKey, int $storeId): CategoryInterface;

    /**
     * Retrieve categories matching the specified criteria
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Magendoo\Faq\Api\Data\CategorySearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): CategorySearchResultsInterface;

    /**
     * Delete category
     *
     * @param \Magendoo\Faq\Api\Data\CategoryInterface $category
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     */
    public function delete(CategoryInterface $category): bool;

    /**
     * Delete category by ID
     *
     * @param int $categoryId
     * @return bool
     * @throws \Magento\Framework\Exception\CouldNotDeleteException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function deleteById(int $categoryId): bool;
}
