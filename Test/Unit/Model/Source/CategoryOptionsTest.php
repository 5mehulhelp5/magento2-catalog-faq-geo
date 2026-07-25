<?php
/**
 * Magendoo Faq Category Options Source Test
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Test\Unit\Model\Source;

use Magendoo\Faq\Model\Category as CategoryModel;
use Magendoo\Faq\Model\ResourceModel\Category\Collection;
use Magendoo\Faq\Model\ResourceModel\Category\CollectionFactory;
use Magendoo\Faq\Model\Source\CategoryOptions;
use Magento\Framework\TestFramework\Unit\Helper\ObjectManager as ObjectManagerHelper;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The multiselect on the question admin form must receive int values (the
 * saved assignment rows join on category_id) with human-readable labels.
 */
#[CoversClass(CategoryOptions::class)]
#[AllowMockObjectsWithoutExpectations]
class CategoryOptionsTest extends TestCase
{
    public function testToOptionArrayBuildsIntValueStringLabelPairs(): void
    {
        $objectManagerHelper = new ObjectManagerHelper($this);
        $categories = [];
        foreach ([['7', 'Shipping'], ['12', 'Returns']] as [$id, $name]) {
            /** @var CategoryModel $category */
            $category = $objectManagerHelper->getObject(CategoryModel::class);
            $category->setData(['category_id' => $id, 'name' => $name]);
            $categories[] = $category;
        }

        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToSelect', 'setOrder', 'getIterator'])
            ->getMock();
        $collection->method('addFieldToSelect')->willReturnSelf();
        $orderCalls = [];
        $collection->method('setOrder')->willReturnCallback(
            function (string $field, string $direction) use (&$orderCalls, $collection) {
                $orderCalls[] = [$field, $direction];
                return $collection;
            }
        );
        $collection->method('getIterator')->willReturn(new \ArrayIterator($categories));

        $collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $collectionFactory->method('create')->willReturn($collection);

        $options = (new CategoryOptions($collectionFactory))->toOptionArray();

        $this->assertSame(
            [
                ['value' => 7, 'label' => 'Shipping'],
                ['value' => 12, 'label' => 'Returns'],
            ],
            $options
        );
        $this->assertSame([['position', 'ASC'], ['name', 'ASC']], $orderCalls);
    }

    public function testToOptionArrayWithNoCategoriesIsEmpty(): void
    {
        $collection = $this->getMockBuilder(Collection::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['addFieldToSelect', 'setOrder', 'getIterator'])
            ->getMock();
        $collection->method('addFieldToSelect')->willReturnSelf();
        $collection->method('setOrder')->willReturnSelf();
        $collection->method('getIterator')->willReturn(new \ArrayIterator([]));

        $collectionFactory = $this->getMockBuilder(CollectionFactory::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['create'])
            ->getMock();
        $collectionFactory->method('create')->willReturn($collection);

        $this->assertSame([], (new CategoryOptions($collectionFactory))->toOptionArray());
    }
}
