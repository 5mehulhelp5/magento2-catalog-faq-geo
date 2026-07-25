<?php
/**
 * Magendoo Faq Breadcrumbs Block
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Block\Faq;

use Magendoo\Faq\Api\Data\CategoryInterface;
use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * FAQ Breadcrumbs Block
 */
class Breadcrumbs extends Template
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
     * Add FAQ home breadcrumb: Home > FAQ
     *
     * @return void
     */
    public function addFaqBreadcrumb(): void
    {
        $breadcrumbsBlock = $this->getBreadcrumbsBlock();
        if (!$breadcrumbsBlock) {
            return;
        }

        $breadcrumbsBlock->addCrumb('home', [
            'label' => __('Home'),
            'title' => __('Go to Home Page'),
            'link' => $this->getBaseUrl(),
        ]);

        $breadcrumbsBlock->addCrumb('faq', [
            'label' => __($this->helper->getTitle()),
            'title' => __($this->helper->getTitle()),
        ]);
    }

    /**
     * Add category breadcrumb: Home > FAQ > Category Name
     *
     * @param CategoryInterface $category
     * @return void
     */
    public function addCategoryBreadcrumb(CategoryInterface $category): void
    {
        $breadcrumbsBlock = $this->getBreadcrumbsBlock();
        if (!$breadcrumbsBlock) {
            return;
        }

        $breadcrumbsBlock->addCrumb('home', [
            'label' => __('Home'),
            'title' => __('Go to Home Page'),
            'link' => $this->getBaseUrl(),
        ]);

        $faqUrl = $this->getBaseUrl() . $this->helper->getUrlPrefix();
        $breadcrumbsBlock->addCrumb('faq', [
            'label' => __($this->helper->getTitle()),
            'title' => __($this->helper->getTitle()),
            'link' => $faqUrl,
        ]);

        $breadcrumbsBlock->addCrumb('category', [
            'label' => $category->getName(),
            'title' => $category->getName(),
        ]);
    }

    /**
     * Add question breadcrumb: Home > FAQ > Category > Question
     *
     * @param QuestionInterface $question
     * @param CategoryInterface|null $category
     * @return void
     */
    public function addQuestionBreadcrumb(QuestionInterface $question, ?CategoryInterface $category = null): void
    {
        $breadcrumbsBlock = $this->getBreadcrumbsBlock();
        if (!$breadcrumbsBlock) {
            return;
        }

        $breadcrumbsBlock->addCrumb('home', [
            'label' => __('Home'),
            'title' => __('Go to Home Page'),
            'link' => $this->getBaseUrl(),
        ]);

        $faqUrl = $this->getBaseUrl() . $this->helper->getUrlPrefix();
        $breadcrumbsBlock->addCrumb('faq', [
            'label' => __($this->helper->getTitle()),
            'title' => __($this->helper->getTitle()),
            'link' => $faqUrl,
        ]);

        if ($category) {
            $categoryUrlKey = $category->getUrlKey();
            $categoryUrl = $categoryUrlKey
                ? $this->getBaseUrl() . $this->helper->buildUrlPath($categoryUrlKey)
                : $this->getUrl('faq/category/view', ['id' => $category->getCategoryId()]);

            $breadcrumbsBlock->addCrumb('category', [
                'label' => $category->getName(),
                'title' => $category->getName(),
                'link' => $categoryUrl,
            ]);
        }

        $breadcrumbsBlock->addCrumb('question', [
            'label' => $question->getTitle(),
            'title' => $question->getTitle(),
        ]);
    }

    /**
     * Add search breadcrumb: Home > FAQ > Search Results
     *
     * @return void
     */
    public function addSearchBreadcrumb(): void
    {
        $breadcrumbsBlock = $this->getBreadcrumbsBlock();
        if (!$breadcrumbsBlock) {
            return;
        }

        $breadcrumbsBlock->addCrumb('home', [
            'label' => __('Home'),
            'title' => __('Go to Home Page'),
            'link' => $this->getBaseUrl(),
        ]);

        $faqUrl = $this->getBaseUrl() . $this->helper->getUrlPrefix();
        $breadcrumbsBlock->addCrumb('faq', [
            'label' => __($this->helper->getTitle()),
            'title' => __($this->helper->getTitle()),
            'link' => $faqUrl,
        ]);

        $breadcrumbsBlock->addCrumb('search', [
            'label' => __('Search Results'),
            'title' => __('Search Results'),
        ]);
    }

    /**
     * Get breadcrumbs block from layout
     *
     * @return \Magento\Theme\Block\Html\Breadcrumbs|null
     */
    private function getBreadcrumbsBlock(): ?\Magento\Theme\Block\Html\Breadcrumbs
    {
        if (!$this->helper->isShowBreadcrumbs()) {
            return null;
        }

        /** @var \Magento\Theme\Block\Html\Breadcrumbs|null $block */
        $block = $this->getLayout()->getBlock('breadcrumbs');

        return $block;
    }
}
