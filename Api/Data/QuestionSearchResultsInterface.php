<?php
/**
 * Magendoo Faq Question Search Results Interface
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Api\Data;

/**
 * Interface for FAQ question search results
 *
 * @api
 */
interface QuestionSearchResultsInterface extends PublicQuestionSearchResultsInterface
{
    /**
     * Get questions list
     *
     * @return \Magendoo\Faq\Api\Data\QuestionInterface[]
     */
    public function getItems(): array;

    /**
     * Set questions list
     *
     * @param \Magendoo\Faq\Api\Data\QuestionInterface[] $items
     * @return $this
     */
    public function setItems(array $items): static;
}
