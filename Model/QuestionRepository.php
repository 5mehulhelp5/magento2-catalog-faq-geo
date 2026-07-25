<?php
/**
 * Magendoo Faq Question Repository
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model;

use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Api\Data\QuestionSearchResultsInterface;
use Magendoo\Faq\Api\Data\QuestionSearchResultsInterfaceFactory;
use Magendoo\Faq\Api\QuestionRepositoryInterface;
use Magendoo\Faq\Model\ResourceModel\Question as ResourceQuestion;
use Magendoo\Faq\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;
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
 * FAQ Question Repository
 */
class QuestionRepository implements QuestionRepositoryInterface
{
    /**
     * @var ResourceQuestion
     */
    protected ResourceQuestion $resource;

    /**
     * @var QuestionFactory
     */
    protected QuestionFactory $questionFactory;

    /**
     * @var QuestionCollectionFactory
     */
    protected QuestionCollectionFactory $questionCollectionFactory;

    /**
     * @var QuestionSearchResultsInterfaceFactory
     */
    protected QuestionSearchResultsInterfaceFactory $searchResultsFactory;

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
     * @param ResourceQuestion $resource
     * @param QuestionFactory $questionFactory
     * @param QuestionCollectionFactory $questionCollectionFactory
     * @param QuestionSearchResultsInterfaceFactory $searchResultsFactory
     * @param DataObjectHelper $dataObjectHelper
     * @param DataObjectProcessor $dataObjectProcessor
     * @param JoinProcessorInterface $extensionAttributesJoinProcessor
     * @param CollectionProcessorInterface $collectionProcessor
     * @param ExtensibleDataObjectConverter $extensibleDataObjectConverter
     */
    public function __construct(
        ResourceQuestion $resource,
        QuestionFactory $questionFactory,
        QuestionCollectionFactory $questionCollectionFactory,
        QuestionSearchResultsInterfaceFactory $searchResultsFactory,
        DataObjectHelper $dataObjectHelper,
        DataObjectProcessor $dataObjectProcessor,
        JoinProcessorInterface $extensionAttributesJoinProcessor,
        CollectionProcessorInterface $collectionProcessor,
        ExtensibleDataObjectConverter $extensibleDataObjectConverter
    ) {
        $this->resource = $resource;
        $this->questionFactory = $questionFactory;
        $this->questionCollectionFactory = $questionCollectionFactory;
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
    public function save(QuestionInterface $question): QuestionInterface
    {
        $questionData = $this->extensibleDataObjectConverter->toNestedArray(
            $question,
            [],
            QuestionInterface::class
        );

        $questionModel = $this->questionFactory->create();

        if ($question->getQuestionId()) {
            $this->resource->load($questionModel, $question->getQuestionId());
        }

        $questionModel->addData($questionData);

        // Preserve non-interface relation data (store_ids, category_ids,
        // product_ids, tags, customer_group_ids) that toNestedArray strips
        // because they aren't defined on QuestionInterface. The admin Save
        // controller and the anonymous submit endpoint both pass these
        // through the same repository entry point.
        if ($question instanceof \Magento\Framework\Model\AbstractModel) {
            foreach (['store_ids', 'category_ids', 'product_ids', 'tags', 'customer_group_ids'] as $relationKey) {
                if ($question->hasData($relationKey)) {
                    $questionModel->setData($relationKey, $question->getData($relationKey));
                }
            }
        }

        try {
            $this->resource->save($questionModel);
        } catch (AlreadyExistsException $exception) {
            // URL key collisions (rewrite generation runs inside the save transaction).
            // Re-throw with the original actionable message so callers do not surface
            // this as a generic "something went wrong" failure.
            throw new CouldNotSaveException(__($exception->getMessage()), $exception);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(__($exception->getMessage()), $exception);
        }

        // Clear registry cache
        unset($this->registry[$questionModel->getId()]);

        return $this->getById((int) $questionModel->getId());
    }

    /**
     * @inheritdoc
     */
    public function getById(int $questionId): QuestionInterface
    {
        if (!isset($this->registry[$questionId])) {
            $question = $this->questionFactory->create();
            $this->resource->load($question, $questionId);

            if (!$question->getId()) {
                throw new NoSuchEntityException(__('FAQ question with id "%1" does not exist.', $questionId));
            }

            $this->registry[$questionId] = $question;
        }

        return $this->registry[$questionId];
    }

    /**
     * @inheritdoc
     */
    public function getByUrlKey(string $urlKey, int $storeId): QuestionInterface
    {
        $questionId = $this->resource->getByUrlKey($urlKey, $storeId);

        if (!$questionId) {
            throw new NoSuchEntityException(
                __('FAQ question with URL key "%1" does not exist in store "%2".', $urlKey, $storeId)
            );
        }

        return $this->getById((int) $questionId);
    }

    /**
     * @inheritdoc
     */
    public function getList(SearchCriteriaInterface $searchCriteria): QuestionSearchResultsInterface
    {
        $collection = $this->questionCollectionFactory->create();

        $this->collectionProcessor->process($searchCriteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setTotalCount($collection->getSize());

        $questions = [];
        /** @var Question $questionModel */
        foreach ($collection->getItems() as $questionModel) {
            $questions[] = $this->convertToDataModel($questionModel);
        }

        $searchResults->setItems($questions);
        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function delete(QuestionInterface $question): bool
    {
        try {
            $questionModel = $this->questionFactory->create();
            $this->resource->load($questionModel, $question->getQuestionId());
            $this->resource->delete($questionModel);
            unset($this->registry[$question->getQuestionId()]);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(__($exception->getMessage()));
        }

        return true;
    }

    /**
     * @inheritdoc
     */
    public function deleteById(int $questionId): bool
    {
        return $this->delete($this->getById($questionId));
    }

    /**
     * Convert model to data interface
     *
     * @param Question $question
     * @return QuestionInterface
     */
    protected function convertToDataModel(Question $question): QuestionInterface
    {
        $questionData = $this->dataObjectProcessor->buildOutputDataArray(
            $question,
            QuestionInterface::class
        );

        $questionDto = $this->questionFactory->create();
        $this->dataObjectHelper->populateWithArray(
            $questionDto,
            $questionData,
            QuestionInterface::class
        );

        return $questionDto;
    }
}
