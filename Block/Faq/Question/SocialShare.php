<?php
/**
 * Magendoo Faq Question Social Share Block
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
use Magento\Framework\Url\EncoderInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * FAQ Question Social Share Block
 *
 * Renders social share buttons for the current FAQ question.
 */
class SocialShare extends Template implements IdentityInterface
{
    /**
     * Supported network identifiers.
     */
    public const NETWORK_FACEBOOK = 'facebook';
    public const NETWORK_TWITTER = 'twitter';
    public const NETWORK_LINKEDIN = 'linkedin';
    public const NETWORK_PINTEREST = 'pinterest';
    public const NETWORK_EMAIL = 'email';

    /**
     * @var FaqHelper
     */
    private FaqHelper $helper;

    /**
     * @var EncoderInterface
     */
    private EncoderInterface $urlEncoder;

    /**
     * @param Context $context
     * @param FaqHelper $helper
     * @param EncoderInterface $urlEncoder
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        FaqHelper $helper,
        EncoderInterface $urlEncoder,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->urlEncoder = $urlEncoder;
        parent::__construct($context, $data);
    }

    /**
     * Check whether social sharing is enabled.
     *
     * @return bool
     */
    public function isEnabled(): bool
    {
        return $this->helper->isSocialEnabled();
    }

    /**
     * Get the list of enabled social networks.
     *
     * @return string[]
     */
    public function getNetworks(): array
    {
        return $this->helper->getSocialNetworks();
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
     * Return identifiers for produced content
     *
     * The share links carry the question's title and URL, so the block shares the
     * question's identities.
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        $question = $this->getQuestion();

        return $question instanceof IdentityInterface ? $question->getIdentities() : [];
    }

    /**
     * Get the absolute URL of the current question page.
     *
     * @return string
     */
    public function getQuestionUrl(): string
    {
        $question = $this->getQuestion();
        if (!$question) {
            return '';
        }

        return $this->getUrl(
            'faq/question/view',
            ['id' => (int) $question->getQuestionId(), '_secure' => $this->getRequest()->isSecure()]
        );
    }

    /**
     * Get the share URL for the given social network.
     *
     * @param string $network
     * @return string
     */
    public function getShareUrl(string $network): string
    {
        $question = $this->getQuestion();
        if (!$question) {
            return '';
        }

        $url = $this->getQuestionUrl();
        $title = (string) $question->getTitle();
        $encodedUrl = rawurlencode($url);
        $encodedTitle = rawurlencode($title);

        switch ($network) {
            case self::NETWORK_FACEBOOK:
                return 'https://www.facebook.com/sharer/sharer.php?u=' . $encodedUrl;
            case self::NETWORK_TWITTER:
                return 'https://twitter.com/intent/tweet?url=' . $encodedUrl . '&text=' . $encodedTitle;
            case self::NETWORK_LINKEDIN:
                return 'https://www.linkedin.com/shareArticle?mini=true&url=' . $encodedUrl
                    . '&title=' . $encodedTitle;
            case self::NETWORK_PINTEREST:
                return 'https://pinterest.com/pin/create/button/?url=' . $encodedUrl
                    . '&description=' . $encodedTitle;
            case self::NETWORK_EMAIL:
                return 'mailto:?subject=' . $encodedTitle . '&body=' . $encodedUrl;
            default:
                return '';
        }
    }
}
