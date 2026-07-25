<?php
/**
 * Magendoo Faq Category Delete Button
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Block\Adminhtml\Category\Edit;

use Magento\Framework\View\Element\UiComponent\Control\ButtonProviderInterface;

/**
 * Delete button for category edit form
 */
class DeleteButton extends GenericButton implements ButtonProviderInterface
{
    /**
     * @inheritdoc
     */
    public function getButtonData(): array
    {
        $data = [];
        $categoryId = $this->getCategoryId();
        if ($categoryId) {
            $confirmMessage = $this->context->getEscaper()->escapeJs(
                $this->context->getEscaper()->escapeHtml(__('Are you sure you want to delete this category?'))
            );
            $data = [
                'label' => __('Delete'),
                'class' => 'delete',
                'on_click' => 'deleteConfirm(\'' . $confirmMessage . '\', \''
                    . $this->getDeleteUrl() . '\', {"data": {}})',
                'sort_order' => 20,
            ];
        }
        return $data;
    }

    /**
     * Get delete URL
     *
     * @return string
     */
    public function getDeleteUrl(): string
    {
        return $this->getUrl('*/*/delete', ['category_id' => $this->getCategoryId()]);
    }
}
