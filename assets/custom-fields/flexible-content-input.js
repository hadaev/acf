(function($){
	var Field = acf.Field.extend({
		type: 'flexible_content',
		events: {
			'click [data-event="add-layout"]': 'onAdd',
			'click [data-event="remove-layout"]': 'onRemove'
		},
		$control: function(){ return this.$('.acf-flexible-content:first'); },
		$layouts: function(){ return this.$('.acf-fc-layouts:first'); },
		$rows: function(){ return this.$layouts().children('.acf-fc-layout').not('.acf-clone'); },
		getFieldName: function(){
			return this.$control().children('input[type="hidden"][name]').first().attr('name') || '';
		},
		reindexRows: function(){
			var fieldName = this.getFieldName();
			if (!fieldName) return;

			var pattern = new RegExp('^' + fieldName.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\[[^\\]]+\\]');
			this.$rows().each(function(index){
				var $row = $(this);
				$row.attr('data-id', index);
				$row.find('[name]').addBack('[name]').each(function(){
					var $input = $(this);
					var name = $input.attr('name');
					if (pattern.test(name)) {
						$input.attr('name', name.replace(pattern, fieldName + '[' + index + ']'));
					}
				});
			});
		},
		addSortable: function(){
			if (!$.fn.sortable) return;
			this.$layouts().sortable({
				items: '> .acf-fc-layout:not(.acf-clone)',
				handle: '.acf-fc-layout-drag',
				cancel: 'input, textarea, select, button, a, .acf-fc-layout-label',
				axis: 'y',
				tolerance: 'pointer',
				placeholder: 'acf-fc-layout-placeholder',
				forcePlaceholderSize: true,
				start: function(e, ui){
					ui.placeholder.height(ui.item.outerHeight());
				},
				update: this.proxy(function(){
					this.reindexRows();
					this.$input().trigger('change');
				})
			});
		},
		getValue: function(){ return this.$rows().length; },
		initialize: function(){
			acf.disable(this.$control().find('> .acf-fc-layout.acf-clone'), this.cid);
			this.addSortable();
			this.reindexRows();
			this.render();
		},
		render: function(){
			var max = parseInt(this.get('max')) || 0;
			this.$('.acf-actions .button').toggleClass('disabled', !!(max && this.val() >= max));
		},
		onAdd: function(e, $el){
			e.preventDefault();
			var layout = $el.data('layout');
			var max = parseInt(this.get('max')) || 0;
			if (max && this.val() >= max) return;

			var $clone = this.$control().children('.acf-fc-layout.acf-clone[data-layout="' + layout + '"]').first();
			if (!$clone.length) return;
			var $row = acf.duplicate({
				target: $clone,
				append: this.proxy(function($el, $el2){
					this.$layouts().append($el2);
				})
			});
			$row.removeClass('acf-clone').attr('data-id', this.$rows().length - 1);
			acf.enable($row, this.cid);
			this.reindexRows();
			this.render();
			this.$input().trigger('change');
		},
		onRemove: function(e, $el){
			e.preventDefault();
			var min = parseInt(this.get('min')) || 0;
			if (min && this.val() <= min) return;
			var $row = $el.closest('.acf-fc-layout');
			acf.remove($row);
			this.reindexRows();
			this.render();
			this.$input().trigger('change');
		}
	});
	acf.registerFieldType(Field);
})(jQuery);
