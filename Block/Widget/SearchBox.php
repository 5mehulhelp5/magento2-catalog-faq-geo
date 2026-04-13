<?php
/**
 * Magendoo FAQ Search Box Widget Block
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Block\Widget;

use Magendoo\Faq\Helper\Data as FaqHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Widget\Block\BlockInterface;

/**
 * FAQ Search Box Widget
 */
class SearchBox extends Template implements BlockInterface
{
    /**
     * @var FaqHelper
     */
    private FaqHelper $helper;

    /**
     * @param Context $context
     * @param FaqHelper $helper
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        FaqHelper $helper,
        array $data = []
    ) {
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Get FAQ search URL
     *
     * @return string
     */
    public function getSearchUrl(): string
    {
        return $this->getUrl('faq/question/search');
    }

    /**
     * Get search placeholder text
     *
     * @return string
     */
    public function getPlaceholder(): string
    {
        return (string) $this->getData('placeholder');
    }

    /**
     * Get block title
     *
     * @return string
     */
    public function getBlockTitle(): string
    {
        return (string) $this->getData('title');
    }
}
