<?php
/**
 * Magendoo Faq URL Rewrite Generator Test
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Test\Unit\Model\UrlRewrite;

use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\Category as CategoryModel;
use Magendoo\Faq\Model\Question as QuestionModel;
use Magendoo\Faq\Model\ResourceModel\Category as CategoryResource;
use Magendoo\Faq\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magendoo\Faq\Model\ResourceModel\Question as QuestionResource;
use Magendoo\Faq\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;
use Magendoo\Faq\Model\UrlRewrite\FaqUrlRewriteGenerator;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use Magento\Store\Api\Data\StoreInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Pins the store-scoping behaviour of the FAQ URL rewrite generator.
 *
 * The most valuable case here is the "All Store Views" expansion: an admin
 * form POST delivers store ids as strings ("0"), and a strict in_array(0, ["0"], true)
 * check silently wrote rewrites with store_id = 0 that Magento's router never
 * matched. These tests fail on any regression back to strict-int matching.
 */
#[CoversClass(FaqUrlRewriteGenerator::class)]
#[AllowMockObjectsWithoutExpectations]
class FaqUrlRewriteGeneratorTest extends TestCase
{
    /**
     * Store ids returned by the store manager (the "all stores" universe).
     */
    private const ALL_STORE_IDS = [1, 3];

    /**
     * @var UrlPersistInterface|MockObject
     */
    private UrlPersistInterface|MockObject $urlPersist;

    /**
     * @var FaqHelper|MockObject
     */
    private FaqHelper|MockObject $helper;

    /**
     * @var FaqUrlRewriteGenerator
     */
    private FaqUrlRewriteGenerator $generator;

    /**
     * @var ObjectManagerHelper
     */
    private ObjectManagerHelper $objectManagerHelper;

    protected function setUp(): void
    {
        $this->objectManagerHelper = new ObjectManagerHelper($this);

        $this->urlPersist = $this->createMock(UrlPersistInterface::class);
        $this->helper = $this->createMock(FaqHelper::class);

        $urlRewriteFactory = $this->getMockBuilder(UrlRewriteFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $serializer = new Json();
        $urlRewriteFactory->method('create')
            ->willReturnCallback(static fn (): UrlRewrite => new UrlRewrite([], $serializer));

        $storeManager = $this->createStub(StoreManagerInterface::class);
        $stores = [];
        foreach (self::ALL_STORE_IDS as $storeId) {
            $store = $this->createStub(StoreInterface::class);
            $store->method('getId')->willReturn($storeId);
            $stores[] = $store;
        }
        $storeManager->method('getStores')->willReturn($stores);

        $categoryCollectionFactory = $this->getMockBuilder(CategoryCollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $questionCollectionFactory = $this->getMockBuilder(QuestionCollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();

        $this->generator = new FaqUrlRewriteGenerator(
            $this->urlPersist,
            $urlRewriteFactory,
            $storeManager,
            $this->helper,
            $categoryCollectionFactory,
            $questionCollectionFactory,
            $this->createStub(CategoryResource::class),
            $this->createStub(QuestionResource::class)
        );
    }

    /**
     * Regression guard for the "All Store Views" store id handling.
     *
     * @param mixed $storeIds Raw store_ids data as an admin POST or a lookup delivers it
     * @param int[] $expectedStoreIds Store ids the persisted rewrites must carry
     */
    #[DataProvider('storeIdsProvider')]
    public function testGenerateForCategoryExpandsAndNormalisesStoreIds(mixed $storeIds, array $expectedStoreIds): void
    {
        $this->configureHelper(false);
        $category = $this->createCategory(7, 'shipping', $storeIds);

        $captured = [];
        $this->urlPersist->expects($this->once())
            ->method('replace')
            ->willReturnCallback(static function (array $urls) use (&$captured): void {
                $captured = $urls;
            });

        $this->generator->generateForCategory($category);

        $actualStoreIds = array_map(
            static fn (UrlRewrite $url): mixed => $url->getStoreId(),
            $captured
        );
        $this->assertSame($expectedStoreIds, $actualStoreIds);
    }

    /**
     * @return array<string, array{mixed, int[]}>
     */
    public static function storeIdsProvider(): array
    {
        return [
            'string zero from admin POST expands to all stores' => [['0'], self::ALL_STORE_IDS],
            'int zero expands to all stores' => [[0], self::ALL_STORE_IDS],
            'string ids are cast to real int store ids' => [['1', '3'], [1, 3]],
            'int ids pass through' => [[3], [3]],
            'zero mixed with real ids still expands to all stores' => [['0', '3'], self::ALL_STORE_IDS],
            'empty array falls back to all stores' => [[], self::ALL_STORE_IDS],
            'absent store_ids falls back to all stores' => [null, self::ALL_STORE_IDS],
        ];
    }

    /**
     * A rewrite persisted with store_id = 0 is dead: the router looks rewrites
     * up by the current (real) store id. Whatever the input, 0 must never survive.
     */
    #[DataProvider('storeIdsProvider')]
    public function testNoRewriteIsEverPersistedWithStoreIdZero(mixed $storeIds, array $expectedStoreIds): void
    {
        $this->configureHelper(false);
        $category = $this->createCategory(7, 'shipping', $storeIds);

        $this->urlPersist->expects($this->once())
            ->method('replace')
            ->willReturnCallback(function (array $urls) use ($expectedStoreIds): void {
                $this->assertCount(count($expectedStoreIds), $urls);
                foreach ($urls as $url) {
                    $this->assertNotSame(0, $url->getStoreId());
                    $this->assertNotSame('0', $url->getStoreId());
                }
            });

        $this->generator->generateForCategory($category);
    }

    public function testGenerateForCategoryBuildsRequestAndTargetPaths(): void
    {
        $this->configureHelper(true, 'faq', '.html');
        $category = $this->createCategory(7, 'shipping', [1]);

        $captured = [];
        $this->urlPersist->expects($this->once())
            ->method('replace')
            ->willReturnCallback(static function (array $urls) use (&$captured): void {
                $captured = $urls;
            });

        $this->generator->generateForCategory($category);

        $this->assertCount(1, $captured);
        $rewrite = $captured[0];
        $this->assertSame('faq/shipping.html', $rewrite->getRequestPath());
        $this->assertSame('faq/category/view/id/7', $rewrite->getTargetPath());
        $this->assertSame('faq-category', $rewrite->getEntityType());
        $this->assertSame(7, $rewrite->getEntityId());
        $this->assertSame(1, $rewrite->getStoreId());
    }

    public function testGenerateForCategoryOmitsSuffixWhenDisabled(): void
    {
        $this->configureHelper(false, 'help');
        $category = $this->createCategory(7, 'shipping', [1]);

        $captured = [];
        $this->urlPersist->expects($this->once())
            ->method('replace')
            ->willReturnCallback(static function (array $urls) use (&$captured): void {
                $captured = $urls;
            });

        $this->generator->generateForCategory($category);

        $this->assertSame('help/shipping', $captured[0]->getRequestPath());
    }

    public function testGenerateForCategoryDeletesExistingRewritesFirst(): void
    {
        $this->configureHelper(false);
        $category = $this->createCategory(7, 'shipping', [1]);

        $this->urlPersist->expects($this->once())
            ->method('deleteByData')
            ->with([
                UrlRewrite::ENTITY_TYPE => 'faq-category',
                UrlRewrite::ENTITY_ID => 7,
            ]);

        $this->generator->generateForCategory($category);
    }

    public function testGenerateForCategoryDoesNothingWithoutUrlKey(): void
    {
        $category = $this->createCategory(7, null, [1]);

        $this->urlPersist->expects($this->never())->method('deleteByData');
        $this->urlPersist->expects($this->never())->method('replace');

        $this->generator->generateForCategory($category);
    }

    public function testGenerateForCategoryDoesNothingWithoutCategoryId(): void
    {
        $category = $this->createCategory(null, 'shipping', [1]);

        $this->urlPersist->expects($this->never())->method('deleteByData');
        $this->urlPersist->expects($this->never())->method('replace');

        $this->generator->generateForCategory($category);
    }

    public function testGenerateForQuestionNormalisesStringStoreIds(): void
    {
        $this->configureHelper(false);
        $question = $this->createQuestion(9, 'how-to-return', ['1', '3']);

        $captured = [];
        $this->urlPersist->expects($this->once())
            ->method('replace')
            ->willReturnCallback(static function (array $urls) use (&$captured): void {
                $captured = $urls;
            });

        $this->generator->generateForQuestion($question);

        $this->assertSame(
            [1, 3],
            array_map(static fn (UrlRewrite $url): mixed => $url->getStoreId(), $captured)
        );
        $this->assertSame('faq-question', $captured[0]->getEntityType());
        $this->assertSame('faq/question/view/id/9', $captured[0]->getTargetPath());
    }

    public function testGenerateForQuestionExpandsStringZeroToAllStores(): void
    {
        $this->configureHelper(false);
        $question = $this->createQuestion(9, 'how-to-return', ['0']);

        $captured = [];
        $this->urlPersist->expects($this->once())
            ->method('replace')
            ->willReturnCallback(static function (array $urls) use (&$captured): void {
                $captured = $urls;
            });

        $this->generator->generateForQuestion($question);

        $this->assertSame(
            self::ALL_STORE_IDS,
            array_map(static fn (UrlRewrite $url): mixed => $url->getStoreId(), $captured)
        );
    }

    /**
     * Configure the helper stub for one test run.
     *
     * @param bool $suffixEnabled
     * @param string $prefix
     * @param string $suffix
     * @return void
     */
    private function configureHelper(bool $suffixEnabled, string $prefix = 'faq', string $suffix = '.html'): void
    {
        $this->helper->method('getUrlPrefix')->willReturn($prefix);
        $this->helper->method('isUrlSuffixEnabled')->willReturn($suffixEnabled);
        $this->helper->method('getUrlSuffix')->willReturn($suffix);
    }

    /**
     * Build a real Category model (DataObject semantics for getData('store_ids')).
     *
     * @param int|null $categoryId
     * @param string|null $urlKey
     * @param mixed $storeIds
     * @return CategoryModel
     */
    private function createCategory(?int $categoryId, ?string $urlKey, mixed $storeIds): CategoryModel
    {
        /** @var CategoryModel $category */
        $category = $this->objectManagerHelper->getObject(CategoryModel::class);
        $category->setData('category_id', $categoryId);
        $category->setData('url_key', $urlKey);
        if ($storeIds !== null) {
            $category->setData('store_ids', $storeIds);
        }

        return $category;
    }

    /**
     * Build a real Question model.
     *
     * @param int|null $questionId
     * @param string|null $urlKey
     * @param mixed $storeIds
     * @return QuestionModel
     */
    private function createQuestion(?int $questionId, ?string $urlKey, mixed $storeIds): QuestionModel
    {
        /** @var QuestionModel $question */
        $question = $this->objectManagerHelper->getObject(QuestionModel::class);
        $question->setData('question_id', $questionId);
        $question->setData('url_key', $urlKey);
        if ($storeIds !== null) {
            $question->setData('store_ids', $storeIds);
        }

        return $question;
    }
}
