<?php
/**
 * Magendoo Faq Hreflang Tags Block
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Block\Faq;

use Magendoo\Faq\Api\CategoryRepositoryInterface;
use Magendoo\Faq\Api\QuestionRepositoryInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * Emits `<link rel="alternate" hreflang="..." href="..."/>` tags for FAQ pages,
 * helping Google serve the correct language version to visitors from different
 * locales.
 *
 * Each alternate is built with the TARGET store's own url_prefix and url_suffix
 * (both are store-scoped), and only stores where the module is enabled and the
 * entity is actually assigned are advertised — an alternate the target store's
 * router cannot match would 404 and poison the whole hreflang cluster.
 * `x-default` deterministically points at the qualifying store with the lowest
 * store id, so every cached page variant advertises the same cluster.
 *
 * Placed in `head.additional` via layout XML and gated by the
 * `magendoo_faq/seo/hreflang_enabled` config flag.
 */
class Hreflang extends Template
{
    /**
     * @var FaqHelper
     */
    private FaqHelper $helper;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var CategoryRepositoryInterface
     */
    private CategoryRepositoryInterface $categoryRepository;

    /**
     * @var QuestionRepositoryInterface
     */
    private QuestionRepositoryInterface $questionRepository;

    /**
     * @param Context $context
     * @param FaqHelper $helper
     * @param StoreManagerInterface $storeManager
     * @param CategoryRepositoryInterface $categoryRepository
     * @param QuestionRepositoryInterface $questionRepository
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        FaqHelper $helper,
        StoreManagerInterface $storeManager,
        CategoryRepositoryInterface $categoryRepository,
        QuestionRepositoryInterface $questionRepository,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->categoryRepository = $categoryRepository;
        $this->questionRepository = $questionRepository;
        parent::__construct($context, $data);
    }

    /**
     * Build an array of hreflang link data for the current FAQ page.
     *
     * @return array<int, array{hreflang: string, href: string}>
     */
    public function getHreflangLinks(): array
    {
        if (!$this->helper->isEnabled()) {
            return [];
        }

        $page = $this->resolveCurrentPage();
        if ($page === null) {
            return [];
        }
        [$urlKey, $entityStoreIds] = $page;

        // Collect qualifying stores keyed by store id so the result (and the
        // x-default choice below) is deterministic across cache variants.
        $candidates = [];
        foreach ($this->storeManager->getStores() as $store) {
            /** @var \Magento\Store\Model\Store $store */
            if (!$store->getIsActive()) {
                continue;
            }

            $storeId = (int) $store->getId();
            if (!$this->helper->isEnabled($storeId)) {
                continue;
            }
            if (!$this->isAssignedToStore($entityStoreIds, $storeId)) {
                continue;
            }

            $path = $urlKey === null
                ? $this->helper->getUrlPrefix($storeId)
                : $this->helper->buildUrlPath($urlKey, $storeId);
            if ($path === '' || $path === '/') {
                continue;
            }

            // Use the store's locale as hreflang (e.g. "en_US" → "en-us").
            $locale = $store->getConfig('general/locale/code') ?: 'en_US';

            $candidates[$storeId] = [
                'hreflang' => str_replace('_', '-', strtolower((string) $locale)),
                'href' => $store->getBaseUrl() . $path,
            ];
        }
        ksort($candidates);

        // Deduplicate: two stores sharing a base URL and prefix would advertise the
        // same href under different language codes, and a repeated hreflang code is
        // invalid — in both cases the first (lowest store id) wins.
        $links = [];
        $seenHreflang = [];
        $seenHref = [];
        foreach ($candidates as $candidate) {
            if (isset($seenHreflang[$candidate['hreflang']]) || isset($seenHref[$candidate['href']])) {
                continue;
            }
            $seenHreflang[$candidate['hreflang']] = true;
            $seenHref[$candidate['href']] = true;
            $links[] = $candidate;
        }

        if (!empty($links)) {
            $links[] = [
                'hreflang' => 'x-default',
                'href' => $links[0]['href'],
            ];
        }

        return $links;
    }

    /**
     * Resolve the current FAQ page to a URL key and entity store assignment.
     *
     * @return array{0: string|null, 1: int[]}|null [urlKey (null = FAQ home), entity store ids],
     *     or null when the page cannot be resolved to a shareable pretty URL
     */
    private function resolveCurrentPage(): ?array
    {
        $request = $this->getRequest();
        $fullActionName = $request instanceof \Magento\Framework\App\Request\Http
            ? $request->getFullActionName()
            : '';

        if ($fullActionName === 'faq_index_index') {
            // The FAQ home page exists in every store the module is enabled for.
            return [null, []];
        }

        $entityId = (int) $this->getRequest()->getParam('id');
        if (!$entityId) {
            return null;
        }

        try {
            if ($fullActionName === 'faq_category_view') {
                $entity = $this->categoryRepository->getById($entityId);
            } elseif ($fullActionName === 'faq_question_view') {
                $entity = $this->questionRepository->getById($entityId);
            } else {
                return null;
            }
        } catch (NoSuchEntityException $e) {
            return null;
        }

        $urlKey = $entity->getUrlKey();
        if (!$urlKey) {
            // Without a url_key there is no pretty URL to advertise across stores.
            return null;
        }

        /**
         * The repositories return interfaces, but every concrete FAQ entity is an
         * AbstractModel; the intersection keeps getData() resolvable without widening
         * (and thus contradicting) the type inferred above.
         *
         * @var (\Magendoo\Faq\Api\Data\CategoryInterface|\Magendoo\Faq\Api\Data\QuestionInterface)&\Magento\Framework\Model\AbstractModel $entity
         */
        $storeIds = $entity->getData('store_ids');

        return [$urlKey, is_array($storeIds) ? array_map('intval', $storeIds) : []];
    }

    /**
     * Check whether the entity is assigned to the given store.
     *
     * An empty assignment or store 0 ("All Store Views") means every store.
     *
     * @param int[] $entityStoreIds
     * @param int $storeId
     * @return bool
     */
    private function isAssignedToStore(array $entityStoreIds, int $storeId): bool
    {
        if (empty($entityStoreIds) || in_array(0, $entityStoreIds, true)) {
            return true;
        }

        return in_array($storeId, $entityStoreIds, true);
    }
}
