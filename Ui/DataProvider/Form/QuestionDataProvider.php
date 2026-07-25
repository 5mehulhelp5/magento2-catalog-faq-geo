<?php
/**
 * Magendoo Faq Question Form Data Provider
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Ui\DataProvider\Form;

use Magendoo\Faq\Model\ResourceModel\Question\CollectionFactory;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\Ui\DataProvider\ModifierPoolDataProvider;

/**
 * Form Data Provider for FAQ Questions
 */
class QuestionDataProvider extends ModifierPoolDataProvider
{
    /**
     * @var \Magendoo\Faq\Model\ResourceModel\Question\Collection
     */
    protected $collection;

    /**
     * @var DataPersistorInterface
     */
    protected DataPersistorInterface $dataPersistor;

    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;

    /**
     * @var array
     */
    protected array $loadedData = [];

    /**
     * Constructor
     *
     * @param string $name
     * @param string $primaryFieldName
     * @param string $requestFieldName
     * @param CollectionFactory $questionCollectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param RequestInterface $request
     * @param array $meta
     * @param array $data
     * @param PoolInterface|null $pool
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $questionCollectionFactory,
        DataPersistorInterface $dataPersistor,
        RequestInterface $request,
        array $meta = [],
        array $data = [],
        ?PoolInterface $pool = null
    ) {
        $this->collection = $questionCollectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->request = $request;
        parent::__construct($name, $primaryFieldName, $requestFieldName, $meta, $data, $pool);
    }

    /**
     * Get data
     *
     * @return array
     */
    public function getData(): array
    {
        if (!empty($this->loadedData)) {
            return $this->loadedData;
        }

        // Load only the question being edited. Without this filter the whole
        // table is hydrated with three junction lookups per row; core form
        // data providers scope to the request id the same way (see
        // \Magento\Cms\Model\Page\DataProvider::getPageId()).
        $questionId = (int) $this->request->getParam($this->getRequestFieldName());
        if ($questionId) {
            $this->collection->addFieldToFilter('question_id', $questionId);

            /** @var \Magendoo\Faq\Model\Question $question */
            foreach ($this->collection->getItems() as $question) {
                $data = $question->getData();

                /** @var \Magendoo\Faq\Model\ResourceModel\Question $resource */
                $resource = $question->getResource();

                // Load store IDs from junction table
                $storeIds = $resource->lookupStoreIds((int)$question->getId());
                $data['store_ids'] = $storeIds;

                // Load category IDs from junction table
                $categoryIds = $resource->lookupCategoryIds((int)$question->getId());
                $data['category_ids'] = $categoryIds;

                // Load product IDs from junction table. The form uses a comma-
                // separated text input, so flatten the int[] to a CSV string.
                $productIds = $resource->lookupProductIds((int)$question->getId());
                $data['product_ids'] = implode(',', $productIds);

                // Load tag IDs from junction table
                $tagIds = $resource->lookupTagIds((int)$question->getId());
                $data['tag_ids'] = $tagIds;

                // Load customer group IDs from junction table
                $customerGroupIds = $resource->lookupCustomerGroupIds((int)$question->getId());
                $data['customer_group_ids'] = $customerGroupIds;

                $this->loadedData[$question->getId()] = $data;
            }
        }

        $data = $this->dataPersistor->get('faq_question');
        if (!empty($data)) {
            $question = $this->collection->getNewEmptyItem();
            $question->setData($data);
            $this->loadedData[$question->getId()] = $question->getData();
            $this->dataPersistor->clear('faq_question');
        }

        return $this->loadedData;
    }
}
