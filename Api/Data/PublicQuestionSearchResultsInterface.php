<?php
/**
 * Magendoo Faq Public Question Search Results Interface
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Api\Data;

use Magento\Framework\Api\SearchResultsInterface;

/**
 * Search results carrying the storefront-safe question projection.
 *
 * Declared return type of the anonymous web API list routes so the serialised
 * items expose PublicQuestionInterface fields only.
 *
 * @api
 */
interface PublicQuestionSearchResultsInterface extends SearchResultsInterface
{
    /**
     * Get questions list
     *
     * @return \Magendoo\Faq\Api\Data\PublicQuestionInterface[]
     */
    public function getItems(): array;

    /**
     * Set questions list
     *
     * @param \Magendoo\Faq\Api\Data\PublicQuestionInterface[] $items
     * @return $this
     */
    public function setItems(array $items): static;
}
