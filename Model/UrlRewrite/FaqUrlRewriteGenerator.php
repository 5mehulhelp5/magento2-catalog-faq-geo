<?php
/**
 * Magendoo Faq URL Rewrite Generator
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model\UrlRewrite;

use Magendoo\Faq\Api\Data\CategoryInterface;
use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\ResourceModel\Category as CategoryResource;
use Magendoo\Faq\Model\ResourceModel\Category\CollectionFactory as CategoryCollectionFactory;
use Magendoo\Faq\Model\ResourceModel\Question as QuestionResource;
use Magendoo\Faq\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;
use Magento\Framework\Exception\AlreadyExistsException;
use Magento\Store\Model\Store;
use Magento\Store\Model\StoreManagerInterface;
use Magento\UrlRewrite\Model\Exception\UrlAlreadyExistsException;
use Magento\UrlRewrite\Model\UrlPersistInterface;
use Magento\UrlRewrite\Service\V1\Data\UrlRewrite;
use Magento\UrlRewrite\Service\V1\Data\UrlRewriteFactory;

/**
 * Generates URL rewrites for FAQ categories and questions
 */
class FaqUrlRewriteGenerator
{
    /**
     * Entity type constants
     */
    public const ENTITY_TYPE_CATEGORY = 'faq-category';
    public const ENTITY_TYPE_QUESTION = 'faq-question';

    /**
     * @var UrlPersistInterface
     */
    private UrlPersistInterface $urlPersist;

    /**
     * @var UrlRewriteFactory
     */
    private UrlRewriteFactory $urlRewriteFactory;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var FaqHelper
     */
    private FaqHelper $helper;

    /**
     * @var CategoryCollectionFactory
     */
    private CategoryCollectionFactory $categoryCollectionFactory;

    /**
     * @var QuestionCollectionFactory
     */
    private QuestionCollectionFactory $questionCollectionFactory;

    /**
     * @var CategoryResource
     */
    private CategoryResource $categoryResource;

    /**
     * @var QuestionResource
     */
    private QuestionResource $questionResource;

    /**
     * @param UrlPersistInterface $urlPersist
     * @param UrlRewriteFactory $urlRewriteFactory
     * @param StoreManagerInterface $storeManager
     * @param FaqHelper $helper
     * @param CategoryCollectionFactory $categoryCollectionFactory
     * @param QuestionCollectionFactory $questionCollectionFactory
     * @param CategoryResource $categoryResource
     * @param QuestionResource $questionResource
     */
    public function __construct(
        UrlPersistInterface $urlPersist,
        UrlRewriteFactory $urlRewriteFactory,
        StoreManagerInterface $storeManager,
        FaqHelper $helper,
        CategoryCollectionFactory $categoryCollectionFactory,
        QuestionCollectionFactory $questionCollectionFactory,
        CategoryResource $categoryResource,
        QuestionResource $questionResource
    ) {
        $this->urlPersist = $urlPersist;
        $this->urlRewriteFactory = $urlRewriteFactory;
        $this->storeManager = $storeManager;
        $this->helper = $helper;
        $this->categoryCollectionFactory = $categoryCollectionFactory;
        $this->questionCollectionFactory = $questionCollectionFactory;
        $this->categoryResource = $categoryResource;
        $this->questionResource = $questionResource;
    }

    /**
     * Generate URL rewrites for a category across all applicable stores
     *
     * @param CategoryInterface $category
     * @return void
     * @throws AlreadyExistsException
     */
    public function generateForCategory(CategoryInterface $category): void
    {
        $categoryId = (int) $category->getCategoryId();
        $urlKey = $category->getUrlKey();

        if (!$urlKey || !$categoryId) {
            return;
        }

        $this->deleteForEntity(self::ENTITY_TYPE_CATEGORY, $categoryId);

        $storeIds = $this->getStoreIdsForEntity($category);
        $urls = [];

        foreach ($storeIds as $storeId) {
            $prefix = $this->helper->getUrlPrefix($storeId);
            $requestPath = $prefix . '/' . $urlKey;

            if ($this->helper->isUrlSuffixEnabled($storeId)) {
                $requestPath .= $this->helper->getUrlSuffix($storeId);
            }

            $urls[] = $this->urlRewriteFactory->create()
                ->setEntityType(self::ENTITY_TYPE_CATEGORY)
                ->setEntityId($categoryId)
                ->setRequestPath($requestPath)
                ->setTargetPath('faq/category/view/id/' . $categoryId)
                ->setStoreId($storeId);
        }

        if (!empty($urls)) {
            $this->persistRewrites($urls, $urlKey);
        }
    }

    /**
     * Generate URL rewrites for a question across all applicable stores
     *
     * @param QuestionInterface $question
     * @return void
     * @throws AlreadyExistsException
     */
    public function generateForQuestion(QuestionInterface $question): void
    {
        $questionId = (int) $question->getQuestionId();
        $urlKey = $question->getUrlKey();

        if (!$urlKey || !$questionId) {
            return;
        }

        $this->deleteForEntity(self::ENTITY_TYPE_QUESTION, $questionId);

        $storeIds = $this->getStoreIdsForEntity($question);
        $urls = [];

        foreach ($storeIds as $storeId) {
            $prefix = $this->helper->getUrlPrefix($storeId);
            $requestPath = $prefix . '/' . $urlKey;

            if ($this->helper->isUrlSuffixEnabled($storeId)) {
                $requestPath .= $this->helper->getUrlSuffix($storeId);
            }

            $urls[] = $this->urlRewriteFactory->create()
                ->setEntityType(self::ENTITY_TYPE_QUESTION)
                ->setEntityId($questionId)
                ->setRequestPath($requestPath)
                ->setTargetPath('faq/question/view/id/' . $questionId)
                ->setStoreId($storeId);
        }

        if (!empty($urls)) {
            $this->persistRewrites($urls, $urlKey);
        }
    }

    /**
     * Regenerate URL rewrites for all categories and questions
     *
     * Performs a full rebuild: every FAQ rewrite is purged first, so rows orphaned by
     * deleted or unpublished entities (including rows left behind by module versions
     * that never removed rewrites on delete) are cleaned up by a single run.
     *
     * An entity whose URL key collides with an existing rewrite is skipped and reported
     * instead of aborting the run and leaving the table half-rebuilt.
     *
     * @return string[] Human-readable messages for entities that had to be skipped
     */
    public function generateAll(): array
    {
        $errors = [];

        $this->urlPersist->deleteByData([UrlRewrite::ENTITY_TYPE => self::ENTITY_TYPE_CATEGORY]);
        $this->urlPersist->deleteByData([UrlRewrite::ENTITY_TYPE => self::ENTITY_TYPE_QUESTION]);

        // The collections do not hydrate the store junction, so store_ids has to be looked up
        // per entity. Without it every entity would fall through to "all stores" and a single
        // run of this command would un-scope every rewrite in the store.
        $categoryCollection = $this->categoryCollectionFactory->create();
        foreach ($categoryCollection as $category) {
            /** @var \Magento\Framework\DataObject&CategoryInterface $category */
            if ((int) $category->getStatus() !== CategoryInterface::STATUS_ENABLED) {
                continue;
            }
            $categoryId = (int) $category->getCategoryId();
            $category->setData('store_ids', $this->categoryResource->lookupStoreIds($categoryId));
            try {
                $this->generateForCategory($category);
            } catch (AlreadyExistsException $exception) {
                $errors[] = sprintf(
                    'Category "%s" (id %d) skipped: %s',
                    $category->getName(),
                    $categoryId,
                    $exception->getMessage()
                );
            }
        }

        $questionCollection = $this->questionCollectionFactory->create();
        foreach ($questionCollection as $question) {
            /** @var \Magento\Framework\DataObject&QuestionInterface $question */
            if ($question->getStatus() !== QuestionInterface::STATUS_ANSWERED) {
                continue;
            }
            $questionId = (int) $question->getQuestionId();
            $question->setData('store_ids', $this->questionResource->lookupStoreIds($questionId));
            try {
                $this->generateForQuestion($question);
            } catch (AlreadyExistsException $exception) {
                $errors[] = sprintf(
                    'Question "%s" (id %d) skipped: %s',
                    $question->getTitle(),
                    $questionId,
                    $exception->getMessage()
                );
            }
        }

        return $errors;
    }

    /**
     * Delete URL rewrites for a specific entity
     *
     * @param string $entityType
     * @param int $entityId
     * @return void
     */
    public function deleteForEntity(string $entityType, int $entityId): void
    {
        $this->urlPersist->deleteByData([
            UrlRewrite::ENTITY_TYPE => $entityType,
            UrlRewrite::ENTITY_ID => $entityId,
        ]);
    }

    /**
     * Persist rewrites, converting the storage-level duplicate failure into an actionable error
     *
     * Categories and questions share one flat "<prefix>/<url_key>" namespace with every other
     * rewrite in the store (CMS pages, products, ...), and url_rewrite has a unique
     * request_path + store_id index. Relying on that index instead of a pre-check keeps the
     * validation race-free; the raw "URL key for specified store already exists." message is
     * replaced with one that names the conflicting request path so the failure is fixable.
     *
     * @param UrlRewrite[] $urls
     * @param string $urlKey
     * @return void
     * @throws AlreadyExistsException
     */
    private function persistRewrites(array $urls, string $urlKey): void
    {
        try {
            $this->urlPersist->replace($urls);
        } catch (UrlAlreadyExistsException $exception) {
            $conflicts = [];
            foreach ($exception->getUrls() as $url) {
                if (is_array($url) && isset($url[UrlRewrite::REQUEST_PATH])) {
                    $conflicts[] = sprintf(
                        '"%s" (store %s)',
                        $url[UrlRewrite::REQUEST_PATH],
                        $url[UrlRewrite::STORE_ID] ?? '?'
                    );
                }
            }
            throw new AlreadyExistsException(
                __(
                    'The URL key "%1" is already used by another page (%2). Please choose a different URL key.',
                    $urlKey,
                    $conflicts ? implode(', ', $conflicts) : __('an existing URL rewrite')
                ),
                $exception
            );
        } catch (AlreadyExistsException $exception) {
            throw new AlreadyExistsException(
                __(
                    'The URL key "%1" is already used by another page. Please choose a different URL key.',
                    $urlKey
                ),
                $exception
            );
        }
    }

    /**
     * Get store IDs for an entity
     *
     * If the entity has store_ids data, use those; otherwise, fall back to all stores
     *
     * @param CategoryInterface|QuestionInterface $entity
     * @return int[]
     */
    private function getStoreIdsForEntity(CategoryInterface|QuestionInterface $entity): array
    {
        /** @var \Magento\Framework\DataObject $entity */
        $storeIds = $entity->getData('store_ids');

        if (is_array($storeIds) && !empty($storeIds)) {
            // Normalise first: an admin form POST delivers store ids as strings ("0", "1"),
            // so the "All Store Views" check must not be a strict comparison against int 0.
            // Store id 0 is not a real store — a rewrite written with store_id = 0 is never
            // matched by Magento's UrlRewrite router, which looks rewrites up by the current
            // store id. Expand it to every real store instead.
            $storeIds = array_map('intval', $storeIds);

            if (in_array(Store::DEFAULT_STORE_ID, $storeIds, true)) {
                return $this->getAllStoreIds();
            }

            return $storeIds;
        }

        return $this->getAllStoreIds();
    }

    /**
     * Get all store IDs (excluding admin store)
     *
     * @return int[]
     */
    private function getAllStoreIds(): array
    {
        $storeIds = [];
        foreach ($this->storeManager->getStores() as $store) {
            $storeIds[] = (int) $store->getId();
        }

        return $storeIds;
    }
}
