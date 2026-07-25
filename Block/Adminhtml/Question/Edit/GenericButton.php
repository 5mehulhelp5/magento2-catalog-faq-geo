<?php
/**
 * Magendoo Faq Question Generic Button
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

declare(strict_types=1);

namespace Magendoo\Faq\Block\Adminhtml\Question\Edit;

use Magento\Backend\Block\Widget\Context;
use Magendoo\Faq\Api\QuestionRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;

/**
 * Generic button base class for question edit form
 */
class GenericButton
{
    /**
     * @var Context
     */
    protected Context $context;

    /**
     * @var QuestionRepositoryInterface
     */
    protected QuestionRepositoryInterface $questionRepository;

    /**
     * @param Context $context
     * @param QuestionRepositoryInterface $questionRepository
     */
    public function __construct(
        Context $context,
        QuestionRepositoryInterface $questionRepository
    ) {
        $this->context = $context;
        $this->questionRepository = $questionRepository;
    }

    /**
     * Return question ID
     *
     * @return int|null
     */
    public function getQuestionId(): ?int
    {
        $questionId = $this->context->getRequest()->getParam('question_id');

        // Return null for new entity creation (no ID in request)
        if (!$questionId) {
            return null;
        }

        try {
            $id = $this->questionRepository->getById((int) $questionId)->getQuestionId();
            return $id ? (int) $id : null;
        } catch (NoSuchEntityException $e) {
            return null;
        }
    }

    /**
     * Generate url by route and parameters
     *
     * @param string $route
     * @param array $params
     * @return string
     */
    public function getUrl(string $route = '', array $params = []): string
    {
        return $this->context->getUrlBuilder()->getUrl($route, $params);
    }
}
