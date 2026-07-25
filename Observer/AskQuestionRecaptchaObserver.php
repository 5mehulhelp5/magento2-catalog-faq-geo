<?php
/**
 * Magendoo Faq reCAPTCHA observer for the "Ask a Question" form.
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Observer;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\App\Response\Http as HttpResponse;
use Magento\Framework\App\Response\RedirectInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\ReCaptchaUi\Model\IsCaptchaEnabledInterface;
use Magento\ReCaptchaUi\Model\RequestHandlerInterface;

/**
 * Validates Google reCAPTCHA on the FAQ "Ask a Question" submit endpoint.
 *
 * Mirrors the official Magento_ReCaptchaReview pattern: registered against
 * `controller_action_predispatch_faq_question_submit`, it checks the
 * configurable type for our integration key and short-circuits the request
 * with a redirect on failure.
 */
class AskQuestionRecaptchaObserver implements ObserverInterface
{
    /**
     * Integration key — must match the field id added under
     * `recaptcha_frontend/type_for/...` in adminhtml/system.xml AND the
     * `recaptcha_for` argument on the layout block.
     */
    public const RECAPTCHA_KEY = 'magendoo_faq_question_submit';

    /**
     * @param RedirectInterface $redirect
     * @param IsCaptchaEnabledInterface $isCaptchaEnabled
     * @param RequestHandlerInterface $requestHandler
     * @param RequestInterface $request
     * @param HttpResponse $response
     */
    public function __construct(
        private readonly RedirectInterface $redirect,
        private readonly IsCaptchaEnabledInterface $isCaptchaEnabled,
        private readonly RequestHandlerInterface $requestHandler,
        private readonly RequestInterface $request,
        private readonly HttpResponse $response
    ) {
    }

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        if (!$this->isCaptchaEnabled->isCaptchaEnabledFor(self::RECAPTCHA_KEY)) {
            return;
        }

        // The dispatched controller implements only ActionInterface::execute(); it has no
        // getRequest()/getResponse(). Take both from dependency injection instead — calling
        // them on the action instance was a fatal error whenever a captcha was configured.
        $redirectOnFailureUrl = (string) $this->redirect->getRedirectUrl();

        $this->requestHandler->execute(
            self::RECAPTCHA_KEY,
            $this->request,
            $this->response,
            $redirectOnFailureUrl
        );
    }
}
