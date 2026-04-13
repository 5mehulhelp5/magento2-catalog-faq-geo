<?php
/**
 * Magendoo Faq Product Questions Block
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Block\Product;

use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\ResourceModel\Question\CollectionFactory as QuestionCollectionFactory;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Framework\Registry;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * FAQ Product Questions Tab Block
 */
class Questions extends Template
{
    /**
     * @var QuestionCollectionFactory
     */
    private QuestionCollectionFactory $questionCollectionFactory;

    /**
     * @var FaqHelper
     */
    private FaqHelper $helper;

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var \Magendoo\Faq\Model\ResourceModel\Question\Collection|null
     */
    private ?\Magendoo\Faq\Model\ResourceModel\Question\Collection $questionCollection = null;

    /**
     * @param Context $context
     * @param QuestionCollectionFactory $questionCollectionFactory
     * @param FaqHelper $helper
     * @param Registry $registry
     * @param CustomerSession $customerSession
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        QuestionCollectionFactory $questionCollectionFactory,
        FaqHelper $helper,
        Registry $registry,
        CustomerSession $customerSession,
        array $data = []
    ) {
        $this->questionCollectionFactory = $questionCollectionFactory;
        $this->helper = $helper;
        $this->registry = $registry;
        $this->customerSession = $customerSession;
        parent::__construct($context, $data);
    }

    /**
     * Get questions for current product
     *
     * @return \Magendoo\Faq\Model\ResourceModel\Question\Collection
     */
    public function getQuestions(): \Magendoo\Faq\Model\ResourceModel\Question\Collection
    {
        if ($this->questionCollection === null) {
            $this->questionCollection = $this->questionCollectionFactory->create();
            $this->questionCollection->addFieldToFilter('visibility', QuestionInterface::VISIBILITY_PUBLIC);
            $this->questionCollection->addFieldToFilter('status', QuestionInterface::STATUS_ANSWERED);

            $productId = $this->getProductId();
            if ($productId) {
                $this->questionCollection->addProductFilter($productId);
            }

            $storeId = (int) $this->_storeManager->getStore()->getId();
            $this->questionCollection->addStoreFilter($storeId);
            $this->questionCollection->addCustomerGroupVisibilityFilter((int) $this->customerSession->getCustomerGroupId());

            $limit = $this->helper->getProductQuestionsLimit();
            if ($limit > 0) {
                $this->questionCollection->setPageSize($limit);
            }

            $this->questionCollection->setOrder('position', 'ASC');
        }

        return $this->questionCollection;
    }

    /**
     * Get current product ID
     *
     * @return int|null
     */
    public function getProductId(): ?int
    {
        $product = $this->registry->registry('current_product');
        return $product ? (int) $product->getId() : null;
    }

    /**
     * Get ask question URL
     *
     * @return string
     */
    public function getAskQuestionUrl(): string
    {
        $productId = $this->getProductId();
        return $this->getUrl('faq/question/submit', ['product_id' => $productId]);
    }

    /**
     * Check if ask question is enabled
     *
     * @return bool
     */
    public function isAskQuestionEnabled(): bool
    {
        return $this->helper->isProductAskQuestionEnabled();
    }

    /**
     * Get tab title with question count
     *
     * @return string
     */
    public function getTabTitle(): string
    {
        $tabName = $this->helper->getProductTabName();
        $count = $this->getQuestions()->getSize();

        return str_replace('{count}', (string) $count, $tabName);
    }

    /**
     * Get title for product.info.details tab label
     *
     * @return string
     */
    public function getTitle(): string
    {
        return $this->getTabTitle();
    }
}
