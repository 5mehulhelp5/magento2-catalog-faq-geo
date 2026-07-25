<?php
/**
 * Magendoo Faq Layout Source Model
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model\Source;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Layout source model
 */
class Layout implements OptionSourceInterface
{
    /**
     * Layout values
     */
    public const SIDEBAR_LEFT = 'sidebar_left';
    public const SIDEBAR_RIGHT = 'sidebar_right';
    public const NONE = 'none';

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::SIDEBAR_LEFT,
                'label' => __('Sidebar Left')
            ],
            [
                'value' => self::SIDEBAR_RIGHT,
                'label' => __('Sidebar Right')
            ],
            [
                'value' => self::NONE,
                'label' => __('No Sidebar')
            ]
        ];
    }

    /**
     * Get options as array (value => label)
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            self::SIDEBAR_LEFT => __('Sidebar Left'),
            self::SIDEBAR_RIGHT => __('Sidebar Right'),
            self::NONE => __('No Sidebar')
        ];
    }
}
