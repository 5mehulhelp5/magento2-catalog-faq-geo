<?php
/**
 * Magendoo Faq Question URL Rewrite Delete Observer
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Observer\UrlRewrite;

use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Model\UrlRewrite\FaqUrlRewriteGenerator;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Remove URL rewrites when a FAQ question is deleted
 *
 * Without this, deleting a question (admin delete/mass delete, REST DELETE — all go
 * through the resource model, which dispatches magendoo_faq_question_delete_after)
 * leaves the rewrite behind: a permanent soft-404 that also keeps the unique
 * request_path + store_id slot occupied, so the url_key can never be reused.
 */
class QuestionDeleteObserver implements ObserverInterface
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
            $this->urlRewriteGenerator->deleteForEntity(
                FaqUrlRewriteGenerator::ENTITY_TYPE_QUESTION,
                (int) $question->getQuestionId()
            );
        }
    }
}
