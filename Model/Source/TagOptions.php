<?php
/**
 * Magendoo Faq Tag options source
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model\Source;

use Magendoo\Faq\Model\ResourceModel\Tag\CollectionFactory;
use Magento\Framework\Data\OptionSourceInterface;

/**
 * Populates the "Tags" multiselect on the question admin form.
 */
class TagOptions implements OptionSourceInterface
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
        $collection->addFieldToSelect(['tag_id', 'name'])
            ->setOrder('name', 'ASC');

        $options = [];
        foreach ($collection as $tag) {
            $options[] = [
                'value' => (int) $tag->getTagId(),
                'label' => (string) $tag->getName(),
            ];
        }

        return $options;
    }
}
