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

use Magendoo\Faq\Api\Data\TagInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\ResourceModel\Tag\CollectionFactory as TagCollectionFactory;
use Magendoo\Faq\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * FAQ Tag Cloud Block
 */
class Cloud extends Template
{
    /**
     * @var TagCollectionFactory
     */
    private TagCollectionFactory $tagCollectionFactory;

    /**
     * @var QuestionCollectionFactory
     */
    private QuestionCollectionFactory $questionCollectionFactory;

    /**
     * @var FaqHelper
     */
    private FaqHelper $helper;

    /**
     * @var array<int, int>|null
     */
    private ?array $tagCounts = null;

    /**
     * @param Context $context
     * @param TagCollectionFactory $tagCollectionFactory
     * @param QuestionCollectionFactory $questionCollectionFactory
     * @param FaqHelper $helper
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        TagCollectionFactory $tagCollectionFactory,
        QuestionCollectionFactory $questionCollectionFactory,
        FaqHelper $helper,
        array $data = []
    ) {
        $this->tagCollectionFactory = $tagCollectionFactory;
        $this->questionCollectionFactory = $questionCollectionFactory;
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Get all tags that have at least one linked question, ordered by name
     *
     * @return \Magendoo\Faq\Model\ResourceModel\Tag\Collection
     */
    public function getTags(): \Magendoo\Faq\Model\ResourceModel\Tag\Collection
    {
        $collection = $this->tagCollectionFactory->create();
        $collection->getSelect()->join(
            ['qt' => $collection->getTable('magendoo_faq_question_tag')],
            'main_table.tag_id = qt.tag_id',
            []
        )->group('main_table.tag_id');
        $collection->setOrder('name', 'ASC');

        return $collection;
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
     * Get number of questions linked to this tag
     *
     * @param TagInterface $tag
     * @return int
     */
    public function getTagCount(TagInterface $tag): int
    {
        $counts = $this->loadTagCounts();
        $tagId = (int) $tag->getTagId();

        return $counts[$tagId] ?? 0;
    }

    /**
     * Load question counts for all tags in a single query
     *
     * @return array<int, int>
     */
    private function loadTagCounts(): array
    {
        if ($this->tagCounts === null) {
            $this->tagCounts = [];
            $collection = $this->questionCollectionFactory->create();
            $connection = $collection->getConnection();
            $select = $connection->select()
                ->from(
                    $collection->getTable('magendoo_faq_question_tag'),
                    ['tag_id', 'cnt' => new \Zend_Db_Expr('COUNT(question_id)')]
                )
                ->group('tag_id');

            foreach ($connection->fetchAll($select) as $row) {
                $this->tagCounts[(int) $row['tag_id']] = (int) $row['cnt'];
            }
        }

        return $this->tagCounts;
    }
}
