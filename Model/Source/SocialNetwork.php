<?php
/**
 * Magendoo Faq Social Network Source Model
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
 * Social network source model
 */
class SocialNetwork implements OptionSourceInterface
{
    /**
     * Social network values
     */
    public const FACEBOOK = 'facebook';
    public const TWITTER = 'twitter';
    public const LINKEDIN = 'linkedin';
    public const PINTEREST = 'pinterest';
    public const EMAIL = 'email';

    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => self::FACEBOOK,
                'label' => __('Facebook')
            ],
            [
                'value' => self::TWITTER,
                'label' => __('Twitter')
            ],
            [
                'value' => self::LINKEDIN,
                'label' => __('LinkedIn')
            ],
            [
                'value' => self::PINTEREST,
                'label' => __('Pinterest')
            ],
            [
                'value' => self::EMAIL,
                'label' => __('Email')
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
            self::FACEBOOK => __('Facebook'),
            self::TWITTER => __('Twitter'),
            self::LINKEDIN => __('LinkedIn'),
            self::PINTEREST => __('Pinterest'),
            self::EMAIL => __('Email')
        ];
    }
}
