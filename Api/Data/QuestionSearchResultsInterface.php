<?php
/**
 * Magendoo Faq Question Search Results Interface
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
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
