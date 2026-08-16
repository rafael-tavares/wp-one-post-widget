(function ($) {
	'use strict';

	function initOnePostAutocomplete(context) {
		var $context = context ? $(context) : $(document);

		$context.find('.wp-one-post-widget-search').each(function () {
			var $input = $(this);
			if ($input.data('wpOnePostAutocomplete')) {
				return;
			}

			var $form = $input.closest('.wp-one-post-widget-form');
			var $idField = $form.find('.wp-one-post-widget-post-id');

			$input.data('wpOnePostAutocomplete', true);

			$input.autocomplete({
				minLength: 1,
				delay: 200,
				source: function (request, response) {
					$.ajax({
						url: wpOnePostWidget.ajaxUrl,
						method: 'GET',
						dataType: 'json',
						data: {
							action: 'wp_one_post_widget_search',
							nonce: wpOnePostWidget.nonce,
							term: request.term
						}
					})
						.done(function (data) {
							response($.isArray(data) ? data : []);
						})
						.fail(function () {
							response([]);
						});
				},
				select: function (event, ui) {
					if (!ui.item) {
						return false;
					}
					$idField.val(ui.item.id || '');
					$input.val(ui.item.label || '');
					$input.trigger('change');
					return false;
				},
				focus: function (event, ui) {
					// Keep keyboard highlight without replacing the typed query prematurely.
					if (ui.item && ui.item.label) {
						event.preventDefault();
					}
				}
			});
		});
	}

	$(function () {
		initOnePostAutocomplete(document);
	});

	$(document).on('widget-added widget-updated', function (event, widget) {
		initOnePostAutocomplete(widget);
	});
})(jQuery);
