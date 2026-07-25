<?php
/**
 * Magendoo Faq Social Network Source Model Test
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Test\Unit\Model\Source;

use Magendoo\Faq\Model\Source\SocialNetwork;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;

/**
 * The multiselect stores these values as a CSV that the share-buttons
 * template switches on, so the codes must stay stable.
 */
#[CoversClass(SocialNetwork::class)]
class SocialNetworkTest extends TestCase
{
    public function testToOptionArrayEmitsExpectedNetworkCodes(): void
    {
        $this->assertSame(
            ['facebook', 'twitter', 'linkedin', 'pinterest', 'email'],
            array_column((new SocialNetwork())->toOptionArray(), 'value')
        );
    }

    public function testToArrayKeysMatchOptionValues(): void
    {
        $source = new SocialNetwork();

        $this->assertSame(
            array_column($source->toOptionArray(), 'value'),
            array_keys($source->toArray())
        );
    }
}
