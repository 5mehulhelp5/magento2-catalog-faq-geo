/**
 * Magendoo Faq Ask Form Gating & Prefill
 *
 * The block always renders the form server-side; the visitor-specific parts
 * happen here, from the `customer` customer-data section (the same source the
 * theme's welcome message uses). Server-side CustomerSession reads are useless
 * on cacheable pages because core depersonalizes the session before blocks
 * render, and their result would be frozen into the page cache.
 *
 * Note: the customer section exposes fullname/firstname only, never the email
 * address, so only the name field can be prefilled.
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

define([
    'jquery',
    'Magento_Customer/js/customer-data',
    'mage/translate'
], function ($, customerData, $t) {
    'use strict';

    return function (config, element) {
        var $form = $(element),
            $wrapper = $form.closest('.faq-ask-form-wrapper'),
            $notice = null,
            customer = customerData.get('customer');

        if (!$wrapper.length) {
            $wrapper = $form;
        }

        /**
         * Build (once) the "please log in" notice shown to guests when guest
         * questions are disabled — same markup the template uses.
         *
         * @returns {jQuery}
         */
        function getNotice() {
            if ($notice === null) {
                $notice = $('<div class="faq-ask-login-required"></div>').hide().insertBefore($wrapper);
                $('<p></p>')
                    .append(document.createTextNode($t('Please') + ' '))
                    .append($('<a></a>').attr('href', config.loginUrl).text($t('log in')))
                    .append(document.createTextNode(' ' + $t('to ask a question.')))
                    .appendTo($notice);
            }

            return $notice;
        }

        /**
         * Apply the current customer state to the form.
         *
         * @param {Object} data - customer section data
         */
        function apply(data) {
            var loggedIn = !!(data && data.firstname),
                $name;

            if (loggedIn && data.fullname) {
                $name = $form.find('input[name="sender_name"]');

                if ($name.length && !$name.val()) {
                    $name.val(data.fullname);
                }
            }

            if (loggedIn || config.allowGuest) {
                if ($notice !== null) {
                    $notice.hide();
                }
                $wrapper.show();
            } else {
                $wrapper.hide();
                getNotice().show();
            }
        }

        if (!config.allowGuest) {
            // Hide immediately to keep the gated form from flashing for guests.
            $wrapper.hide();
        }

        apply(customer());
        customer.subscribe(apply);
    };
});
