<?php
/**
 * Magendoo Faq Category Repository
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model;

use Magendoo\Faq\Api\Data\CategoryInterface;
use Magendoo\Faq\Api\Data\CategorySearchResultsInterface;
use Magendoo\Faq\Api\Data\CategorySearchResultsInterfaceFactory;
use Magendoo\Faq\Api\CategoryRepositoryInterface;
use Magendoo\Faq\Model\ResourceModel\Category as ResourceCategory;
use Magendoo\Faq\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;

/**
 * FAQ Category Repository
 */
class CategoryRepository implements CategoryRepositoryInterface
{
    /**
     * @var ResourceCategory
     */
    protected ResourceCategory $resource;

    /**
     * @var CategoryFactory
     */
    protected CategoryFactory $categoryFactory;

    /**
     * @var CategoryCollectionFactory
     */
    protected CategoryCollectionFactory $categoryCollectionFactory;

    /**
     * @var CategorySearchResultsInterfaceFactory
     */
    protected CategorySearchResultsInterfaceFactory $searchResultsFactory;

    /**
     * @var DataObjectHelper
     */
    protected DataObjectHelper $dataObjectHelper;

    /**
     * @var DataObjectProcessor
     */
    protected DataObjectProcessor $dataObjectProcessor;

    /**
     * @var JoinProcessorInterface
     */
    protected JoinProcessorInterface $extensionAttributesJoinProcessor;

    /**
     * @var CollectionProcessorInterface
     */
    protected CollectionProcessorInterface $collectionProcessor;

    /**
     * @var ExtensibleDataObjectConverter
     */
    protected ExtensibleDataObjectConverter $extensibleDataObjectConverter;

    /**
     * @var array
     */
    protected array $registry = [];

    /**
     * @param ResourceCategory $resource
     * @param CategoryFactory $categoryFactory
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param CategorySearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param CollectionProcessorInterface $collectionProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceCategory $resource,
        CategoryFactory $categoryFactory,
        CategoryCollectionFactory $categoryCollectionFactory,
        CategorySearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        CollectionProcessorInterface $collectionProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->categoryFactory = $categoryFactory;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->dataObjectHelper = $dataObjectHelper;
        $this->dataObjectProcessor = $dataObjectProcessor;
        $this->extensionAttributesJoinProcessor = $extensionAttributesJoinProcessor;
        $this->collectionProcessor = $collectionProcessor;
        $this->extensibleDataObjectConverter = $extensibleDataObjectConverter;
    }

    /**
     * @inheritdoc
     */
    public function save(CategoryInterface $category): CategoryInterface
    {
        $categoryData = $this->extensibleDataObjectConverter->toNestedArray(
            $category,
            [],
            CategoryInterface::class
        );

        $categoryModel = $this->categoryFactory->create();

        if ($category->getCategoryId()) {
            $this->resource->load($categoryModel, $category->getCategoryId());
        }

        $categoryModel->addData($categoryData);

        // Preserve non-interface relation data (store_ids, customer_group_ids)
        // that toNestedArray strips because they aren't defined on the interface.
        if ($category instanceof \Magento\Framework\Model\AbstractModel) {
            foreach (['store_ids', 'customer_group_ids'] as $relationKey) {
                if ($category->hasData($relationKey)) {
                    $categoryModel->setData($relationKey, $category->getData($relationKey));
                }
            }
        }

        try {
            $this->resource->save($categoryModel);
        } catch (AlreadyExistsException $exception) {
            // URL key collisions (rewrite generation runs inside the save transaction).
            // Re-throw with the original actionable message so callers do not surface
            // this as a generic "something went wrong" failure.
            throw new CouldNotSaveException(__($exception->getMessage()), $exception);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()), $exception);
        }

        // Clear registry cache
        unset($this->registry[$categoryModel->getId()]);

        return $this->getById((int) $categoryModel->getId());
    }

    /**
     * @inheritdoc
     */
    public function getById(int $categoryId): CategoryInterface
    {
        if (!isset($this->registry[$categoryId])) {
            $category = $this->categoryFactory->create();
            $this->resource->load($category, $categoryId);

            if (!$category->getId()) {
                throw new NoSuchEntityException(__('FAQ category with id "%1" does not exist.', $categoryId));
            }

            $this->registry[$categoryId] = $category;
        }

        return $this->registry[$categoryId];
    }

    /**
     * @inheritdoc
     */
    public function getByUrlKey(string $urlKey, int $storeId): CategoryInterface
    {
        $categoryId = $this->resource->getByUrlKey($urlKey, $storeId);

        if (!$categoryId) {
            throw new NoSuchEntityException(
                __('FAQ category with URL key "%1" does not exist in store "%2".', $urlKey, $storeId)
            );
        }

        return $this->getById((int) $categoryId);
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): CategorySearchResultsInterface
    {
        $collection = $this->categoryCollectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setTotalCount($collection->getSize());

        $categories = [];
        /** @var Category $categoryModel */
        foreach ($collection->getItems() as $categoryModel) {
            $categories[] = $this->convertToDataModel($categoryModel);
        }

        $searchResults->setItems($categories);
        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function delete(CategoryInterface $category): bool
    {
        try {
            $categoryModel = $this->categoryFactory->create();
            $this->resource->load($categoryModel, $category->getCategoryId());
            $this->resource->delete($categoryModel);
            unset($this->registry[$category->getCategoryId()]);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $categoryId): bool
    {
        return $this->delete($this->getById($categoryId));
    }

    /**
     * Convert model to data interface
     *
     * @param Category $category
     * @return CategoryInterface
     */
    protected function convertToDataModel(Category $category): CategoryInterface
    {
        $categoryData = $this->dataObjectProcessor->buildOutputDataArray(
            $category,
            CategoryInterface::class
        );

        $categoryDto = $this->categoryFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $categoryDto,
            $categoryData,
            CategoryInterface::class
        );

        return $categoryDto;
    }
}
