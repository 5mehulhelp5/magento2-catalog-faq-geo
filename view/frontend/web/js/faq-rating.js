/**
 * Magendoo Faq Rating JS
 *
 * @category  Magendoo
 * @package   Magendoo_Faq
 * @copyright Copyright (c) Magendoo (https://magendoo.ro)
 * @license   https://opensource.org/licenses/MIT MIT License
 */

define([
    'jquery',
    'mage/cookies'
], function ($) {
    'use strict';

    return function (config, element) {
        var $el = $(element);

        $el.on('click', '.faq-rate', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var vote = $btn.data('vote');
            var url = $btn.data('url');
            var questionId = $el.data('question-id');

            if ($btn.hasClass('voted') || $el.hasClass('voted')) {
                return;
            }

            $btn.prop('disabled', true);

            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: {
                    question_id: questionId,
                    vote_type: vote,
                    form_key: $.mage.cookies.get('form_key')
                }
            }).done(function (response) {
                if (response && response.success) {
                    var $count = $btn.find('.count');
                    var current = parseInt(($count.text() || '(0)').replace(/[^0-9]/g, ''), 10) || 0;
                    $count.text('(' + (current + 1) + ')');
                    $btn.addClass('voted');
                    $el.addClass('voted');
                }
            }).always(function () {
                $btn.prop('disabled', false);
            });
        });

        $el.on('click', '.faq-star', function (e) {
            e.preventDefault();

            var $btn = $(this);
            var value = parseInt($btn.data('vote'), 10);
            var url = $btn.data('url');
            var questionId = $el.data('question-id');
            var $stars = $el.find('.faq-star');

            if ($el.hasClass('voted')) {
                return;
            }

            $stars.prop('disabled', true);

            $.ajax({
                url: url,
                type: 'POST',
                dataType: 'json',
                data: {
                    question_id: questionId,
                    // The service reads the star value from vote_type ("1".."5").
                    vote_type: String(value),
                    form_key: $.mage.cookies.get('form_key')
                }
            }).done(function (response) {
                if (response && response.success) {
                    $el.addClass('voted');
                    $stars.each(function () {
                        var starValue = parseInt($(this).data('vote'), 10);

                        $(this)
                            .toggleClass('filled', starValue <= value)
                            .text(starValue <= value ? '★' : '☆');
                    });
                } else {
                    $stars.prop('disabled', false);
                }
            }).fail(function () {
                $stars.prop('disabled', false);
            });
        });
    };
});
