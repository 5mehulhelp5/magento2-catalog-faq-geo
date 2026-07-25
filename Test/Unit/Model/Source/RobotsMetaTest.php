<?php
/**
 * Magendoo Faq Robots Meta Source Model Test
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Test\Unit\Model\Source;

use Magendoo\Faq\Model\Source\RobotsMeta;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * These values are rendered verbatim into the robots meta tag, so they must
 * be the canonical comma-separated directives.
 */
#[CoversClass(RobotsMeta::class)]
class RobotsMetaTest extends TestCase
{
    public function testToOptionArrayEmitsCanonicalRobotsDirectives(): void
    {
        $this->assertSame(
            ['INDEX,FOLLOW', 'NOINDEX,FOLLOW', 'INDEX,NOFOLLOW', 'NOINDEX,NOFOLLOW'],
            array_column((new RobotsMeta())->toOptionArray(), 'value')
        );
    }

    public function testToArrayKeysMatchOptionValues(): void
    {
        $source = new RobotsMeta();

        $this->assertSame(
            array_column($source->toOptionArray(), 'value'),
            array_keys($source->toArray())
        );
    }
}
