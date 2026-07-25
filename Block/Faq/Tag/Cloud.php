<?php
/**
 * Magendoo Faq Tag Cloud Block
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Block\Faq\Tag;

use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Api\Data\TagInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\Question as QuestionModel;
use Magendoo\Faq\Model\ResourceModel\Tag\CollectionFactory as TagCollectionFactory;
use Magendoo\Faq\Model\Tag as TagModel;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\DataObject;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;

/**
 * FAQ Tag Cloud Block
 */
class Cloud extends Template implements IdentityInterface
{
    /**
     * Bounds the cloud when the navigation/tags_limit config value is not set.
     */
    private const DEFAULT_TAGS_LIMIT = 50;

    /**
     * Config path for the maximum number of tags rendered in the cloud.
     */
    private const XML_PATH_TAGS_LIMIT = 'magendoo_faq/navigation/tags_limit';

    /**
     * @var TagCollectionFactory
     */
    private TagCollectionFactory $tagCollectionFactory;

    /**
     * @var FaqHelper
     */
    private FaqHelper $helper;

    /**
     * @var StoreManagerInterface
     */
    private StoreManagerInterface $storeManager;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var \Magendoo\Faq\Model\ResourceModel\Tag\Collection|null
     */
    private ?\Magendoo\Faq\Model\ResourceModel\Tag\Collection $tags = null;

    /**
     * @param Context $context
     * @param TagCollectionFactory $tagCollectionFactory
     * @param FaqHelper $helper
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        TagCollectionFactory $tagCollectionFactory,
        FaqHelper $helper,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->tagCollectionFactory = $tagCollectionFactory;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        parent::__construct($context, $data);
    }

    /**
     * Get the most used tags among questions visible to the current shopper
     *
     * Only answered, public questions assigned to the current store (or to no
     * store / all stores) and visible to the shopper's customer group are
     * counted; tags without such a question are omitted. The list is capped by
     * the navigation/tags_limit config value, keeping the most used tags,
     * ordered alphabetically for display. Each row carries a question_count
     * column consumed by getTagCount()/getTagSize().
     *
     * @return \Magendoo\Faq\Model\ResourceModel\Tag\Collection
     */
    public function getTags(): \Magendoo\Faq\Model\ResourceModel\Tag\Collection
    {
        if ($this->tags === null) {
            $collection = $this->tagCollectionFactory->create();
            $storeId = (int) $this->storeManager->getStore()->getId();
            $groupId = (int) $this->customerSession->getCustomerGroupId();
            $groupJunction = $collection->getTable('magendoo_faq_question_customer_group');
            $noRestriction =
                "NOT EXISTS (SELECT 1 FROM {$groupJunction} qcg WHERE qcg.question_id = q.question_id)";
            $matchesGroup = "EXISTS (SELECT 1 FROM {$groupJunction} qcg WHERE qcg.question_id = q.question_id"
                . ' AND qcg.customer_group_id = ' . $groupId . ')';

            $select = $collection->getSelect();
            $select->join(
                ['qt' => $collection->getTable('magendoo_faq_question_tag')],
                'main_table.tag_id = qt.tag_id',
                []
            )->join(
                ['q' => $collection->getTable('magendoo_faq_question')],
                'qt.question_id = q.question_id',
                []
            )->joinLeft(
                ['q_store' => $collection->getTable('magendoo_faq_question_store')],
                'q.question_id = q_store.question_id',
                []
            )->where('q.status = ?', QuestionInterface::STATUS_ANSWERED)
                ->where('q.visibility = ?', QuestionInterface::VISIBILITY_PUBLIC)
                ->where('q_store.store_id IS NULL OR q_store.store_id IN (?)', [0, $storeId])
                ->where("({$noRestriction}) OR ({$matchesGroup})")
                ->group('main_table.tag_id')
                ->columns(['question_count' => new \Zend_Db_Expr('COUNT(DISTINCT q.question_id)')])
                ->order(['question_count DESC', 'name ASC'])
                ->limit($this->getTagsLimit());

            $this->tags = $collection;
        }

        return $this->tags;
    }

    /**
     * Return identifiers for produced content
     *
     * The cloud lists tags sized by question counts, so it carries every rendered
     * tag's identities plus both bare list tags: new tags must appear, and question
     * saves change the counts and which tags qualify.
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        $identities = [[TagModel::CACHE_TAG, QuestionModel::CACHE_TAG]];
        foreach ($this->getTags() as $tag) {
            if ($tag instanceof IdentityInterface) {
                $identities[] = $tag->getIdentities();
            }
        }

        return array_merge([], ...$identities);
    }

    /**
     * Get tag URL
     *
     * @param TagInterface $tag
     * @return string
     */
    public function getTagUrl(TagInterface $tag): string
    {
        $urlKey = $tag->getUrlKey();
        if ($urlKey) {
            return $this->getBaseUrl() . $this->helper->buildUrlPath('tag/' . $urlKey);
        }

        return $this->getUrl('faq/tag/view', ['id' => $tag->getTagId()]);
    }

    /**
     * Get CSS class based on question count
     *
     * @param TagInterface $tag
     * @return string
     */
    public function getTagSize(TagInterface $tag): string
    {
        $count = $this->getTagCount($tag);

        if ($count >= 10) {
            return 'faq-tag-lg';
        }

        if ($count >= 3) {
            return 'faq-tag-md';
        }

        return 'faq-tag-sm';
    }

    /**
     * Get number of visible questions linked to this tag
     *
     * The count is computed by the getTags() query (question_count column),
     * so it reflects the same store/status/visibility/customer-group scope
     * as the rendered cloud.
     *
     * @param TagInterface $tag
     * @return int
     */
    public function getTagCount(TagInterface $tag): int
    {
        if ($tag instanceof DataObject) {
            return (int) $tag->getData('question_count');
        }

        return 0;
    }

    /**
     * Get the configured maximum number of tags to render
     *
     * @return int
     */
    private function getTagsLimit(): int
    {
        $limit = (int) $this->_scopeConfig->getValue(
            self::XML_PATH_TAGS_LIMIT,
            ScopeInterface::SCOPE_STORE
        );

        return $limit > 0 ? $limit : self::DEFAULT_TAGS_LIMIT;
    }
}
