<?php
/**
 * Magendoo Faq Ask Question Form Block
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

declare(strict_types=1);

namespace Magendoo\Faq\Block\Faq\Question;

use Magendoo\Faq\Helper\Data as FaqHelper;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Registry;
use Magento\Framework\Serialize\Serializer\Json;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

/**
 * Ask a Question form block
 *
 * The form is always rendered server-side. Login gating and customer prefill
 * happen in the browser (Magendoo_Faq/js/faq-ask-form) from the `customer`
 * customer-data section: on cacheable pages core's DepersonalizePlugin
 * (vendor/magento/module-customer/Model/Layout/DepersonalizePlugin.php) clears
 * the customer session before any block renders, so a server-side
 * CustomerSession::isLoggedIn() check is always false and whatever it decides
 * gets baked into the page cache for every visitor of the same cache variant.
 * The submit controller still enforces the guest policy server-side on the
 * non-cacheable POST.
 */
class AskForm extends Template
{
    /**
     * @var FaqHelper
     */
    private FaqHelper $helper;

    /**
     * @var ProductMetadataInterface
     */
    private ProductMetadataInterface $productMetadata;

    /**
     * @var Registry
     */
    private Registry $registry;

    /**
     * @var Json
     */
    private Json $jsonSerializer;

    /**
     * @param Context $context
     * @param FaqHelper $helper
     * @param ProductMetadataInterface $productMetadata
     * @param Registry $registry
     * @param Json $jsonSerializer
     * @param array<string, mixed> $data
     */
    public function __construct(
        Context $context,
        FaqHelper $helper,
        ProductMetadataInterface $productMetadata,
        Registry $registry,
        Json $jsonSerializer,
        array $data = []
    ) {
        $this->helper = $helper;
        $this->productMetadata = $productMetadata;
        $this->registry = $registry;
        $this->jsonSerializer = $jsonSerializer;
        parent::__construct($context, $data);
    }

    /**
     * Check if unregistered visitors may submit questions.
     *
     * @return bool
     */
    public function isGuestAllowed(): bool
    {
        return $this->helper->isGuestQuestionAllowed();
    }

    /**
     * Whether the form should be rendered for the current visitor.
     *
     * Always true: the visitor-specific decision must not be made while
     * rendering a cacheable page (see the class comment), so the markup is
     * always emitted and the client-side component shows either the form or
     * the login notice.
     *
     * @return bool
     */
    public function canShowForm(): bool
    {
        return true;
    }

    /**
     * Get the full name of the logged in customer, empty string otherwise.
     *
     * Always empty: the customer session is depersonalized during rendering,
     * so the prefill is done client-side from the customer-data section. Kept
     * because the template renders it as the field's initial value.
     *
     * @return string
     */
    public function getLoggedInCustomerName(): string
    {
        return '';
    }

    /**
     * Get the email of the logged in customer, empty string otherwise.
     *
     * Always empty — see getLoggedInCustomerName().
     *
     * @return string
     */
    public function getLoggedInCustomerEmail(): string
    {
        return '';
    }

    /**
     * Get the current product id when on a product page.
     *
     * @return int|null
     */
    public function getCurrentProductId(): ?int
    {
        $product = $this->registry->registry('current_product');
        if ($product && $product->getId()) {
            return (int) $product->getId();
        }

        return null;
    }

    /**
     * Get the URL to submit the form to.
     *
     * @return string
     */
    public function getSubmitUrl(): string
    {
        return $this->getUrl('faq/question/submit');
    }

    /**
     * Check if GDPR consent is enabled.
     *
     * @return bool
     */
    public function isGdprEnabled(): bool
    {
        return $this->helper->isGdprEnabled();
    }

    /**
     * Get the configured GDPR text.
     *
     * @return string
     */
    public function getGdprText(): string
    {
        return $this->helper->getGdprText();
    }

    /**
     * Get the customer account login URL.
     *
     * @return string
     */
    public function getLoginUrl(): string
    {
        return $this->getUrl('customer/account/login');
    }

    /**
     * Get the Magento product version (useful for cache-busting / diagnostics).
     *
     * @return string
     */
    public function getMagentoVersion(): string
    {
        return $this->productMetadata->getVersion();
    }

    /**
     * Append the client-side gating/prefill initializer to the rendered form.
     *
     * @param string $html
     * @return string
     */
    protected function _afterToHtml($html)
    {
        if (trim((string) $html) === '') {
            return $html;
        }

        $config = [
            '#faq-ask-form' => [
                'faqAskForm' => [
                    'allowGuest' => $this->isGuestAllowed(),
                    'loginUrl' => $this->getLoginUrl(),
                ],
            ],
        ];

        return $html
            . '<script type="text/x-magento-init">'
            . $this->jsonSerializer->serialize($config)
            . '</script>';
    }
}
