<?php
/**
 * Magendoo Faq Question Status Source Model
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
 * Question status source model
 */
class Status implements OptionSourceInterface
{
    /**
     * @inheritdoc
     */
    public function toOptionArray(): array
    {
        return [
            [
                'value' => QuestionInterface::STATUS_PENDING,
                'label' => __('Pending')
            ],
            [
                'value' => QuestionInterface::STATUS_ANSWERED,
                'label' => __('Answered')
            ],
            [
                'value' => QuestionInterface::STATUS_REJECTED,
                'label' => __('Rejected')
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
            QuestionInterface::STATUS_PENDING => __('Pending'),
            QuestionInterface::STATUS_ANSWERED => __('Answered'),
            QuestionInterface::STATUS_REJECTED => __('Rejected')
        ];
    }
}
