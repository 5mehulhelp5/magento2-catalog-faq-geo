<?php
/**
 * Magendoo Faq Question View Block
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Block\Faq\Question;

use Magendoo\Faq\Api\CategoryRepositoryInterface;
use Magendoo\Faq\Api\Data\CategoryInterface;
use Magendoo\Faq\Api\Data\QuestionInterface;
use Magendoo\Faq\Api\QuestionRepositoryInterface;
use Magendoo\Faq\Helper\Data as FaqHelper;
use Magendoo\Faq\Model\Question as QuestionModel;
use Magento\Framework\DataObject\IdentityInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * FAQ Question View Block
 */
class View extends Template implements IdentityInterface
{
    /**
     * @var QuestionRepositoryInterface
     */
    private QuestionRepositoryInterface $questionRepository;

    /**
     * @var CategoryRepositoryInterface
     */
    private CategoryRepositoryInterface $categoryRepository;

    /**
     * @var FaqHelper
     */
    private FaqHelper $helper;

    /**
     * @var QuestionInterface|null
     */
    private ?QuestionInterface $question = null;

    /**
     * @var CategoryInterface|null|false
     */
    private CategoryInterface|null|false $category = false;

    /**
     * @param Context $context
     * @param QuestionRepositoryInterface $questionRepository
     * @param CategoryRepositoryInterface $categoryRepository
     * @param FaqHelper $helper
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        QuestionRepositoryInterface $questionRepository,
        CategoryRepositoryInterface $categoryRepository,
        FaqHelper $helper,
        array $data = []
    ) {
        $this->questionRepository = $questionRepository;
        $this->categoryRepository = $categoryRepository;
        $this->helper = $helper;
        parent::__construct($context, $data);
    }

    /**
     * Get current question
     *
     * @return QuestionInterface|null
     */
    public function getQuestion(): ?QuestionInterface
    {
        if ($this->question === null) {
            $questionId = (int) $this->getRequest()->getParam('id');
            if ($questionId) {
                try {
                    $this->question = $this->questionRepository->getById($questionId);
                } catch (NoSuchEntityException $e) {
                    $this->question = null;
                }
            }
        }

        return $this->question;
    }

    /**
     * Get category for breadcrumbs
     *
     * @return CategoryInterface|null
     */
    public function getCategory(): ?CategoryInterface
    {
        if ($this->category === false) {
            $categoryId = (int) $this->getRequest()->getParam('category_id');
            if ($categoryId) {
                try {
                    $this->category = $this->categoryRepository->getById($categoryId);
                } catch (NoSuchEntityException $e) {
                    $this->category = null;
                }
            } else {
                $this->category = null;
            }
        }

        return $this->category;
    }

    /**
     * Return identifiers for produced content
     *
     * The page renders the question plus (for breadcrumbs) its category, so both
     * entities' identities are stamped; the bare question tag is the fallback when
     * nothing could be loaded, mirroring
     * \Magento\CatalogWidget\Block\Product\ProductsList::getIdentities().
     *
     * @return string[]
     */
    public function getIdentities(): array
    {
        $identities = [];

        $question = $this->getQuestion();
        if ($question instanceof IdentityInterface) {
            $identities[] = $question->getIdentities();
        }

        $category = $this->getCategory();
        if ($category instanceof IdentityInterface) {
            $identities[] = $category->getIdentities();
        }

        $identities = array_merge([], ...$identities);

        return $identities ?: [QuestionModel::CACHE_TAG];
    }

    /**
     * Check if rating is enabled
     *
     * @return bool
     */
    public function isRatingEnabled(): bool
    {
        return $this->helper->isRatingEnabled();
    }

    /**
     * Get rating type
     *
     * @return string
     */
    public function getRatingType(): string
    {
        return $this->helper->getRatingType();
    }

    /**
     * Get structured data as JSON-LD array
     *
     * @return array<string, mixed>
     */
    public function getStructuredData(): array
    {
        $question = $this->getQuestion();
        if (!$question) {
            return [];
        }

        return [
            '@context' => 'https://schema.org',
            '@type' => 'FAQPage',
            'mainEntity' => [
                [
                    '@type' => 'Question',
                    'name' => $question->getTitle(),
                    'acceptedAnswer' => [
                        '@type' => 'Answer',
                        'text' => strip_tags($question->getFullAnswer() ?? ''),
                    ],
                ],
            ],
        ];
    }
}
