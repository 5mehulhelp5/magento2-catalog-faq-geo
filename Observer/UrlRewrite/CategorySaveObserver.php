<?php
/**
 * Magendoo Faq Category URL Rewrite Observer
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Observer\UrlRewrite;

use Magendoo\Faq\Api\Data\CategoryInterface;
use Magendoo\Faq\Model\UrlRewrite\FaqUrlRewriteGenerator;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;

/**
 * Regenerate URL rewrites when a FAQ category is saved
 */
class CategorySaveObserver implements ObserverInterface
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
        /** @var CategoryInterface $category */
        $category = $observer->getEvent()->getData('faq_category');
        if (!$category) {
            $category = $observer->getEvent()->getData('object');
        }

        if ($category instanceof CategoryInterface && $category->getCategoryId()) {
            // A disabled category 404s on the frontend, so keeping (or creating) its
            // rewrite would only produce soft-404s and block the url_key for reuse.
            if ((int) $category->getStatus() !== CategoryInterface::STATUS_ENABLED) {
                $this->urlRewriteGenerator->deleteForEntity(
                    FaqUrlRewriteGenerator::ENTITY_TYPE_CATEGORY,
                    (int) $category->getCategoryId()
                );
                return;
            }

            $this->urlRewriteGenerator->generateForCategory($category);
        }
    }
}
