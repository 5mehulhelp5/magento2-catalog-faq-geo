<?php
/**
 * Magendoo Faq Question Management
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model;

use Magendoo\Faq\Api\Data\PublicQuestionInterface;
use Magendoo\Faq\Api\Data\PublicQuestionSearchResultsInterface;
use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Api\Data\QuestionSearchResultsInterfaceFactory;
use Magendoo\Faq\Api\QuestionManagementInterface;
use Magendoo\Faq\Api\QuestionRepositoryInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\Email\Sender as EmailSender;
use Magendoo\Faq\Model\ResourceModel\Question as ResourceQuestion;
use Magendoo\Faq\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;
use Magendoo\Faq\Model\Source\Rating\Type as RatingType;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Model\Group as CustomerGroup;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Psr\Log\LoggerInterface;

/**
 * FAQ Question Management Service
 *
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
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
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var RemoteAddress
     */
    private RemoteAddress $remoteAddress;

    /**
     * @var FaqHelper
     */
    private FaqHelper $faqHelper;

    /**
     * @var FilterManager
     */
    private FilterManager $filterManager;

    /**
     * @var UserContextInterface
     */
    private UserContextInterface $userContext;

    /**
     * @var CustomerRepositoryInterface
     */
    private CustomerRepositoryInterface $customerRepository;

    /**
     * Vote types accepted by rateQuestion()
     */
    public const VOTE_POSITIVE = 'positive';
    public const VOTE_NEGATIVE = 'negative';

    /**
     * Vote type recorded for 1-5 star votes (rating/type = average_rating)
     */
    public const VOTE_TYPE_STAR = 'star';

    /**
     * Bounds of the star scale
     */
    private const STAR_MIN = 1;
    private const STAR_MAX = 5;

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
     * @param CustomerSession $customerSession
     * @param RemoteAddress $remoteAddress
     * @param FaqHelper $faqHelper
     * @param FilterManager $filterManager
     * @param UserContextInterface $userContext
     * @param CustomerRepositoryInterface $customerRepository
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
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
        RemoteAddress $remoteAddress,
        FaqHelper $faqHelper,
        FilterManager $filterManager,
        UserContextInterface $userContext,
        CustomerRepositoryInterface $customerRepository
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
        $this->faqHelper = $faqHelper;
        $this->filterManager = $filterManager;
        $this->userContext = $userContext;
        $this->customerRepository = $customerRepository;
    }

    /**
     * @inheritdoc
     */
    public function submitQuestion(QuestionInterface $question, bool $gdprConsent = false): PublicQuestionInterface
    {
        $title = trim((string) $question->getTitle());
        $senderName = trim((string) $question->getSenderName());
        $senderEmail = trim((string) $question->getSenderEmail());

        if ($title === '' || $senderName === '' || $senderEmail === '') {
            throw new LocalizedException(__('Please fill in all required fields.'));
        }

        if (!filter_var($senderEmail, FILTER_VALIDATE_EMAIL)) {
            throw new LocalizedException(__('Please enter a valid email address.'));
        }

        // Identity must come from the authenticated context, never from the
        // payload: the submit route is anonymous, so a caller-supplied
        // customer_id would let anyone attribute questions to real customers.
        $customerId = $this->userContext->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER
            && $this->userContext->getUserId()
                ? (int) $this->userContext->getUserId()
                : null;

        if ($customerId === null && !$this->faqHelper->isGuestQuestionAllowed()) {
            throw new LocalizedException(__('Please log in to submit a question.'));
        }

        if ($this->faqHelper->isGdprEnabled() && !$gdprConsent) {
            throw new LocalizedException(
                __('You must agree to the privacy policy before submitting your question.')
            );
        }

        // Record the consent so it can be produced later: WHEN it was given
        // (UTC, the same gmtDate() source as the entity timestamps) and a
        // snapshot of the wording that was shown — the configured text is
        // mutable, so a record pointing at live config proves nothing. Both
        // fields are server-owned; whatever the caller sent is discarded.
        if ($this->faqHelper->isGdprEnabled() && $gdprConsent) {
            $question->setConsentGivenAt($this->dateTime->gmtDate());
            $question->setConsentText($this->faqHelper->getGdprText());
        } else {
            $question->setConsentGivenAt(null);
            $question->setConsentText(null);
        }

        $question->setTitle($title);
        $question->setSenderName($senderName);
        $question->setSenderEmail($senderEmail);
        $question->setCustomerId($customerId);

        // Server-owned fields: the slug is always generated from the title
        // (the storefront renders answers unescaped once published, so no
        // submitter-controlled markup or arbitrary slug may enter here),
        // answers belong to the admin and counters start at zero.
        $question->setUrlKey($this->generateUrlKey($title));
        $question->setStatus(QuestionInterface::STATUS_PENDING);
        $question->setVisibility(QuestionInterface::VISIBILITY_NONE);
        $question->setShortAnswer(null);
        $question->setFullAnswer(null);
        $question->setPositiveRating(0);
        $question->setNegativeRating(0);
        $question->setAverageRating(0.0);
        $question->setViewCount(0);
        $question->setPosition(0);

        try {
            $savedQuestion = $this->questionRepository->save($question);
        } catch (\Exception $e) {
            throw new CouldNotSaveException(__('Could not save the question: %1', $e->getMessage()));
        }

        // Both entry points (storefront form and anonymous REST) pass through
        // here, so this is the single place the merchant is alerted about a
        // new pending question.
        $this->notifyAdmin($savedQuestion);

        return $savedQuestion;
    }

    /**
     * @inheritdoc
     */
    public function rateQuestion(int $questionId, string $voteType): bool
    {
        // The vote value travels through the single $voteType parameter so
        // every existing entry point (AJAX controller, anonymous REST route)
        // keeps working unchanged: helpful/not-helpful modes accept
        // positive/negative, the star mode accepts "1".."5". Which set is
        // valid is decided by the configured rating type, matching what the
        // storefront widget renders.
        $starValue = null;
        if ($this->faqHelper->getRatingType() === RatingType::AVERAGE_RATING) {
            if (!ctype_digit($voteType)
                || (int) $voteType < self::STAR_MIN
                || (int) $voteType > self::STAR_MAX
            ) {
                throw new LocalizedException(__('Please provide a star rating between 1 and 5.'));
            }
            $starValue = (int) $voteType;
        } elseif (!in_array($voteType, [self::VOTE_POSITIVE, self::VOTE_NEGATIVE], true)) {
            throw new LocalizedException(__('Invalid vote type.'));
        }

        // Verify question exists
        $this->questionRepository->getById($questionId);

        // Like the identity below, login state is resolved server-side so the
        // anonymous REST route and the AJAX controller share one guard.
        if (!$this->customerSession->isLoggedIn() && !$this->faqHelper->isGuestRatingAllowed()) {
            throw new LocalizedException(__('Please log in to rate this question.'));
        }

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
            'vote_type' => $starValue !== null ? self::VOTE_TYPE_STAR : $voteType,
            'value' => $starValue ?? 0,
            'created_at' => $this->dateTime->gmtDate(),
        ]);

        $questionTable = $this->resourceConnection->getTableName('magendoo_faq_question');

        if ($starValue !== null) {
            // average_rating is a genuine 0-5 average over the recorded star
            // values. Helpful/not-helpful votes carry no scale, so they never
            // contribute to it — they only move the counters below.
            $select = $connection->select()
                ->from($ratingTable, [new \Zend_Db_Expr('AVG(`value`)')])
                ->where('question_id = ?', $questionId)
                ->where('vote_type = ?', self::VOTE_TYPE_STAR);

            $average = (float) $connection->fetchOne($select);
            $connection->update(
                $questionTable,
                ['average_rating' => round($average, 2)],
                ['question_id = ?' => $questionId]
            );
        } elseif ($voteType === self::VOTE_POSITIVE) {
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

        return true;
    }

    /**
     * @inheritdoc
     */
    public function getProductQuestions(int $productId, int $storeId): PublicQuestionSearchResultsInterface
    {
        $collection = $this->questionCollectionFactory->create();
        $collection->addProductFilter($productId);
        $collection->addActiveFilter();
        $collection->addVisibilityFilter(QuestionInterface::VISIBILITY_PUBLIC);
        $collection->addStoreFilter($storeId);
        // The storefront blocks scope their collections by customer group;
        // this anonymous REST-reachable method must apply the same predicate
        // or a group-restricted question stays readable without logging in.
        $collection->addCustomerGroupVisibilityFilter($this->resolveCustomerGroupId());
        $collection->setOrder('position', 'ASC');

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function getCategoryQuestions(int $categoryId, int $storeId): PublicQuestionSearchResultsInterface
    {
        $collection = $this->questionCollectionFactory->create();
        $collection->addCategoryFilter($categoryId);
        $collection->addActiveFilter();
        $collection->addVisibilityFilter(QuestionInterface::VISIBILITY_PUBLIC);
        $collection->addStoreFilter($storeId);
        // Same customer-group predicate as the storefront blocks (see
        // getProductQuestions()).
        $collection->addCustomerGroupVisibilityFilter($this->resolveCustomerGroupId());
        $collection->setOrder('position', 'ASC');

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setTotalCount($collection->getSize());
        $searchResults->setItems($collection->getItems());

        return $searchResults;
    }

    /**
     * @inheritdoc
     */
    public function searchQuestions(string $queryText, int $storeId): PublicQuestionSearchResultsInterface
    {
        $collection = $this->questionCollectionFactory->create();
        $collection->addSearchFilter($queryText);
        $collection->addActiveFilter();
        $collection->addVisibilityFilter(QuestionInterface::VISIBILITY_PUBLIC);
        $collection->addStoreFilter($storeId);
        // Same customer-group predicate as the storefront blocks (see
        // getProductQuestions()).
        $collection->addCustomerGroupVisibilityFilter($this->resolveCustomerGroupId());

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
    public function getQuestionByUrlKey(string $urlKey, int $storeId): PublicQuestionInterface
    {
        $isLoggedInCustomer = $this->userContext->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER
            && $this->userContext->getUserId();

        // The resource lookup enforces the status + visibility predicate itself
        // (a question retracted to visibility "none" never resolves; "logged_in"
        // resolves only for an authenticated customer), so the login state is
        // resolved here and passed down rather than re-filtered afterwards.
        $questionId = $this->resourceQuestion->getByUrlKey($urlKey, $storeId, (bool) $isLoggedInCustomer);
        if (!$questionId) {
            throw new NoSuchEntityException(
                __('FAQ question with URL key "%1" does not exist in store "%2".', $urlKey, $storeId)
            );
        }

        // Group-restricted questions are hidden from every listing, so the
        // direct url-key lookup must apply the same predicate. Same message as
        // a miss so a hidden question is indistinguishable from a missing one.
        $allowedGroupIds = $this->resourceQuestion->lookupCustomerGroupIds($questionId);
        if ($allowedGroupIds !== []
            && !in_array($this->resolveCustomerGroupId(), $allowedGroupIds, true)
        ) {
            throw new NoSuchEntityException(
                __('FAQ question with URL key "%1" does not exist in store "%2".', $urlKey, $storeId)
            );
        }

        $question = $this->questionRepository->getById($questionId);

        // Defence in depth: keep the service-layer visibility gate in front of
        // the loaded entity even though the resource lookup already filtered.
        $visibility = $question->getVisibility();
        if ($visibility !== QuestionInterface::VISIBILITY_PUBLIC
            && !($visibility === QuestionInterface::VISIBILITY_LOGGED_IN && $isLoggedInCustomer)
        ) {
            throw new NoSuchEntityException(
                __('FAQ question with URL key "%1" does not exist in store "%2".', $urlKey, $storeId)
            );
        }

        return $question;
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

    /**
     * Notify the merchant that a new question arrived.
     *
     * Gated on the admin_notifications/enabled flag so a disabled feature is
     * not reported as a failure. A failed send is logged, never surfaced:
     * the shopper's submission already succeeded and must not error out
     * because of mail transport trouble.
     *
     * @param QuestionInterface $question
     * @return void
     */
    private function notifyAdmin(QuestionInterface $question): void
    {
        if (!$this->faqHelper->isAdminNotificationEnabled()) {
            return;
        }

        try {
            if (!$this->emailSender->sendAdminNotification($question)) {
                $this->logger->error(
                    'Magendoo FAQ: admin notification was not sent for question '
                    . (int) $question->getQuestionId()
                    . '; check the admin notification recipient and template configuration.'
                );
            }
        } catch (\Exception $e) {
            $this->logger->error(
                'Magendoo FAQ: admin notification failed for question '
                . (int) $question->getQuestionId() . ': ' . $e->getMessage()
            );
        }
    }

    /**
     * Resolve the current caller's customer group server-side.
     *
     * Storefront requests carry the group on the customer session; REST
     * token requests only populate the user context, so the group is read
     * back from the customer record. Anonymous callers fall back to the
     * NOT LOGGED IN group, mirroring how the storefront blocks scope their
     * collections.
     *
     * @return int
     */
    private function resolveCustomerGroupId(): int
    {
        if ($this->customerSession->isLoggedIn()) {
            return (int) $this->customerSession->getCustomerGroupId();
        }

        if ($this->userContext->getUserType() === UserContextInterface::USER_TYPE_CUSTOMER
            && $this->userContext->getUserId()
        ) {
            try {
                $customer = $this->customerRepository->getById((int) $this->userContext->getUserId());

                return (int) $customer->getGroupId();
            } catch (NoSuchEntityException | LocalizedException $e) {
                return CustomerGroup::NOT_LOGGED_IN_ID;
            }
        }

        return CustomerGroup::NOT_LOGGED_IN_ID;
    }

    /**
     * Generate a URL key slug from a title.
     *
     * @param string $title
     * @return string
     */
    private function generateUrlKey(string $title): string
    {
        $slug = $this->filterManager->translitUrl($title);
        $slug = strtolower($slug);
        $slug = preg_replace('#[^a-z0-9]+#', '-', $slug);
        $slug = trim((string) $slug, '-');

        if ($slug === '') {
            $slug = 'question';
        }

        // Append uniqueness suffix to avoid collisions on pending/auto-generated keys.
        $slug .= '-' . substr((string) uniqid('', true), -6);

        // Keep the url_key length sane.
        if (strlen($slug) > 128) {
            $slug = substr($slug, 0, 128);
            $slug = rtrim($slug, '-');
        }

        return $slug;
    }
}
