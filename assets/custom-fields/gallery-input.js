(function ($) {
	function getIds($field) {
		var value = $field.find('input[type="hidden"]').val() || '';
		var ids = [];

		$.each(value.split(','), function (_, item) {
			var id = parseInt(item, 10);
			if (id > 0 && ids.indexOf(id) === -1) {
				ids.push(id);
			}
		});

		return ids;
	}

	function setIds($field, ids) {
		$field.find('input[type="hidden"]').val(ids.join(','));
		renderGallery($field);
	}

	function syncIdsFromDom($field) {
		var ids = [];
		$field.find('.thumbnail').each(function () {
			var id = parseInt($(this).data('id'), 10);
			if (id > 0) {
				ids.push(id);
			}
		});
		$field.find('input[type="hidden"]').val(ids.join(','));
		$field.find('.toolbar .count').text(ids.length + ' ' + (ids.length === 1 ? 'image' : 'images'));
	}

	function renderGallery($field) {
		var $list = $field.find('.thumbnails .inner');
		var ids = getIds($field);
		var previewSize = $field.data('preview_size') || 'medium';

		// очистить список, но не вызывать fetch-рендер для каждой миниатюры, чтобы избежать «мигания"
		$list.empty();
		$field.find('.toolbar .count').text(ids.length + ' ' + (ids.length === 1 ? 'image' : 'images'));

		if (!ids.length) {
			return;
		}

		// Сначала создаём плейсхолдеры в точном порядке id
		$.each(ids, function (_, id) {
			var $placeholder = $('<div class="thumbnail loading" data-id="' + id + '" draggable="true">'
				+ '<span class="drag-handle" aria-hidden="true">⋮⋮</span>'
				+ '<div class="thumb-inner"></div>'
				+ '<div class="actions"><a href="#" class="acf-button-edit">Edit</a><a href="#" class="acf-button-delete">Remove</a></div>'
				+ '</div>');
			$list.append($placeholder);
		});

		// Затем асинхронно заполняем каждый плейсхолдер, но порядок остаётся прежним
		$.each(ids, function (_, id) {
			var attachment = wp.media.attachment(id);
			attachment.fetch().done(function () {
				var attrs = attachment.attributes || {};
				var url = attrs.url || '';
				var preview = attrs.sizes && attrs.sizes[previewSize] ? attrs.sizes[previewSize].url : url;
				var filename = attrs.filename || attrs.title || 'image';
				var $placeholder = $list.find('.thumbnail[data-id="' + id + '"]');
				if ($placeholder.length) {
					$placeholder.removeClass('loading');
					$placeholder.find('.thumb-inner').html('<img src="' + preview + '" alt="' + filename + '" />');
				}
			}).fail(function () {
				var $placeholder = $list.find('.thumbnail[data-id="' + id + '"]');
				if ($placeholder.length) {
					$placeholder.removeClass('loading');
					$placeholder.find('.thumb-inner').html('<div class="thumb-empty">Image not found</div>');
				}
			});
		});
	}

	function saveOrderFromDom($field) {
		var ids = [];
		$field.find('.thumbnails .inner .thumbnail').each(function () {
			var id = parseInt($(this).data('id'), 10);
			if (id > 0) {
				ids.push(id);
			}
		});
		$field.find('input[type="hidden"]').val(ids.join(','));
		$field.find('.toolbar .count').text(ids.length + ' ' + (ids.length === 1 ? 'image' : 'images'));
	}

	function reorderDom($field, sourceId, targetId) {
		var $items = $field.find('.thumbnails .inner .thumbnail');
		var $source = $items.filter('[data-id="' + sourceId + '"]');
		var $target = $items.filter('[data-id="' + targetId + '"]');

		if (!$source.length || !$target.length || sourceId === targetId) {
			return false;
		}

		var sourceIndex = $items.index($source);
		var targetIndex = $items.index($target);

		if (sourceIndex === -1 || targetIndex === -1) {
			return false;
		}

		if (sourceIndex < targetIndex) {
			$target.after($source);
		} else {
			$target.before($source);
		}

		return true;
	}

	$(document).on('click', '.acf-gallery .add-image', function (e) {
		e.preventDefault();

		var $field = $(this).closest('.acf-gallery');
		var ids = getIds($field);
		var frame = wp.media({
			title: 'Select Image',
			library: { type: 'image' },
			multiple: true,
			button: { text: 'Add to Gallery' }
		});

		frame.on('select', function () {
			var selected = frame.state().get('selection').toJSON();

			$.each(selected, function (_, item) {
				var id = parseInt(item.id, 10);
				if (id > 0 && ids.indexOf(id) === -1) {
					ids.push(id);
				}
			});

			setIds($field, ids);
		});

		frame.open();
	});

	$(document).on('click', '.acf-gallery .acf-button-delete', function (e) {
		e.preventDefault();

		var $field = $(this).closest('.acf-gallery');
		var id = parseInt($(this).closest('.thumbnail').data('id'), 10);
		var ids = getIds($field);

		ids = $.grep(ids, function (value) {
			return parseInt(value, 10) !== id;
		});

		setIds($field, ids);
	});

	$(document).on('click', '.acf-gallery .acf-button-edit', function (e) {
		e.preventDefault();

		var $field = $(this).closest('.acf-gallery');
		var id = parseInt($(this).closest('.thumbnail').data('id'), 10);
		var frame = wp.media({
			title: 'Edit Image',
			multiple: false,
			library: { post__in: [id], type: 'image' },
			button: { text: 'Update Image' }
		});

		frame.on('open', function () {
			var selection = frame.state().get('selection');
			var attachment = wp.media.attachment(id);
			attachment.fetch();
			selection.add(attachment);
		});

		frame.on('select', function () {
			renderGallery($field);
		});

		frame.open();
	});

	$(document).on('dragstart', '.acf-gallery .thumbnail', function (e) {
		var $thumb = $(this);
		var sourceId = parseInt($thumb.data('id'), 10);
		var dt = e.originalEvent && e.originalEvent.dataTransfer ? e.originalEvent.dataTransfer : null;

		if (dt) {
			dt.effectAllowed = 'move';
			dt.setData('text/plain', String(sourceId));
		}

		$thumb.closest('.acf-gallery').data('drag-id', sourceId);
		$thumb.addClass('is-dragging');
	});

	$(document).on('dragend', '.acf-gallery .thumbnail', function () {
		$(this).removeClass('is-dragging');
		$(this).closest('.acf-gallery').find('.thumbnail').removeClass('drag-over');
		$(this).closest('.acf-gallery').removeData('drag-id');
	});

	$(document).on('dragover', '.acf-gallery .thumbnail', function (e) {
		e.preventDefault();
		e.stopPropagation();
		if (e.originalEvent && e.originalEvent.dataTransfer) {
			e.originalEvent.dataTransfer.dropEffect = 'move';
		}
		$(this).addClass('drag-over');
	});

	$(document).on('dragleave', '.acf-gallery .thumbnail', function () {
		$(this).removeClass('drag-over');
	});

	$(document).on('drop', '.acf-gallery .thumbnail', function (e) {
		e.preventDefault();
		e.stopPropagation();

		var $field = $(this).closest('.acf-gallery');
		var sourceId = parseInt($field.data('drag-id'), 10);
		var targetId = parseInt($(this).data('id'), 10);

		if (!sourceId || !targetId || sourceId === targetId) {
			$(this).removeClass('drag-over');
			return;
		}

		if (reorderDom($field, sourceId, targetId)) {
			saveOrderFromDom($field);
		}

		$field.find('.thumbnail').removeClass('drag-over');
		$field.removeData('drag-id');
	});

	$(document).on('acf/setup_fields', function (e, el) {
		$(el).find('.acf-gallery').each(function () {
			renderGallery($(this));
		});
	});

	$(document).ready(function () {
		$('.acf-gallery').each(function () {
			renderGallery($(this));
		});
	});
})(jQuery);
