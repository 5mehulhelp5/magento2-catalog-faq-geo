<?php
/**
 * Magendoo Faq Category URL Rewrite Delete Observer
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
 * Remove URL rewrites when a FAQ category is deleted
 *
 * Without this, deleting a category (admin delete/mass delete, REST DELETE — all go
 * through the resource model, which dispatches magendoo_faq_category_delete_after)
 * leaves the rewrite behind: a permanent soft-404 that also keeps the unique
 * request_path + store_id slot occupied, so the url_key can never be reused.
 */
class CategoryDeleteObserver implements ObserverInterface
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
            $this->urlRewriteGenerator->deleteForEntity(
                FaqUrlRewriteGenerator::ENTITY_TYPE_CATEGORY,
                (int) $category->getCategoryId()
            );
        }
    }
}
