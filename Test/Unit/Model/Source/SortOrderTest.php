<?php
/**
 * Magendoo Faq Sort Order Source Model Test
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Test\Unit\Model\Source;

use Magendoo\Faq\Model\Source\SortOrder;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The emitted option values must match what the consuming code compares
 * against (collection sort field switches on these exact strings).
 */
#[CoversClass(SortOrder::class)]
class SortOrderTest extends TestCase
{
    public function testToOptionArrayEmitsExpectedValues(): void
    {
        $this->assertSame(
            ['position', 'name', 'most_viewed'],
            array_column((new SortOrder())->toOptionArray(), 'value')
        );
    }

    public function testToOptionArrayEntriesCarryValueAndLabel(): void
    {
        foreach ((new SortOrder())->toOptionArray() as $option) {
            $this->assertArrayHasKey('value', $option);
            $this->assertArrayHasKey('label', $option);
            $this->assertNotSame('', (string) $option['label']);
        }
    }

    public function testToArrayKeysMatchOptionValues(): void
    {
        $source = new SortOrder();

        $this->assertSame(
            array_column($source->toOptionArray(), 'value'),
            array_keys($source->toArray())
        );
    }
}
