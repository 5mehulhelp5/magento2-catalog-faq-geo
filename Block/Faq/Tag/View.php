<?php
/**
 * Magendoo Faq Tag View Block
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
use Magendoo\Faq\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;
use Magendoo\Faq\Model\ResourceModel\Tag as TagResource;
use Magendoo\Faq\Model\TagFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Store\Model\StoreManagerInterface;

/**
 * FAQ Tag View Block
 */
class View extends Template
{
    /**
     * @var TagFactory
     */
    private TagFactory $tagFactory;

    /**
     * @var TagResource
     */
    private TagResource $tagResource;

    /**
     * @var QuestionCollectionFactory
     */
    private QuestionCollectionFactory $questionCollectionFactory;

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
     * @var TagInterface|null|false
     */
    private TagInterface|null|false $tag = false;

    /**
     * @param Context $context
     * @param TagFactory $tagFactory
     * @param TagResource $tagResource
     * @param QuestionCollectionFactory $questionCollectionFactory
     * @param FaqHelper $helper
     * @param StoreManagerInterface $storeManager
     * @param CustomerSession $customerSession
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        TagFactory $tagFactory,
        TagResource $tagResource,
        QuestionCollectionFactory $questionCollectionFactory,
        FaqHelper $helper,
        StoreManagerInterface $storeManager,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->tagFactory = $tagFactory;
        $this->tagResource = $tagResource;
        $this->questionCollectionFactory = $questionCollectionFactory;
        $this->helper = $helper;
        $this->storeManager = $storeManager;
        $this->customerSession = $customerSession;
        parent::__construct($context, $data);
    }

    /**
     * Get current tag
     *
     * @return TagInterface|null
     */
    public function getTag(): ?TagInterface
    {
        if ($this->tag === false) {
            $tagId = (int) $this->getRequest()->getParam('id');
            if ($tagId) {
                $tag = $this->tagFactory->create();
                $this->tagResource->load($tag, $tagId);
                $this->tag = $tag->getTagId() ? $tag : null;
            } else {
                $this->tag = null;
            }
        }

        return $this->tag;
    }

    /**
     * Get questions linked to this tag, filtered by active + public + store + customer group
     *
     * @return \Magendoo\Faq\Model\ResourceModel\Question\Collection|null
     */
    public function getQuestions(): ?\Magendoo\Faq\Model\ResourceModel\Question\Collection
    {
        $tag = $this->getTag();
        if (!$tag) {
            return null;
        }

        $collection = $this->questionCollectionFactory->create();

        // Join question_tag to filter by tag
        $collection->getSelect()->join(
            ['qt' => $collection->getTable('magendoo_faq_question_tag')],
            'main_table.question_id = qt.question_id',
            []
        )->where('qt.tag_id = ?', $tag->getTagId());

        // Active + public
        $collection->addActiveFilter();
        $collection->addVisibilityFilter(QuestionInterface::VISIBILITY_PUBLIC);

        // Store filter
        $storeId = (int) $this->storeManager->getStore()->getId();
        $collection->addStoreFilter($storeId);

        // Customer group visibility
        $collection->addCustomerGroupVisibilityFilter((int) $this->customerSession->getCustomerGroupId());

        $collection->setOrder('position', 'ASC');

        return $collection;
    }

    /**
     * Get question URL
     *
     * @param QuestionInterface $question
     * @return string
     */
    public function getQuestionUrl(QuestionInterface $question): string
    {
        $urlKey = $question->getUrlKey();
        if ($urlKey) {
            // Build a direct question URL (without category prefix)
            return $this->getUrl('faq/question/view', ['id' => $question->getQuestionId()]);
        }

        return $this->getUrl('faq/question/view', ['id' => $question->getQuestionId()]);
    }
}
