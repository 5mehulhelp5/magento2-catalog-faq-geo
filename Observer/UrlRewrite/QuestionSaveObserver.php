<?php
/**
 * Magendoo Faq Question URL Rewrite Observer
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Observer\UrlRewrite;

use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Model\UrlRewrite\FaqUrlRewriteGenerator;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Regenerate URL rewrites when a FAQ question is saved
 */
class QuestionSaveObserver implements ObserverInterface
{
    /**
     * @var FaqUrlRewriteGenerator
     */
    private FaqUrlRewriteGenerator $urlRewriteGenerator;

    /**
     * @param FaqUrlRewriteGenerator $urlRewriteGenerator
     */
    public function __construct(
        FaqUrlRewriteGenerator $urlRewriteGenerator
    ) {
        $this->urlRewriteGenerator = $urlRewriteGenerator;
    }

    /**
     * Execute observer
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var QuestionInterface $question */
        $question = $observer->getEvent()->getData('faq_question');
        if (!$question) {
            $question = $observer->getEvent()->getData('object');
        }

        if ($question instanceof QuestionInterface && $question->getQuestionId()) {
            // Only answered questions are visible on the frontend. Generating rewrites
            // for pending/spam questions (e.g. guest submissions) creates soft-404s and
            // lets an unmoderated title occupy a request_path slot; dropping the rewrite
            // here also covers the answered -> pending/spam transition.
            if ($question->getStatus() !== QuestionInterface::STATUS_ANSWERED) {
                $this->urlRewriteGenerator->deleteForEntity(
                    FaqUrlRewriteGenerator::ENTITY_TYPE_QUESTION,
                    (int) $question->getQuestionId()
                );
                return;
            }

            $this->urlRewriteGenerator->generateForQuestion($question);
        }
    }
}
