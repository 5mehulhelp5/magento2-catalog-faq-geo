<?php
/**
 * Magendoo Faq Question Search Results
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Model;

use Magento\Framework\Api\SearchResults;
use Magendoo\Faq\Api\Data\QuestionSearchResultsInterface;

/**
 * FAQ Question Search Results
 */
class QuestionSearchResults extends SearchResults implements QuestionSearchResultsInterface
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
