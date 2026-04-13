/**
 * Magendoo FAQ Tab Deep-Link Fix
 *
 * Magento's Luma tabs widget deep-linking (`_handleDeepLinking`) marks the
 * correct tab title as `active` when the URL hash points at a tab content ID,
 * but does NOT reliably flip the content panel from `display:none` to visible.
 *
 * This component runs AFTER the tabs widget initialises (RequireJS dependency
 * ordering ensures this) and force-opens the content panel if the current
 * URL hash matches the element's ID.
 *
 * Usage (in layout XML or template):
 *   data-mage-init='{"faqTabDeeplink": {}}'
 *   on the content <div id="product_faq" data-role="content">
 */
define([
    'jquery'
], function ($) {
    'use strict';

    return function (config, element) {
        var $el = $(element),
            elId = $el.attr('id'),
            hash = window.location.hash.replace('#', '');

        if (!elId || hash !== elId) {
            return; // Hash doesn't target this element — nothing to do.
        }

        // Wait a tick so the Magento tabs/collapsible widgets finish init,
        // then force the content visible and mark the title as active.
        setTimeout(function () {
            // Show this content panel
            $el.show().css('display', '');
            $el.attr('aria-hidden', 'false');

            // Ensure the matching title div has the active class
            var $title = $('#tab-label-' + elId);
            if ($title.length) {
                $title.addClass('active');
                // Also trigger the collapsible widget's activate if available
                if ($title.data('mageFaqTabDeeplink') || $title.data('mageCollapsible')) {
                    try {
                        $title.collapsible('forceActivate');
                    } catch (e) {
                        // Widget not initialised yet — CSS fix above is enough
                    }
                }
            }

            // Deactivate other tab titles + hide other content panels
            $el.siblings('[data-role="content"]').each(function () {
                $(this).hide();
            });
            $title.siblings('[data-role="collapsible"]').each(function () {
                $(this).removeClass('active');
            });

            // Scroll the tab into view
            $('html, body').animate({
                scrollTop: $title.offset().top - 80
            }, 300);
        }, 200);
    };
});
