<?php
/**
 * Magendoo Faq Short Answer Behavior Source Model Test
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Test\Unit\Model\Source;

use Magendoo\Faq\Model\Source\ShortAnswerBehavior;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * Templates compare the stored config value against these exact strings.
 */
#[CoversClass(ShortAnswerBehavior::class)]
class ShortAnswerBehaviorTest extends TestCase
{
    public function testToOptionArrayEmitsExpectedValues(): void
    {
        $this->assertSame(
            ['short_answer', 'cut_full_answer'],
            array_column((new ShortAnswerBehavior())->toOptionArray(), 'value')
        );
    }
}
