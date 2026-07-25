<?php
/**
 * Magendoo Faq Category Icon Upload Controller
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Controller\Adminhtml\Category;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Catalog\Model\ImageUploader;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;

/**
 * Category icon upload controller
 *
 * Target of the `faq/category/upload` fileUploader URL declared in
 * view/adminhtml/ui_component/faq_category_form.xml. Saves the uploaded
 * file to the temporary media directory; Save moves it to its final
 * location once the category is persisted.
 */
class Upload extends Action implements HttpPostActionInterface
{
    /**
     * Authorization resource
     */
    public const ADMIN_RESOURCE = 'Magendoo_Faq::category_edit';

    /**
     * @var ImageUploader
     */
    protected ImageUploader $imageUploader;

    /**
     * @param Context $context
     * @param ImageUploader $imageUploader
     */
    public function __construct(
        Context $context,
        ImageUploader $imageUploader
    ) {
        $this->imageUploader = $imageUploader;
        parent::__construct($context);
    }

    /**
     * Upload the category icon to the temporary media directory.
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute(): \Magento\Framework\Controller\ResultInterface
    {
        $fileId = $this->getRequest()->getParam('param_name', 'icon');

        try {
            $result = $this->imageUploader->saveFileToTmpDir($fileId);
        } catch (\Exception $e) {
            $result = ['error' => $e->getMessage(), 'errorcode' => $e->getCode()];
        }

        /** @var \Magento\Framework\Controller\Result\Json $resultJson */
        $resultJson = $this->resultFactory->create(ResultFactory::TYPE_JSON);

        return $resultJson->setData($result);
    }
}
