<?php
/**
 * Magendoo Faq Category Search Results
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model;

use Magento\Framework\Api\SearchResults;
use Magendoo\Faq\Api\Data\CategorySearchResultsInterface;

/**
 * FAQ Category Search Results
 */
class CategorySearchResults extends SearchResults implements CategorySearchResultsInterface
{
    /**
     * @inheritdoc
     */
    public function getItems(): array
    {
        return parent::getItems();
    }

    /**
     * @inheritdoc
     */
    public function setItems(array $items): static
    {
        return parent::setItems($items);
    }
}
