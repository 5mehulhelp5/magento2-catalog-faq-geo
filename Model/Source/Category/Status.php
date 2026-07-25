<?php
/**
 * Magendoo Faq Category Status Source Model
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model\Source\Category;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Category status source model
 */
class Status implements OptionSourceInterface
{
    /**
     * Status values
     */
    public const ENABLED = 1;
    public const DISABLED = 0;

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::ENABLED,
                'label' => __('Enabled')
            ],
            [
                'value' => self::DISABLED,
                'label' => __('Disabled')
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
            self::ENABLED => __('Enabled'),
            self::DISABLED => __('Disabled')
        ];
    }
}
