(function($){
	'use strict';

	function nextLayoutIndex($container) {
		var index = 0;
		$container.closest('.acf-fc-field-layouts').find('.acf-fc-field-layout').each(function(){
			var name = $(this).find('input[name*="[name]"]').attr('name');
			var match = name && name.match(/\[layouts\]\[(.*?)\]/);
			if (match && /^\d+$/.test(match[1])) {
				index = Math.max(index, parseInt(match[1], 10) + 1);
			}
		});
		return index;
	}

	function createLayout($button) {
		var $settings = $button.closest('.acf-fc-field-layouts');
		var $container = $settings.find('.acf-fc-field-layout-list').first();
		var template = $settings.find('.acf-fc-layout-template').html();

		if (!template) {
			return;
		}

		var index = nextLayoutIndex($container);
		template = template.replace(/__INDEX__/g, index);
		template = template.replace(/layout_new/g, 'layout_' + index);
		template = template.replace(/acfcloneindex/g, 'acfcloneindex_' + index);
		$container.append(template);

		var $newLayout = $container.children('.acf-fc-field-layout').last();
		$newLayout.find('.acf-fc-layout-label').trigger('focus');
		acf.doAction('append', $newLayout);
	}

	$(document).on('click', '.acf-fc-add-layout', function(e){
		e.preventDefault();
		e.stopPropagation();
		createLayout($(this));
	});

	$(document).on('click', '.acf-fc-remove-layout', function(e){
		e.preventDefault();
		e.stopPropagation();
		$(this).closest('.acf-fc-field-layout').remove();
	});

	$(document).on('click', '.acf-fc-field-layout-title', function(){
		var $title = $(this);
		var isExpanded = $title.attr('aria-expanded') === 'true';
		$title.attr('aria-expanded', isExpanded ? 'false' : 'true');
		$title.closest('.acf-fc-field-layout').toggleClass('-collapsed', isExpanded);
	});

	$(document).on('input', '.acf-fc-layout-label', function(){
		var $layout = $(this).closest('.acf-fc-field-layout');
		var label = $(this).val();
		$layout.find('.acf-fc-field-layout-title').text(label || acf.__('New Layout'));

		var $name = $layout.find('input[name*="[name]"]');
		if (!$name.val()) {
			$name.val(label.toLowerCase().trim().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, ''));
		}
	});

	function tagSubFields($layout) {
		if (!$layout || !$layout.length) {
			return;
		}
		var layoutKey = $layout.attr('data-layout');
		var flexibleId = $layout.closest('.acf-fc-field-layouts').closest('.acf-field-object').attr('data-id');

		$layout.find('.acf-fc-field-layout-subfields .acf-field-object').each(function(){
			var $field = $(this);
			var $keyInput = $field.find('.meta input[name*="[key]"]').first();
			var keyName = $keyInput.attr('name');
			var prefix = keyName ? keyName.replace(/\[key\]$/, '') : '';
			if (!prefix) {
				return;
			}

			var $parentLayout = $field.find('.meta input[name="' + prefix + '[parent_layout]' + '"]');
			if (!$parentLayout.length) {
				$field.find('.meta').append(
					'<input type="hidden" name="' + prefix + '[parent_layout]" value="' + layoutKey + '">'
				);
			} else {
				$parentLayout.val(layoutKey);
			}

			if (flexibleId) {
				$field.find('.meta input[name="' + prefix + '[parent]' + '"]').val(flexibleId);
			}
		});
	}

	$(document).on('click', '.acf-fc-field-layout-subfields .add-field', function(){
		var $layout = $(this).closest('.acf-fc-field-layout');
		setTimeout(function(){ tagSubFields($layout); }, 20);
	});

	$(document).on('acf/append', function(e, $el){
		var $layout = $el && $el.closest ? $el.closest('.acf-fc-field-layout') : null;
		if ($layout && $layout.length) {
			tagSubFields($layout);
		}
	});

	$(function(){
		$('.acf-fc-field-layout').each(function(){
			tagSubFields($(this));
		});
	});
})(jQuery);
