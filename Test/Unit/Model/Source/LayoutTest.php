<?php
/**
 * Magendoo Faq Layout Source Model Test
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Test\Unit\Model\Source;

use Magendoo\Faq\Model\Source\Layout;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Layout handles are chosen off these exact values.
 */
#[CoversClass(Layout::class)]
class LayoutTest extends TestCase
{
    public function testToOptionArrayEmitsExpectedValues(): void
    {
        $this->assertSame(
            ['sidebar_left', 'sidebar_right', 'none'],
            array_column((new Layout())->toOptionArray(), 'value')
        );
    }

    public function testToArrayKeysMatchOptionValues(): void
    {
        $source = new Layout();

        $this->assertSame(
            array_column($source->toOptionArray(), 'value'),
            array_keys($source->toArray())
        );
    }
}
