<?php
/**
 * Magendoo Faq Question Management
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
use Magendoo\Faq\Api\QuestionManagementInterface;
use Magendoo\Faq\Api\QuestionRepositoryInterface;
use Magendoo\Faq\Model\Email\Sender as EmailSender;
use Magendoo\Faq\Model\ResourceModel\Question as ResourceQuestion;
use Magendoo\Faq\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Psr\Log\LoggerInterface;

/**
 * FAQ Question Management Service
 */
class QuestionManagement implements QuestionManagementInterface
{
    /**
     * @var QuestionRepositoryInterface
     */
    protected QuestionRepositoryInterface $questionRepository;

    /**
     * @var ResourceQuestion
     */
    protected ResourceQuestion $resourceQuestion;

    /**
     * @var QuestionCollectionFactory
     */
    protected QuestionCollectionFactory $questionCollectionFactory;

    /**
     * @var QuestionSearchResultsInterfaceFactory
     */
    protected QuestionSearchResultsInterfaceFactory $searchResultsFactory;

    /**
     * @var ResourceConnection
     */
    protected ResourceConnection $resourceConnection;

    /**
     * @var SearchLogFactory
     */
    protected SearchLogFactory $searchLogFactory;

    /**
     * @var ResourceModel\SearchLog
     */
    protected ResourceModel\SearchLog $searchLogResource;

    /**
     * @var DateTime
     */
    protected DateTime $dateTime;

    /**
     * @var LoggerInterface
     */
    protected LoggerInterface $logger;

    /**
     * @var EmailSender
     */
    protected EmailSender $emailSender;

    /**
     * @param QuestionRepositoryInterface $questionRepository
     * @param ResourceQuestion $resourceQuestion
     * @param QuestionCollectionFactory $questionCollectionFactory
     * @param QuestionSearchResultsInterfaceFactory $searchResultsFactory
     * @param ResourceConnection $resourceConnection
     * @param SearchLogFactory $searchLogFactory
     * @param ResourceModel\SearchLog $searchLogResource
     * @param DateTime $dateTime
     * @param LoggerInterface $logger
     * @param EmailSender $emailSender
     */
    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var RemoteAddress
     */
    private RemoteAddress $remoteAddress;

    /**
     * Vote types accepted by rateQuestion()
     */
    public const VOTE_POSITIVE = 'positive';
    public const VOTE_NEGATIVE = 'negative';

    public function __construct(
        QuestionRepositoryInterface $questionRepository,
        ResourceQuestion $resourceQuestion,
        QuestionCollectionFactory $questionCollectionFactory,
        QuestionSearchResultsInterfaceFactory $searchResultsFactory,
        ResourceConnection $resourceConnection,
        SearchLogFactory $searchLogFactory,
        ResourceModel\SearchLog $searchLogResource,
        DateTime $dateTime,
        LoggerInterface $logger,
        EmailSender $emailSender,
        CustomerSession $customerSession,
        RemoteAddress $remoteAddress
    ) {
        $this->questionRepository = $questionRepository;
        $this->resourceQuestion = $resourceQuestion;
        $this->questionCollectionFactory = $questionCollectionFactory;
        $this->searchResultsFactory = $searchResultsFactory;
        $this->resourceConnection = $resourceConnection;
        $this->searchLogFactory = $searchLogFactory;
        $this->searchLogResource = $searchLogResource;
        $this->dateTime = $dateTime;
        $this->logger = $logger;
        $this->emailSender = $emailSender;
        $this->customerSession = $customerSession;
        $this->remoteAddress = $remoteAddress;
    }

    /**
     * @inheritdoc
     */
    public function submitQuestion(QuestionInterface $question): QuestionInterface
    {
        if (!$question->getTitle()) {
            throw new LocalizedException(__('Question title is required.'));
        }

        if (!$question->getSenderEmail()) {
            throw new LocalizedException(__('Sender email is required.'));
        }

        // Set default status for submitted questions
        $question->setStatus(QuestionInterface::STATUS_PENDING);
        $question->setVisibility(QuestionInterface::VISIBILITY_NONE);

        try {
            return $this->questionRepository->save($question);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the question: %1', $e->getMessage()));
        }
    }

    /**
     * @inheritdoc
     */
    public function rateQuestion(int $questionId, string $voteType): bool
    {
        if (!in_array($voteType, [self::VOTE_POSITIVE, self::VOTE_NEGATIVE], true)) {
            throw new LocalizedException(__('Invalid vote type.'));
        }

        // Verify question exists
        $this->questionRepository->getById($questionId);

        // The de-duplication key must never come from the caller: this method backs an
        // anonymous REST route, so a client that supplied its own ip_address/customer_id
        // could vote without limit and attribute votes to arbitrary customers.
        $customerId = $this->customerSession->isLoggedIn()
            ? (int) $this->customerSession->getCustomerId()
            : null;
        $ipAddress = (string) $this->remoteAddress->getRemoteAddress();

        $connection = $this->resourceConnection->getConnection();
        $ratingTable = $this->resourceConnection->getTableName('magendoo_faq_rating');

        // Check for duplicate vote
        $select = $connection->select()
            ->from($ratingTable, ['rating_id'])
            ->where('question_id = ?', $questionId);

        if ($customerId) {
            $select->where('customer_id = ?', $customerId);
        } else {
            $select->where('ip_address = ?', $ipAddress);
        }

        if ($connection->fetchOne($select)) {
            throw new LocalizedException(__('You have already rated this question.'));
        }

        // Insert the rating
        $connection->insert($ratingTable, [
            'question_id' => $questionId,
            'customer_id' => $customerId,
            'ip_address' => $ipAddress,
            'vote_type' => $voteType,
            'created_at' => $this->dateTime->gmtDate(),
        ]);

        // Update question rating counts
        $questionTable = $this->resourceConnection->getTableName('magendoo_faq_question');

        if ($voteType === self::VOTE_POSITIVE) {
            $connection->update(
                $questionTable,
                ['positive_rating' => new \Zend_Db_Expr('positive_rating + 1')],
                ['question_id = ?' => $questionId]
            );
        } else {
            $connection->update(
                $questionTable,
                ['negative_rating' => new \Zend_Db_Expr('negative_rating + 1')],
                ['question_id = ?' => $questionId]
            );
        }

        // Recalculate average rating
        $select = $connection->select()
            ->from($questionTable, ['positive_rating', 'negative_rating'])
            ->where('question_id = ?', $questionId);

        $row = $connection->fetchRow($select);
        $total = (int) $row['positive_rating'] + (int) $row['negative_rating'];
        $average = $total > 0 ? (float) $row['positive_rating'] / $total * 100 : 0;

        $connection->update(
            $questionTable,
            ['average_rating' => round($average, 2)],
            ['question_id = ?' => $questionId]
        );

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getProductQuestions(int $productId, int $storeId): QuestionSearchResultsInterface
    {
        $collection = $this->questionCollectionFactory->create();
        $collection->addProductFilter($productId);
        $collection->addActiveFilter();
        $collection->addVisibilityFilter(QuestionInterface::VISIBILITY_PUBLIC);
        $collection->addStoreFilter($storeId);
        $collection->setOrder('position', 'ASC');

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function getCategoryQuestions(int $categoryId, int $storeId): QuestionSearchResultsInterface
    {
        $collection = $this->questionCollectionFactory->create();
        $collection->addCategoryFilter($categoryId);
        $collection->addActiveFilter();
        $collection->addVisibilityFilter(QuestionInterface::VISIBILITY_PUBLIC);
        $collection->addStoreFilter($storeId);
        $collection->setOrder('position', 'ASC');

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function searchQuestions(string $queryText, int $storeId): QuestionSearchResultsInterface
    {
        $collection = $this->questionCollectionFactory->create();
        $collection->addSearchFilter($queryText);
        $collection->addActiveFilter();
        $collection->addVisibilityFilter(QuestionInterface::VISIBILITY_PUBLIC);
        $collection->addStoreFilter($storeId);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());

        // Log search term
        $this->logSearchTerm($queryText, $storeId, $collection->getSize());

        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function incrementViewCount(int $questionId): void
    {
        $this->resourceQuestion->incrementViewCount($questionId);
    }

    /**
     * @inheritdoc
     */
    public function sendAnswerNotification(int $questionId): bool
    {
        try {
            $question = $this->questionRepository->getById($questionId);
        } catch (NoSuchEntityException $e) {
            $this->logger->error(
                __('FAQ answer notification failed, question %1 not found: %2', $questionId, $e->getMessage())
            );
            return false;
        }

        return $this->emailSender->sendAnswerNotification($question);
    }

    /**
     * Log search term to search log table
     *
     * @param string $queryText
     * @param int $storeId
     * @param int $resultsCount
     * @return void
     */
    protected function logSearchTerm(string $queryText, int $storeId, int $resultsCount): void
    {
        try {
            $searchLog = $this->searchLogFactory->create();
            $searchLog->setQueryText($queryText);
            $searchLog->setStoreId($storeId);
            $searchLog->setResultsCount($resultsCount);
            $this->searchLogResource->save($searchLog);
        } catch (\Exception $e) {
            $this->logger->error(__('Failed to log FAQ search term: %1', $e->getMessage()));
        }
    }
}
