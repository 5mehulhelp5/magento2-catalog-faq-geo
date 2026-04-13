<?php
/**
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model;

use Magendoo\Faq\Api\Data\TagSearchResultsInterface;
use Magento\Framework\Api\SearchResults;

class TagSearchResults extends SearchResults implements TagSearchResultsInterface
{
    /**
     * @inheritDoc
     *
     * @return \Magendoo\Faq\Api\Data\TagInterface[]
     */
    public function getItems(): array
    {
        return parent::getItems() ?? [];
    }

    /**
     * @inheritDoc
     *
     * @param \Magendoo\Faq\Api\Data\TagInterface[] $items
     * @return $this
     */
    public function setItems(array $items): static
    {
        return parent::setItems($items);
    }
}
