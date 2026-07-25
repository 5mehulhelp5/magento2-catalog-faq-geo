<?php
/**
 * Magendoo Faq Question Rating Block
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Block\Faq\Question;

use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * FAQ Question Rating Block
 *
 * Renders the rating widget for a FAQ question using one of three modes:
 *  - yes_no:         simple "Was this helpful? Yes/No" buttons
 *  - voting:         thumbs-up / thumbs-down voting buttons
 *  - average_rating: 5-star average rating display
 */
class Rating extends Template implements IdentityInterface
{
    /**
     * Rating type identifiers.
     */
    public const TYPE_YES_NO = 'yes_no';
    public const TYPE_VOTING = 'voting';
    public const TYPE_AVERAGE_RATING = 'average_rating';

    /**
     * @var FaqHelper
     */
    private FaqHelper $helper;

    /**
     * @param Context $context
     * @param FaqHelper $helper
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        FaqHelper $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Check if rating is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->helper->isRatingEnabled();
    }

    /**
     * Get the configured rating type.
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->helper->getRatingType();
    }

    /**
     * Get the current question.
     *
     * Falls back to the parent block's question if not set directly on this block.
     *
     * @return QuestionInterface|null
     */
    public function getQuestion(): ?QuestionInterface
    {
        $question = $this->getData('question');
        if ($question instanceof QuestionInterface) {
            return $question;
        }

        $parent = $this->getParentBlock();
        if ($parent && method_exists($parent, 'getQuestion')) {
            $parentQuestion = $parent->getQuestion();
            if ($parentQuestion instanceof QuestionInterface) {
                return $parentQuestion;
            }
        }

        return null;
    }

    /**
     * Get the URL used to submit a rating vote.
     *
     * @return string
     */
    public function getRateUrl(): string
    {
        return $this->getUrl('faq/question/rate');
    }

    /**
     * Get positive vote count for the current question.
     *
     * @return int
     */
    public function getPositiveCount(): int
    {
        $question = $this->getQuestion();

        return $question ? (int) $question->getPositiveRating() : 0;
    }

    /**
     * Get negative vote count for the current question.
     *
     * @return int
     */
    public function getNegativeCount(): int
    {
        $question = $this->getQuestion();

        return $question ? (int) $question->getNegativeRating() : 0;
    }

    /**
     * Get the average rating for the current question.
     *
     * @return float
     */
    public function getAverageRating(): float
    {
        $question = $this->getQuestion();

        return $question ? (float) $question->getAverageRating() : 0.0;
    }

    /**
     * Return identifiers for produced content
     *
     * The rendered vote counts / average belong to the question, so its identities
     * make sure the cached page is purged when a vote changes the numbers.
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        $question = $this->getQuestion();

        return $question instanceof IdentityInterface ? $question->getIdentities() : [];
    }

    /**
     * Get the template file to use for the current rating type.
     *
     * @return string
     */
    public function getTemplateForType(): string
    {
        switch ($this->getType()) {
            case self::TYPE_VOTING:
                return 'Magendoo_Faq::question/rating/voting.phtml';
            case self::TYPE_AVERAGE_RATING:
                return 'Magendoo_Faq::question/rating/average.phtml';
            case self::TYPE_YES_NO:
            default:
                return 'Magendoo_Faq::question/rating/yes-no.phtml';
        }
    }

    /**
     * Set the template based on the configured rating type before rendering.
     *
     * @return string
     */
    protected function _toHtml()
    {
        if (!$this->isEnabled() || !$this->getQuestion()) {
            return '';
        }

        $this->setTemplate($this->getTemplateForType());

        return parent::_toHtml();
    }
}
