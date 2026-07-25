<?php
/**
 * Magendoo Faq Category Save Controller
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\Request\DataPersistorInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Exception\LocalizedException;
use Magendoo\Faq\Api\CategoryRepositoryInterface;
use Magendoo\Faq\Api\Data\CategoryInterface;
use Magendoo\Faq\Model\CategoryFactory;

/**
 * Save category controller
 */
class Save extends Action implements HttpPostActionInterface
{
    /**
     * Authorization resource
     */
    public const ADMIN_RESOURCE = 'Magendoo_Faq::category_edit';

    /**
     * @var CategoryRepositoryInterface
     */
    protected CategoryRepositoryInterface $categoryRepository;

    /**
     * @var CategoryFactory
     */
    protected CategoryFactory $categoryFactory;

    /**
     * @var DataPersistorInterface
     */
    protected DataPersistorInterface $dataPersistor;

    /**
     * @var ImageUploader
     */
    protected ImageUploader $imageUploader;

    /**
     * @param Context $context
     * @param CategoryRepositoryInterface $categoryRepository
     * @param CategoryFactory $categoryFactory
     * @param DataPersistorInterface $dataPersistor
     * @param ImageUploader $imageUploader
     */
    public function __construct(
        Context $context,
        CategoryRepositoryInterface $categoryRepository,
        CategoryFactory $categoryFactory,
        DataPersistorInterface $dataPersistor,
        ImageUploader $imageUploader
    ) {
        $this->categoryRepository = $categoryRepository;
        $this->categoryFactory = $categoryFactory;
        $this->dataPersistor = $dataPersistor;
        $this->imageUploader = $imageUploader;
        parent::__construct($context);
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        /** @var \Magento\Backend\Model\View\Result\Redirect $resultRedirect */
        $resultRedirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $data = $this->getRequest()->getPostValue();

        if (!$data) {
            return $resultRedirect->setPath('*/*/');
        }

        $categoryId = isset($data['category_id']) ? (int) $data['category_id'] : null;

        try {
            if ($categoryId) {
                $category = $this->categoryRepository->getById($categoryId);
            } else {
                $category = $this->categoryFactory->create();
            }

            $category->setName($data['name'] ?? '');
            $category->setPageTitle($data['page_title'] ?? null);
            $category->setUrlKey($data['url_key'] ?? null);
            $category->setDescription($data['description'] ?? null);

            // The icon comes from the fileUploader component as an array of
            // file info. A freshly uploaded file carries `tmp_name` and must
            // be moved out of the tmp media directory (same contract as
            // \Magento\Catalog\Model\Category\Attribute\Backend\Image). When
            // the key is absent from the POST the stored value is kept as is.
            if (array_key_exists('icon', $data)) {
                if (is_array($data['icon']) && isset($data['icon'][0]['name'])) {
                    $iconName = (string) $data['icon'][0]['name'];
                    if (isset($data['icon'][0]['tmp_name'])) {
                        $iconName = $this->moveIconFromTmp($iconName);
                        $data['icon'][0]['name'] = $iconName;
                        unset($data['icon'][0]['tmp_name']);
                    }
                    $category->setIcon($iconName);
                } elseif (empty($data['icon'])) {
                    $category->setIcon(null);
                }
            }

            $category->setPosition((int) ($data['position'] ?? 0));
            $category->setStatus((int) ($data['status'] ?? CategoryInterface::STATUS_ENABLED));
            $category->setMetaTitle($data['meta_title'] ?? null);
            $category->setMetaDescription($data['meta_description'] ?? null);
            $category->setNoindex(!empty($data['noindex']));
            $category->setNofollow(!empty($data['nofollow']));
            $category->setCanonicalUrl($data['canonical_url'] ?? null);
            $category->setExcludeSitemap(!empty($data['exclude_sitemap']));

            // Handle store_ids from form data
            if (isset($data['store_ids'])) {
                $category->setData('store_ids', $data['store_ids']);
            }

            $category = $this->categoryRepository->save($category);

            $this->messageManager->addSuccessMessage(__('The category has been saved.'));
            $this->dataPersistor->clear('faq_category');

            if ($this->getRequest()->getParam('back')) {
                return $resultRedirect->setPath('*/*/edit', ['category_id' => $category->getCategoryId()]);
            }

            return $resultRedirect->setPath('*/*/');
        } catch (LocalizedException $e) {
            $this->messageManager->addErrorMessage($e->getMessage());
        } catch (\Exception $e) {
            $this->messageManager->addExceptionMessage($e, __('Something went wrong while saving the category.'));
        }

        // Keep the submitted values so the form DataProvider can restore
        // them after the redirect (see
        // \Magento\Cms\Controller\Adminhtml\Page\Save::execute()).
        $this->dataPersistor->set('faq_category', $data);

        // Redirect back with data
        $redirectParams = ['_current' => true, '_use_forward' => false];
        if ($categoryId) {
            $redirectParams['category_id'] = $categoryId;
        }

        return $resultRedirect->setPath('*/*/edit', $redirectParams);
    }

    /**
     * Move a freshly uploaded icon from the tmp media path to its final location.
     *
     * The file may be renamed on the way to keep the target name unique, so
     * the resulting file name is taken from the returned relative path.
     *
     * @param string $iconName
     * @return string
     * @throws LocalizedException
     */
    private function moveIconFromTmp(string $iconName): string
    {
        $newRelativePath = $this->imageUploader->moveFileFromTmp($iconName, true);
        $parts = explode('/', $newRelativePath);

        return (string) end($parts);
    }
}
