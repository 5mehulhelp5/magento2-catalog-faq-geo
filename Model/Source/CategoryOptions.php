<?php
/**
 * Magendoo Faq Category options source
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model\Source;

use Magendoo\Faq\Model\ResourceModel\Category\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Populates the "Assign to FAQ Categories" multiselect on the question admin form.
 */
class CategoryOptions implements OptionSourceInterface
{
    /**
     * @param CollectionFactory $collectionFactory
     */
    public function __construct(
        private readonly CollectionFactory $collectionFactory
    ) {
    }

    /**
     * @inheritDoc
     *
     * @return array<int, array{value: int, label: string}>
     */
    public function toOptionArray(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addFieldToSelect(['category_id', 'name'])
            ->setOrder('position', 'ASC')
            ->setOrder('name', 'ASC');

        $options = [];
        foreach ($collection as $category) {
            $options[] = [
                'value' => (int) $category->getCategoryId(),
                'label' => (string) $category->getName(),
            ];
        }

        return $options;
    }
}
