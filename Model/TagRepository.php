<?php
/**
 * Magendoo Faq Tag Repository
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model;

use Magendoo\Faq\Api\Data\TagInterface;
use Magendoo\Faq\Api\Data\TagSearchResultsInterface;
use Magendoo\Faq\Api\Data\TagSearchResultsInterfaceFactory;
use Magendoo\Faq\Api\TagRepositoryInterface;
use Magendoo\Faq\Model\ResourceModel\Tag as ResourceTag;
use Magendoo\Faq\Model\ResourceModel\Tag\CollectionFactory as TagCollectionFactory;
use Magento\Framework\Api\DataObjectHelper;
use Magento\Framework\Api\ExtensibleDataObjectConverter;
use Magento\Framework\Api\ExtensionAttribute\JoinProcessorInterface;
use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Reflection\DataObjectProcessor;

/**
 * FAQ Tag Repository
 */
class TagRepository implements TagRepositoryInterface
{
    /**
     * @var ResourceTag
     */
    protected ResourceTag $resource;

    /**
     * @var TagFactory
     */
    protected TagFactory $tagFactory;

    /**
     * @var TagCollectionFactory
     */
    protected TagCollectionFactory $tagCollectionFactory;

    /**
     * @var TagSearchResultsInterfaceFactory
     */
    protected TagSearchResultsInterfaceFactory $searchResultsFactory;

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
     * @param ResourceTag $resource
     * @param TagFactory $tagFactory
     * @param TagCollectionFactory $tagCollectionFactory
     * @param TagSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param CollectionProcessorInterface $collectionProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceTag $resource,
        TagFactory $tagFactory,
        TagCollectionFactory $tagCollectionFactory,
        TagSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        CollectionProcessorInterface $collectionProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->tagFactory = $tagFactory;
        $this->tagCollectionFactory = $tagCollectionFactory;
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
    public function save(TagInterface $tag): TagInterface
    {
        $tagData = $this->extensibleDataObjectConverter->toNestedArray(
            $tag,
            [],
            TagInterface::class
        );

        $tagModel = $this->tagFactory->create();

        if ($tag->getTagId()) {
            $this->resource->load($tagModel, $tag->getTagId());
        }

        $tagModel->addData($tagData);

        try {
            $this->resource->save($tagModel);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()));
        }

        // Clear registry cache
        unset($this->registry[$tagModel->getId()]);

        return $this->getById((int) $tagModel->getId());
    }

    /**
     * @inheritdoc
     */
    public function getById(int $tagId): TagInterface
    {
        if (!isset($this->registry[$tagId])) {
            $tag = $this->tagFactory->create();
            $this->resource->load($tag, $tagId);

            if (!$tag->getId()) {
                throw new NoSuchEntityException(__('FAQ tag with id "%1" does not exist.', $tagId));
            }

            $this->registry[$tagId] = $tag;
        }

        return $this->registry[$tagId];
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): TagSearchResultsInterface
    {
        $collection = $this->tagCollectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setTotalCount($collection->getSize());

        $tags = [];
        /** @var Tag $tagModel */
        foreach ($collection->getItems() as $tagModel) {
            $tags[] = $this->convertToDataModel($tagModel);
        }

        $searchResults->setItems($tags);
        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function delete(TagInterface $tag): bool
    {
        try {
            $tagModel = $this->tagFactory->create();
            $this->resource->load($tagModel, $tag->getTagId());
            $this->resource->delete($tagModel);
            unset($this->registry[$tag->getTagId()]);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $tagId): bool
    {
        return $this->delete($this->getById($tagId));
    }

    /**
     * Convert model to data interface
     *
     * @param Tag $tag
     * @return TagInterface
     */
    protected function convertToDataModel(Tag $tag): TagInterface
    {
        $tagData = $this->dataObjectProcessor->buildOutputDataArray(
            $tag,
            TagInterface::class
        );

        $tagDto = $this->tagFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $tagDto,
            $tagData,
            TagInterface::class
        );

        return $tagDto;
    }
}
