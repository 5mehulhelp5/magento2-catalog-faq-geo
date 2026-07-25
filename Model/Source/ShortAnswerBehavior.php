<?php
/**
 * Magendoo Faq Short Answer Behavior Source
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
 * Source model for "Short Answer Behavior" config field.
 *
 * Controls how the preview of a question's answer is rendered:
 *  - short_answer: Use the question's `short_answer` field.
 *  - cut_full_answer: Truncate the `full_answer` to the configured length.
 */
class ShortAnswerBehavior implements OptionSourceInterface
{
    public const SHORT_ANSWER = 'short_answer';
    public const CUT_FULL_ANSWER = 'cut_full_answer';

    /**
     * @inheritDoc
     */
    public function toOptionArray(): array
    {
        return [
            ['value' => self::SHORT_ANSWER, 'label' => __('Short Answer')],
            ['value' => self::CUT_FULL_ANSWER, 'label' => __('Cut Full Answer')],
        ];
    }
}
