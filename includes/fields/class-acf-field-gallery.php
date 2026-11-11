<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'acf_field_gallery' ) ) :

class acf_field_gallery extends acf_field {

	public function initialize() {
		$this->name = 'gallery';
		$this->label = __( 'Gallery', 'acf' );
		$this->category = 'content';
		$this->description = __( 'An interactive interface for managing a collection of attachments.', 'acf' );
		$this->defaults = array(
			'return_format' => 'array',
			'preview_size' => 'medium',
			'library' => 'all',
			'min' => 0,
			'max' => 0,
		);
	}

	public function input_admin_enqueue_scripts() {
		wp_enqueue_media();
		wp_enqueue_script( 'acf-gallery-input', acf_get_url( 'assets/custom-fields/gallery-input.js' ), array( 'jquery', 'wp-mediaelement' ), '1.0.0', true );
		wp_enqueue_style( 'acf-gallery-input', acf_get_url( 'assets/custom-fields/gallery-input.css' ), array(), '1.0.0' );

		acf_localize_text(
			array(
				'Select Image' => __( 'Select Image', 'acf' ),
				'Edit Image' => __( 'Edit Image', 'acf' ),
				'Update Image' => __( 'Update Image', 'acf' ),
				'All images' => __( 'All images', 'acf' ),
				'Add Image to Gallery' => __( 'Add Image to Gallery', 'acf' ),
			)
		);
	}

	public function input_admin_localize_scripts() {
		acf_localize_text(
			array(
				'Select Image' => __( 'Select Image', 'acf' ),
				'Edit Image' => __( 'Edit Image', 'acf' ),
				'Update Image' => __( 'Update Image', 'acf' ),
				'All images' => __( 'All images', 'acf' ),
				'Add Image to Gallery' => __( 'Add Image to Gallery', 'acf' ),
			)
		);
	}

	public function render_field( $field ) {
		$uploader = acf_get_setting( 'uploader' );
		if ( $uploader === 'wp' ) {
			acf_enqueue_uploader();
		}

		$value = array();
		if ( ! empty( $field['value'] ) ) {
			if ( is_array( $field['value'] ) ) {
				$value = $field['value'];
			} elseif ( is_string( $field['value'] ) ) {
				$value = maybe_unserialize( $field['value'] );
				if ( ! is_array( $value ) ) {
					$value = array();
				}
			}
		}

		$attachments = array();
		foreach ( $value as $attachment_id ) {
			if ( ! is_numeric( $attachment_id ) ) {
				continue;
			}
			$attachment = wp_get_attachment_image_src( $attachment_id, $field['preview_size'] );
			if ( $attachment ) {
				$attachments[] = array(
					'id' => (int) $attachment_id,
					'url' => $attachment[0],
					'filename' => basename( get_attached_file( $attachment_id ) ),
				);
			}
		}

		$hidden_value = array_map( 'intval', $value );
		?>
		<div class="acf-gallery" data-preview_size="<?php echo esc_attr( $field['preview_size'] ); ?>" data-library="<?php echo esc_attr( $field['library'] ); ?>" data-min="<?php echo esc_attr( $field['min'] ); ?>" data-max="<?php echo esc_attr( $field['max'] ); ?>">
			<input type="hidden" name="<?php echo esc_attr( $field['name'] ); ?>" value="<?php echo esc_attr( implode( ',', $hidden_value ) ); ?>" />
			<div class="toolbar">
				<a href="#" class="acf-button button add-image"><?php esc_html_e( 'Add Image to Gallery', 'acf' ); ?></a>
				<span class="count"><?php echo count( $attachments ); ?> <?php esc_html_e( 'images', 'acf' ); ?></span>
			</div>
			<div class="thumbnails">
				<div class="inner">
					<?php foreach ( $attachments as $attachment ) : ?>
						<div class="thumbnail" data-id="<?php echo esc_attr( $attachment['id'] ); ?>">
							<img src="<?php echo esc_url( $attachment['url'] ); ?>" alt="<?php echo esc_attr( $attachment['filename'] ); ?>" />
							<div class="actions">
								<a href="#" class="acf-button-edit"><?php esc_html_e( 'Edit', 'acf' ); ?></a>
								<a href="#" class="acf-button-delete"><?php esc_html_e( 'Remove', 'acf' ); ?></a>
							</div>
						</div>
					<?php endforeach; ?>
				</div>
			</div>
		</div>
		<?php
	}

	public function render_field_settings( $field ) {
		acf_render_field_setting(
			$field,
			array(
				'label' => __( 'Return Format', 'acf' ),
				'instructions' => '',
				'name' => 'return_format',
				'type' => 'radio',
				'layout' => 'horizontal',
				'choices' => array(
					'array' => __( 'Image Array', 'acf' ),
					'url' => __( 'Image URL', 'acf' ),
					'id' => __( 'Image ID', 'acf' ),
				),
			)
		);

		acf_render_field_setting(
			$field,
			array(
				'label' => __( 'Library', 'acf' ),
				'instructions' => __( 'Limit the media library choice', 'acf' ),
				'name' => 'library',
				'type' => 'radio',
				'layout' => 'horizontal',
				'choices' => array(
					'all' => __( 'All', 'acf' ),
					'uploadedTo' => __( 'Uploaded to this post', 'acf' ),
				),
			)
		);

		acf_render_field_setting( $field, array(
			'label' => __( 'Minimum', 'acf' ),
			'name' => 'min',
			'type' => 'number',
			'min' => 0,
		) );

		acf_render_field_setting( $field, array(
			'label' => __( 'Maximum', 'acf' ),
			'name' => 'max',
			'type' => 'number',
			'min' => 0,
		) );
	}

	public function load_value( $value, $post_id, $field ) {
		if ( empty( $value ) ) {
			return array();
		}

		if ( ! is_array( $value ) ) {
			$value = explode( ',', $value );
		}

		return array_values( array_filter( array_map( 'intval', $value ) ) );
	}

	public function update_value( $value, $post_id, $field ) {
		if ( empty( $value ) ) {
			return '';
		}

		if ( is_array( $value ) ) {
			$value = array_map( 'intval', $value );
			return implode( ',', array_filter( $value ) );
		}

		return (string) $value;
	}

	public function format_value( $value, $post_id, $field ) {
		if ( empty( $value ) ) {
			return array();
		}

		if ( ! is_array( $value ) ) {
			$value = explode( ',', (string) $value );
		}

		$ids = array_values( array_filter( array_map( 'intval', $value ) ) );
		if ( $field['return_format'] === 'id' ) {
			return $ids;
		}
		if ( $field['return_format'] === 'url' ) {
			$urls = array();
			foreach ( $ids as $id ) {
				$image = wp_get_attachment_url( $id );
				if ( $image ) {
					$urls[] = $image;
				}
			}
			return $urls;
		}

		$images = array();
		foreach ( $ids as $id ) {
			$images[] = wp_prepare_attachment_for_js( $id );
		}
		return array_filter( $images );
	}
}

acf_register_field_type( 'acf_field_gallery' );
endif;
