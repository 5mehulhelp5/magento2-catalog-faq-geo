<?php
/**
 * Magendoo Faq Question Management Test
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Test\Unit\Model;

use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Api\Data\QuestionSearchResultsInterfaceFactory;
use Magendoo\Faq\Api\QuestionRepositoryInterface;
use Magendoo\Faq\Model\Email\Sender as EmailSender;
use Magento\Authorization\Model\UserContextInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\QuestionManagement;
use Magendoo\Faq\Model\ResourceModel\Question as ResourceQuestion;
use Magendoo\Faq\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;
use Magendoo\Faq\Model\ResourceModel\SearchLog as SearchLogResource;
use Magendoo\Faq\Model\SearchLogFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;
use Magento\Framework\DB\Select;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Filter\FilterManager;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\HTTP\PhpEnvironment\RemoteAddress;
use Magento\Framework\Stdlib\DateTime\DateTime;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * Pins the rateQuestion() contract:
 * - only 'positive'/'negative' vote types are accepted;
 * - the de-duplication identity comes from the session / remote address,
 *   never from the caller (the method signature carries no identity args);
 * - duplicate votes raise the "already rated" LocalizedException;
 * - the vote is written to magendoo_faq_rating (a past defect queried the
 *   non-existent magendoo_faq_question_rating table).
 */
#[CoversClass(QuestionManagement::class)]
#[AllowMockObjectsWithoutExpectations]
class QuestionManagementTest extends TestCase
{
    /**
     * @var QuestionRepositoryInterface|MockObject
     */
    private QuestionRepositoryInterface|MockObject $questionRepository;

    /**
     * @var ResourceConnection|MockObject
     */
    private ResourceConnection|MockObject $resourceConnection;

    /**
     * @var AdapterInterface|MockObject
     */
    private AdapterInterface|MockObject $connection;

    /**
     * @var CustomerSession|MockObject
     */
    private CustomerSession|MockObject $customerSession;

    /**
     * @var RemoteAddress|MockObject
     */
    private RemoteAddress|MockObject $remoteAddress;

    /**
     * @var DateTime|MockObject
     */
    private DateTime|MockObject $dateTime;

    /**
     * @var FaqHelper|MockObject
     */
    private FaqHelper|MockObject $faqHelper;

    /**
     * @var QuestionManagement
     */
    private QuestionManagement $sut;

    /**
     * Table names the service resolved through getTableName().
     *
     * @var string[]
     */
    private array $requestedTables = [];

    protected function setUp(): void
    {
        $this->questionRepository = $this->createMock(QuestionRepositoryInterface::class);
        $this->resourceConnection = $this->createMock(ResourceConnection::class);
        $this->connection = $this->createMock(AdapterInterface::class);
        $this->customerSession = $this->createMock(CustomerSession::class);
        $this->remoteAddress = $this->createMock(RemoteAddress::class);
        $this->dateTime = $this->createMock(DateTime::class);
        $this->faqHelper = $this->createMock(FaqHelper::class);
        $this->faqHelper->method('isGuestRatingAllowed')->willReturn(true);

        $this->requestedTables = [];
        $this->resourceConnection->method('getConnection')->willReturn($this->connection);
        $this->resourceConnection->method('getTableName')
            ->willReturnCallback(function (string $table): string {
                $this->requestedTables[] = $table;
                return $table;
            });

        $this->sut = new QuestionManagement(
            $this->questionRepository,
            $this->createStub(ResourceQuestion::class),
            $this->getMockBuilder(QuestionCollectionFactory::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['create'])
                ->getMock(),
            $this->getMockBuilder(QuestionSearchResultsInterfaceFactory::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['create'])
                ->getMock(),
            $this->resourceConnection,
            $this->getMockBuilder(SearchLogFactory::class)
                ->disableOriginalConstructor()
                ->onlyMethods(['create'])
                ->getMock(),
            $this->createStub(SearchLogResource::class),
            $this->dateTime,
            $this->createStub(LoggerInterface::class),
            $this->createStub(EmailSender::class),
            $this->customerSession,
            $this->remoteAddress,
            $this->faqHelper,
            $this->createStub(FilterManager::class),
            $this->createStub(UserContextInterface::class),
            $this->createStub(CustomerRepositoryInterface::class)
        );
    }

    #[DataProvider('invalidVoteTypeProvider')]
    public function testRateQuestionRejectsInvalidVoteType(string $voteType): void
    {
        $this->questionRepository->expects($this->never())->method('getById');
        $this->connection->expects($this->never())->method('insert');

        $this->expectException(LocalizedException::class);
        $this->expectExceptionMessage('Invalid vote type.');

        $this->sut->rateQuestion(11, $voteType);
    }

    /**
     * @return array<string, array{string}>
     */
    public static function invalidVoteTypeProvider(): array
    {
        return [
            'arbitrary string' => ['sideways'],
            'empty string' => [''],
            'wrong case' => ['POSITIVE'],
            'numeric string' => ['1'],
        ];
    }

    public function testRateQuestionPropagatesMissingQuestion(): void
    {
        $this->questionRepository->method('getById')
            ->willThrowException(new NoSuchEntityException(__('No such question.')));
        $this->connection->expects($this->never())->method('insert');

        $this->expectException(NoSuchEntityException::class);

        $this->sut->rateQuestion(404, QuestionManagement::VOTE_POSITIVE);
    }

    public function testDuplicateVoteByCustomerIsRejectedUsingSessionIdentity(): void
    {
        $this->questionRepository->method('getById')
            ->willReturn($this->createStub(QuestionInterface::class));
        $this->customerSession->method('isLoggedIn')->willReturn(true);
        $this->customerSession->method('getCustomerId')->willReturn('42');
        $this->remoteAddress->method('getRemoteAddress')->willReturn('203.0.113.9');

        $whereCalls = [];
        $select = $this->createSelect($whereCalls);
        $this->connection->method('select')->willReturn($select);
        $this->connection->method('fetchOne')->willReturn('99');
        $this->connection->expects($this->never())->method('insert');

        try {
            $this->sut->rateQuestion(11, QuestionManagement::VOTE_POSITIVE);
            $this->fail('Expected LocalizedException for a duplicate vote');
        } catch (LocalizedException $e) {
            $this->assertSame('You have already rated this question.', $e->getMessage());
        }

        // The duplicate check must query the real rating table...
        $this->assertContains('magendoo_faq_rating', $this->requestedTables);
        $this->assertNotContains('magendoo_faq_question_rating', $this->requestedTables);
        // ...keyed on the session customer id, not on anything the caller sent.
        $this->assertContains(['question_id = ?', 11], $whereCalls);
        $this->assertContains(['customer_id = ?', 42], $whereCalls);
        $this->assertNotContains(['ip_address = ?', '203.0.113.9'], $whereCalls);
    }

    public function testDuplicateVoteByGuestIsRejectedUsingRemoteAddress(): void
    {
        $this->questionRepository->method('getById')
            ->willReturn($this->createStub(QuestionInterface::class));
        $this->customerSession->method('isLoggedIn')->willReturn(false);
        $this->remoteAddress->method('getRemoteAddress')->willReturn('203.0.113.9');

        $whereCalls = [];
        $select = $this->createSelect($whereCalls);
        $this->connection->method('select')->willReturn($select);
        $this->connection->method('fetchOne')->willReturn('5');
        $this->connection->expects($this->never())->method('insert');

        try {
            $this->sut->rateQuestion(11, QuestionManagement::VOTE_NEGATIVE);
            $this->fail('Expected LocalizedException for a duplicate vote');
        } catch (LocalizedException $e) {
            $this->assertSame('You have already rated this question.', $e->getMessage());
        }

        $this->assertContains(['ip_address = ?', '203.0.113.9'], $whereCalls);
        foreach ($whereCalls as $call) {
            $this->assertStringNotContainsString('customer_id', (string) $call[0]);
        }
    }

    #[DataProvider('voteTypeProvider')]
    public function testSuccessfulVoteInsertsIntoRatingTableAndUpdatesCounters(
        string $voteType,
        string $expectedCounterColumn
    ): void {
        $this->questionRepository->method('getById')
            ->willReturn($this->createStub(QuestionInterface::class));
        $this->customerSession->method('isLoggedIn')->willReturn(true);
        $this->customerSession->method('getCustomerId')->willReturn('42');
        $this->remoteAddress->method('getRemoteAddress')->willReturn('203.0.113.9');
        $this->dateTime->method('gmtDate')->willReturn('2026-07-25 10:00:00');

        $whereCalls = [];
        $duplicateCheckSelect = $this->createSelect($whereCalls);
        $recalcSelect = $this->createSelect($whereCalls);
        $this->connection->method('select')
            ->willReturnOnConsecutiveCalls($duplicateCheckSelect, $recalcSelect);
        $this->connection->method('fetchOne')->willReturn(false);
        $this->connection->method('fetchRow')
            ->willReturn(['positive_rating' => 3, 'negative_rating' => 1]);

        $inserts = [];
        $this->connection->expects($this->once())
            ->method('insert')
            ->willReturnCallback(function (string $table, array $row) use (&$inserts): int {
                $inserts[] = [$table, $row];
                return 1;
            });

        $updates = [];
        $this->connection->expects($this->once())
            ->method('update')
            ->willReturnCallback(function (string $table, array $bind, $where) use (&$updates): int {
                $updates[] = [$table, $bind, $where];
                return 1;
            });

        $this->assertTrue($this->sut->rateQuestion(11, $voteType));

        // The vote lands in magendoo_faq_rating — not the non-existent
        // magendoo_faq_question_rating a past defect queried.
        [$insertTable, $insertRow] = $inserts[0];
        $this->assertSame('magendoo_faq_rating', $insertTable);
        $this->assertNotContains('magendoo_faq_question_rating', $this->requestedTables);
        $this->assertSame(11, $insertRow['question_id']);
        $this->assertSame(42, $insertRow['customer_id']);
        $this->assertSame('203.0.113.9', $insertRow['ip_address']);
        $this->assertSame($voteType, $insertRow['vote_type']);
        $this->assertSame('2026-07-25 10:00:00', $insertRow['created_at']);

        // First update increments the right counter on the question table.
        [$counterTable, $counterBind, $counterWhere] = $updates[0];
        $this->assertSame('magendoo_faq_question', $counterTable);
        $this->assertArrayHasKey($expectedCounterColumn, $counterBind);
        $this->assertInstanceOf(\Zend_Db_Expr::class, $counterBind[$expectedCounterColumn]);
        $this->assertSame(
            $expectedCounterColumn . ' + 1',
            (string) $counterBind[$expectedCounterColumn]
        );
        $this->assertSame(['question_id = ?' => 11], $counterWhere);

        // A helpful / not-helpful vote only moves its counter. average_rating is no longer
        // written here: it used to be recomputed as percent-positive and then rendered by the
        // template as a score out of 5, so a single positive vote displayed "100.0 / 5".
        // The column is now an average of the 1-5 star values and is written only by star votes.
        $this->assertCount(1, $updates, 'a helpful/not-helpful vote must not touch average_rating');
    }

    /**
     * @return array<string, array{string, string}>
     */
    public static function voteTypeProvider(): array
    {
        return [
            'positive vote increments positive_rating' => [
                QuestionManagement::VOTE_POSITIVE,
                'positive_rating',
            ],
            'negative vote increments negative_rating' => [
                QuestionManagement::VOTE_NEGATIVE,
                'negative_rating',
            ],
        ];
    }

    /**
     * Build a fluent Select mock that records its where() calls.
     *
     * @param array $whereCalls Accumulator for [condition, value] pairs
     * @return Select|MockObject
     */
    private function createSelect(array &$whereCalls): Select|MockObject
    {
        $select = $this->createMock(Select::class);
        $select->method('from')->willReturnSelf();
        $select->method('where')->willReturnCallback(
            // Capture condition + bound value only; where() carries further
            // defaulted parameters ($type) that would pollute the comparison.
            function (...$args) use (&$whereCalls, $select) {
                $whereCalls[] = [$args[0], $args[1] ?? null];
                return $select;
            }
        );

        return $select;
    }
}
