<?php
/**
 * Magendoo Faq Category Form Data Provider
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Ui\DataProvider\Form;

use Magendoo\Faq\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\File\Mime;
use Magento\Framework\Filesystem;
use Magento\Framework\UrlInterface;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Ui\DataProvider\Modifier\PoolInterface;
use Magento\Ui\DataProvider\ModifierPoolDataProvider;

/**
 * Form Data Provider for FAQ Categories
 */
class CategoryDataProvider extends ModifierPoolDataProvider
{
    /**
     * Media directory (relative to pub/media) where category icons live.
     * Must match the base path used by the icon upload controller.
     */
    public const ICON_MEDIA_PATH = 'magendoo/faq/category';

    /**
     * @var \Magendoo\Faq\Model\ResourceModel\Category\Collection
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
     * @var Filesystem
     */
    protected Filesystem $filesystem;

    /**
     * @var StoreManagerInterface
     */
    protected StoreManagerInterface $storeManager;

    /**
     * @var Mime
     */
    protected Mime $mime;

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
     * @param CollectionFactory $categoryCollectionFactory
     * @param DataPersistorInterface $dataPersistor
     * @param RequestInterface $request
     * @param Filesystem $filesystem
     * @param StoreManagerInterface $storeManager
     * @param Mime $mime
     * @param array $meta
     * @param array $data
     * @param PoolInterface|null $pool
     * @SuppressWarnings(PHPMD.ExcessiveParameterList)
     */
    public function __construct(
        $name,
        $primaryFieldName,
        $requestFieldName,
        CollectionFactory $categoryCollectionFactory,
        DataPersistorInterface $dataPersistor,
        RequestInterface $request,
        Filesystem $filesystem,
        StoreManagerInterface $storeManager,
        Mime $mime,
        array $meta = [],
        array $data = [],
        ?PoolInterface $pool = null
    ) {
        $this->collection = $categoryCollectionFactory->create();
        $this->dataPersistor = $dataPersistor;
        $this->request = $request;
        $this->filesystem = $filesystem;
        $this->storeManager = $storeManager;
        $this->mime = $mime;
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

        // Load only the category being edited. Without this filter the whole
        // table is hydrated with a junction lookup per row; core form data
        // providers scope to the request id the same way (see
        // \Magento\Cms\Model\Page\DataProvider::getPageId()).
        $categoryId = (int) $this->request->getParam($this->getRequestFieldName());
        if ($categoryId) {
            $this->collection->addFieldToFilter('category_id', $categoryId);

            /** @var \Magendoo\Faq\Model\Category $category */
            foreach ($this->collection->getItems() as $category) {
                $data = $category->getData();

                // Load store IDs from junction table
                $storeIds = $category->getResource()->lookupStoreIds((int)$category->getId());
                $data['store_ids'] = $storeIds;

                $data = $this->convertIconData($data);

                $this->loadedData[$category->getId()] = $data;
            }
        }

        $data = $this->dataPersistor->get('faq_category');
        if (!empty($data)) {
            $category = $this->collection->getNewEmptyItem();
            $category->setData($data);
            $this->loadedData[$category->getId()] = $category->getData();
            $this->dataPersistor->clear('faq_category');
        }

        return $this->loadedData;
    }

    /**
     * Convert the stored icon path into the file-info record set expected by
     * the fileUploader UI component, mirroring
     * \Magento\Catalog\Model\Category\DataProvider::convertValues().
     *
     * If the stored file no longer exists on disk the value is omitted
     * instead of emitting a record the uploader cannot preview.
     *
     * @param array $data
     * @return array
     */
    private function convertIconData(array $data): array
    {
        $icon = $data['icon'] ?? null;
        unset($data['icon']);

        if (!is_string($icon) || $icon === '') {
            return $data;
        }

        // The save controller stores the bare file name inside the module
        // media folder; tolerate media-relative paths in pre-existing data.
        $relativePath = ltrim($icon, '/');
        if (!str_contains($relativePath, '/')) {
            $relativePath = self::ICON_MEDIA_PATH . '/' . $relativePath;
        }

        $mediaDirectory = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA);
        if (!$mediaDirectory->isFile($relativePath)) {
            return $data;
        }

        $stat = $mediaDirectory->stat($relativePath);
        /** @var \Magento\Store\Model\Store $store */
        $store = $this->storeManager->getStore();
        $mediaUrl = $store->getBaseUrl(UrlInterface::URL_TYPE_MEDIA);

        $data['icon'] = [
            [
                // phpcs:ignore Magento2.Functions.DiscouragedFunction
                'name' => basename($relativePath),
                'url' => $mediaUrl . $relativePath,
                'size' => $stat['size'] ?? 0,
                'type' => $this->mime->getMimeType($mediaDirectory->getAbsolutePath($relativePath)),
            ],
        ];

        return $data;
    }
}
