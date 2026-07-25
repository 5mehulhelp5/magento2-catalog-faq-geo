<?php
/**
 * Magendoo Faq Question Resource Tag Normalization Test
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Test\Unit\Model\ResourceModel;

use Magendoo\Faq\Model\ResourceModel\Question;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Guards the rule that only real ids reach magendoo_faq_question_tag.
 *
 * A tag NAME reaching this path used to cast to 0 and violate the junction's foreign key,
 * which aborted the whole question save. That is what made an exported CSV impossible to
 * re-import: the export writes tag names, and nothing resolved them back to ids.
 */
#[CoversClass(Question::class)]
class QuestionTagNormalizationTest extends TestCase
{
    /**
     * @param mixed $input
     * @param int[] $expected
     */
    #[DataProvider('tagIdProvider')]
    public function testOnlyPositiveIntegerIdsSurvive(mixed $input, array $expected): void
    {
        $method = new \ReflectionMethod(Question::class, 'normalizeTagIds');

        // The resource model's constructor pulls a full Db context; the normalization itself
        // is pure, so exercise it without instantiating the object graph.
        $resource = (new \ReflectionClass(Question::class))->newInstanceWithoutConstructor();

        $this->assertSame($expected, $method->invoke($resource, $input));
    }

    /**
     * @return array<string, array{mixed, int[]}>
     */
    public static function tagIdProvider(): array
    {
        return [
            'real ids pass through'            => [[1, 2, 3], [1, 2, 3]],
            'numeric strings are cast'         => [['1', '2'], [1, 2]],
            'a tag NAME is dropped, not zeroed' => [['Delivery'], []],
            'mixed names and ids keep the ids' => [[1, 'Delivery', 2], [1, 2]],
            'a comma-joined name list is dropped' => [['Delivery,Returns'], []],
            'a bare string is dropped'         => ['Delivery', []],
            'zero is not an id'                => [[0, 1], [1]],
            'negatives are not ids'            => [[-1, 5], [5]],
            'duplicates collapse'              => [[2, 2, 3], [2, 3]],
            'null yields nothing'              => [[null], []],
            'empty stays empty'                => [[], []],
        ];
    }
}
