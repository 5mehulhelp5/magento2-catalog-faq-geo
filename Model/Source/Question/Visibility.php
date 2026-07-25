<?php
/**
 * Magendoo Faq Question Visibility Source Model
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model\Source\Question;

use Magendoo\Faq\Api\Data\QuestionInterface;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Question visibility source model
 */
class Visibility implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => QuestionInterface::VISIBILITY_NONE,
                'label' => __('None')
            ],
            [
                'value' => QuestionInterface::VISIBILITY_PUBLIC,
                'label' => __('Public')
            ],
            [
                'value' => QuestionInterface::VISIBILITY_LOGGED_IN,
                'label' => __('Logged In Only')
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
            QuestionInterface::VISIBILITY_NONE => __('None'),
            QuestionInterface::VISIBILITY_PUBLIC => __('Public'),
            QuestionInterface::VISIBILITY_LOGGED_IN => __('Logged In Only')
        ];
    }
}
