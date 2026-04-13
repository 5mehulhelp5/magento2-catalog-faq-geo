/**
 * Magendoo Faq Search Autocomplete
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.com)
 * @license   https://opensource.org/licenses/OSL-3.0 Open Software License v. 3.0 (OSL-3.0)
 */

define(['jquery'], function ($) {
    'use strict';

    return function (config, element) {
        var $input = $(element).find('input[name="q"]');
        var $dropdown = $('<div class="faq-autocomplete-dropdown"></div>').insertAfter($input).hide();
        var timer;

        $input.on('keyup', function () {
            clearTimeout(timer);
            var q = $input.val().trim();

            if (q.length < 2) {
                $dropdown.hide();
                return;
            }

            timer = setTimeout(function () {
                $.getJSON(config.suggestUrl, { q: q }, function (data) {
                    if (!data.length) {
                        $dropdown.hide();
                        return;
                    }

                    var html = '';

                    data.forEach(function (item) {
                        html += '<a href="' + item.url + '" class="faq-suggest-item">' +
                            $('<span>').text(item.title).html() +
                            '</a>';
                    });
                    $dropdown.html(html).show();
                });
            }, 300);
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('.faq-autocomplete-dropdown, input[name="q"]').length) {
                $dropdown.hide();
            }
        });
    };
});
