<?php
/**
 * Magendoo Faq Rating Type Source Model
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model\Source\Rating;

use Magento\Framework\Data\OptionSourceInterface;

/**
 * Rating type source model
 */
class Type implements OptionSourceInterface
{
    /**
     * Rating type values
     */
    public const YES_NO = 'yes_no';
    public const VOTING = 'voting';
    public const AVERAGE_RATING = 'average_rating';

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::YES_NO,
                'label' => __('Yes / No')
            ],
            [
                'value' => self::VOTING,
                'label' => __('Voting')
            ],
            [
                'value' => self::AVERAGE_RATING,
                'label' => __('Average Rating')
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
            self::YES_NO => __('Yes / No'),
            self::VOTING => __('Voting'),
            self::AVERAGE_RATING => __('Average Rating')
        ];
    }
}
