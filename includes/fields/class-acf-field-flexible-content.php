<?php

if ( ! class_exists( 'acf_field_flexible_content' ) ) :

class acf_field_flexible_content extends acf_field {

	public function initialize() {
		$this->name = 'flexible_content';
		$this->label = __( 'Flexible Content', 'acf' );
		$this->category = 'layout';
		$this->defaults = array(
			'layouts' => array(),
			'min' => 0,
			'max' => 0,
			'display' => 'block',
			'button_label' => 'Add Layout',
		);
		$this->add_field_filter( 'acf/prepare_field_for_export', array( $this, 'prepare_field_for_export' ) );
		$this->add_field_filter( 'acf/prepare_field_for_import', array( $this, 'prepare_field_for_import' ) );
		$this->add_field_filter( 'acf/get_sub_field', array( $this, 'get_sub_field' ), 10, 3 );
	}

	public function input_admin_enqueue_scripts() {
		wp_enqueue_script(
			'acf-input-flexible-content',
			acf_get_url( 'assets/custom-fields/flexible-content-input.js' ),
			array( 'acf-input', 'jquery-ui-sortable' ),
			false,
			true
		);
		wp_enqueue_style(
			'acf-input-flexible-content',
			acf_get_url( 'assets/custom-fields/flexible-content-input.css' )
		);
	}

	public function field_group_admin_enqueue_scripts() {
		wp_enqueue_script(
			'acf-field-group-flexible-content',
			acf_get_url( 'assets/custom-fields/flexible-content-field-group.js' ),
			array( 'acf-field-group' ),
			false,
			true
		);
		wp_enqueue_style(
			'acf-field-group-flexible-content',
			acf_get_url( 'assets/custom-fields/flexible-content-field-group.css' )
		);
	}

	public function load_field( $field ) {
		$field['min'] = (int) ( $field['min'] ?? 0 );
		$field['max'] = (int) ( $field['max'] ?? 0 );
		$field['display'] = in_array( $field['display'] ?? '', array( 'block', 'table', 'row' ), true )
			? $field['display']
			: 'block';
		$field['layouts'] = is_array( $field['layouts'] ?? null ) ? $field['layouts'] : array();

		$stored_sub_fields = acf_get_fields( $field );
		foreach ( $field['layouts'] as &$layout ) {
			$layout['sub_fields'] = array();
			foreach ( $stored_sub_fields as $stored_sub_field ) {
				if ( ( $stored_sub_field['parent_layout'] ?? '' ) === $layout['key'] ) {
					$layout['sub_fields'][] = $stored_sub_field;
				}
			}
			$layout['min'] = (int) ( $layout['min'] ?? 0 );
			$layout['max'] = (int) ( $layout['max'] ?? 0 );
			$layout['display'] = $layout['display'] ?? 'block';
		}
		unset( $layout );

		return $field;
	}

	public function get_layout( $field, $layout_name ) {
		foreach ( $field['layouts'] as $layout ) {
			if ( ( $layout['name'] ?? '' ) === $layout_name ) {
				return $layout;
			}
		}
		return false;
	}

	public function get_sub_field( $sub_field, $selector, $field ) {
		if ( $sub_field ) {
			return $sub_field;
		}
		$loop = acf_get_loop( 'active' );
		if ( $loop && isset( $field['key'], $loop['field']['key'] ) && $loop['field']['key'] === $field['key'] ) {
			$layout_name = $loop['value'][ $loop['i'] ]['acf_fc_layout'] ?? '';
			$layout = $this->get_layout( $field, $layout_name );
			return $layout ? acf_search_fields( $selector, $layout['sub_fields'] ?? array() ) : false;
		}

		foreach ( $field['layouts'] ?? array() as $layout ) {
			$match = acf_search_fields( $selector, $layout['sub_fields'] ?? array() );
			if ( $match ) {
				return $match;
			}
		}
		return $sub_field;
	}

	public function load_value( $value, $post_id, $field ) {
		$count = (int) acf_get_metadata( $post_id, $field['name'] );
		if ( ! $count ) {
			return false;
		}

		$rows = array();
		for ( $i = 0; $i < $count; $i++ ) {
			$layout_name = acf_get_metadata( $post_id, $field['name'] . '_' . $i . '_acf_fc_layout' );
			$layout = $this->get_layout( $field, $layout_name );
			if ( ! $layout ) {
				continue;
			}

			$row = array( 'acf_fc_layout' => $layout_name );
			foreach ( $layout['sub_fields'] as $sub_field ) {
				if ( acf_is_empty( $sub_field['name'] ?? '' ) ) {
					continue;
				}
				$sub_field['name'] = $field['name'] . '_' . $i . '_' . $sub_field['name'];
				$row[ $sub_field['key'] ] = acf_get_value( $post_id, $sub_field );
			}
			$rows[] = $row;
		}
		return $rows;
	}

	public function format_value( $value, $post_id, $field ) {
		if ( ! is_array( $value ) ) {
			return false;
		}

		foreach ( $value as $i => $row ) {
			$layout_name = $row['acf_fc_layout'] ?? '';
			$layout = $this->get_layout( $field, $layout_name );
			if ( ! $layout ) {
				continue;
			}
			foreach ( $layout['sub_fields'] as $sub_field ) {
				if ( acf_is_empty( $sub_field['name'] ?? '' ) ) {
					continue;
				}
				$key = $sub_field['key'];
				$sub_value = $row[ $key ] ?? null;
				$sub_field['name'] = $field['name'] . '_' . $i . '_' . $sub_field['name'];
				$value[ $i ][ $sub_field['_name'] ] = acf_format_value( $sub_value, $post_id, $sub_field );
			}
		}
		return $value;
	}

	public function update_row( $row, $i, $field, $post_id ) {
		$layout_name = $row['acf_fc_layout'] ?? '';
		$layout = $this->get_layout( $field, $layout_name );
		if ( ! $layout ) {
			return false;
		}

		acf_update_metadata( $post_id, $field['name'] . '_' . $i . '_acf_fc_layout', $layout_name );

		foreach ( $layout['sub_fields'] as $sub_field ) {
			$value = null;
			if ( isset( $row[ $sub_field['key'] ] ) ) {
				$value = $row[ $sub_field['key'] ];
			} elseif ( isset( $row[ $sub_field['name'] ] ) ) {
				$value = $row[ $sub_field['name'] ];
			} else {
				continue;
			}
			$sub_field['name'] = $field['name'] . '_' . $i . '_' . $sub_field['name'];
			acf_update_value( $value, $post_id, $sub_field );
		}
		return true;
	}

	public function delete_row( $i, $field, $post_id ) {
		foreach ( $field['layouts'] as $layout ) {
			foreach ( $layout['sub_fields'] as $sub_field ) {
				$sub_field['name'] = $field['name'] . '_' . $i . '_' . $sub_field['name'];
				acf_delete_value( $post_id, $sub_field );
			}
		}
		acf_delete_metadata( $post_id, $field['name'] . '_' . $i . '_acf_fc_layout' );
	}

	public function update_value( $value, $post_id, $field ) {
		$new_value = 0;
		$old_value = (int) acf_get_metadata( $post_id, $field['name'] );

		if ( is_array( $value ) ) {
			foreach ( $value as $row ) {
				if ( ! is_array( $row ) ) {
					continue;
				}
				if ( $this->update_row( $row, $new_value, $field, $post_id ) ) {
					$new_value++;
				}
			}
		}

		if ( $old_value > $new_value ) {
			for ( $i = $new_value; $i < $old_value; $i++ ) {
				$this->delete_row( $i, $field, $post_id );
			}
		}

		return $new_value ? $new_value : '';
	}

	public function delete_value( $post_id, $key, $field ) {
		$old_value = (int) acf_get_metadata( $post_id, $field['name'] );
		if ( ! $old_value ) {
			return;
		}
		for ( $i = 0; $i < $old_value; $i++ ) {
			$this->delete_row( $i, $field, $post_id );
		}
	}

	public function validate_value( $valid, $value, $field, $input ) {
		$count = is_array( $value ) ? count( $value ) : 0;
		if ( isset( $value['acfcloneindex'] ) ) {
			$count--;
		}

		if ( $field['required'] && ! $count ) {
			return false;
		}
		if ( $field['min'] && $count < (int) $field['min'] ) {
			return str_replace( '{min}', $field['min'], __( 'Minimum layouts reached ({min} layouts)', 'acf' ) );
		}
		if ( $field['max'] && $count > (int) $field['max'] ) {
			return str_replace( '{max}', $field['max'], __( 'Maximum layouts reached ({max} layouts)', 'acf' ) );
		}
		return $valid;
	}

	public function render_field( $field ) {
		$field['layouts'] = is_array( $field['layouts'] ?? null ) ? $field['layouts'] : array();
		$value = is_array( $field['value'] ?? null ) ? $field['value'] : array();
		$field['button_label'] = $field['button_label'] ?: __( 'Add Layout', 'acf' );

		$div = array(
			'class' => 'acf-flexible-content',
			'data-min' => (int) $field['min'],
			'data-max' => (int) $field['max'],
		);

		echo '<div ' . acf_esc_attr( $div ) . '>';
		acf_hidden_input( array( 'name' => $field['name'], 'value' => '' ) );

		echo '<div class="acf-fc-layouts">';
		foreach ( $value as $i => $row ) {
			$layout_name = $row['acf_fc_layout'] ?? '';
			$layout = $this->get_layout( $field, $layout_name );
			if ( $layout ) {
				$this->render_layout( $field, $layout, $i, $row );
			}
		}
		echo '</div>';

		foreach ( $field['layouts'] as $layout ) {
			$this->render_layout( $field, $layout, 'acfcloneindex', array( 'acf_fc_layout' => $layout['name'] ), true );
		}

		echo '<div class="acf-actions"><div class="acf-fc-layout-select">';
		foreach ( $field['layouts'] as $layout ) {
			echo '<a href="#" class="acf-button button" data-layout="' . esc_attr( $layout['name'] ) . '" data-event="add-layout">';
			echo esc_html( $layout['label'] );
			echo '</a>';
		}
		echo '</div></div>';
		echo '</div>';
	}

	private function render_layout( $field, $layout, $i, $row, $clone = false ) {
		$classes = 'acf-fc-layout';
		if ( $clone ) {
			$classes .= ' acf-clone';
		}
		$display = $field['display'] ?? ( $layout['display'] ?? 'block' );
		$classes .= ' acf-fc-layout-' . sanitize_html_class( $display );

		echo '<div class="' . esc_attr( $classes ) . '" data-layout="' . esc_attr( $layout['name'] ) . '" data-id="' . esc_attr( $i ) . '">';
		echo '<div class="acf-fc-layout-handle">';
		echo '<span class="acf-fc-layout-drag" aria-hidden="true">&#8942;&#8942;</span>';
		echo '<span class="acf-fc-layout-label">' . esc_html( $layout['label'] ) . '</span>';
		echo '<a class="acf-icon -plus small" href="#" data-event="add-layout" title="' . esc_attr__( 'Add layout', 'acf' ) . '"></a>';
		echo '<a class="acf-icon -minus small" href="#" data-event="remove-layout" title="' . esc_attr__( 'Remove layout', 'acf' ) . '"></a>';
		echo '</div>';
		echo '<div class="acf-fc-layout-body">';
		acf_hidden_input( array(
			'name' => $field['name'] . '[' . $i . '][acf_fc_layout]',
			'value' => $layout['name'],
		) );

		if ( 'table' === $display ) {
			echo '<table class="acf-table acf-fc-layout-table"><thead><tr>';
			foreach ( $layout['sub_fields'] as $sub_field ) {
				echo '<th>' . esc_html( acf_get_field_label( $sub_field ) ) . '</th>';
			}
			echo '</tr></thead><tbody><tr>';
		} elseif ( 'row' === $display ) {
			echo '<div class="acf-fields -left">';
		}

		foreach ( $layout['sub_fields'] as $sub_field ) {
			if ( isset( $row[ $sub_field['key'] ] ) ) {
				$sub_field['value'] = $row[ $sub_field['key'] ];
			} elseif ( isset( $sub_field['default_value'] ) ) {
				$sub_field['value'] = $sub_field['default_value'];
			}
			$sub_field['prefix'] = $field['name'] . '[' . $i . ']';
			acf_render_field_wrap( $sub_field, 'table' === $display ? 'td' : 'div' );
		}

		if ( 'table' === $display ) {
			echo '</tr></tbody></table>';
		} elseif ( 'row' === $display ) {
			echo '</div>';
		}
		echo '</div>';
		echo '</div>';
	}

	private function render_layout_setting( $field, $layout, $index, $prefix ) {
		$layout = wp_parse_args( $layout, array(
			'key' => 'layout_' . sanitize_title( (string) $index ),
			'name' => '',
			'label' => '',
			'display' => 'block',
			'min' => 0,
			'max' => 0,
			'sub_fields' => array(),
		) );
		$layout_prefix = $prefix . '[layouts][' . $index . ']';
		foreach ( $layout['sub_fields'] as &$sub_field ) {
			$sub_field['parent_layout'] = $layout['key'];
			if ( empty( $sub_field['parent'] ) || ! is_numeric( $sub_field['parent'] ) ) {
				$sub_field['parent'] = $field['ID'] ?: 0;
			}
		}
		unset( $sub_field );
		?>
		<div class="acf-fc-field-layout" data-layout="<?php echo esc_attr( $layout['key'] ); ?>">
			<div class="acf-fc-field-layout-header">
				<button type="button" class="acf-fc-field-layout-title" aria-expanded="true">
					<?php echo esc_html( $layout['label'] ?: __( 'New Layout', 'acf' ) ); ?>
				</button>
				<a href="#" class="acf-fc-remove-layout acf-icon acf-icon-minus" title="<?php esc_attr_e( 'Remove Layout', 'acf' ); ?>"></a>
			</div>
			<div class="acf-fc-field-layout-settings">
				<?php acf_hidden_input( array( 'name' => $layout_prefix . '[key]', 'value' => $layout['key'] ) ); ?>
				<div class="acf-fc-field-setting">
					<label><?php esc_html_e( 'Label', 'acf' ); ?></label>
					<input type="text" name="<?php echo esc_attr( $layout_prefix ); ?>[label]" value="<?php echo esc_attr( $layout['label'] ); ?>" class="acf-fc-layout-label">
				</div>
				<div class="acf-fc-field-setting">
					<label><?php esc_html_e( 'Name', 'acf' ); ?></label>
					<input type="text" name="<?php echo esc_attr( $layout_prefix ); ?>[name]" value="<?php echo esc_attr( $layout['name'] ); ?>">
				</div>
			</div>
			<div class="acf-input-sub acf-fc-field-layout-subfields">
				<?php
				$args = array(
					'fields' => $layout['sub_fields'],
					'parent' => $field['ID'] ?: 0,
					'is_subfield' => true,
					'parent_layout' => $layout['key'],
				);
				acf_get_view( 'acf-field-group/fields', $args );
				?>
			</div>
		</div>
		<?php
	}

	public function render_field_settings( $field ) {
		$field['layouts'] = is_array( $field['layouts'] ?? null ) ? $field['layouts'] : array();
		$prefix = $field['prefix'] ?? 'acf_fields[' . $field['ID'] . ']';

		?><tr class="acf-field acf-field-setting-flexible-layouts" data-setting="flexible_content" data-name="layouts">
			<td class="acf-label">
				<label><strong><?php esc_html_e( 'Layouts', 'acf' ); ?></strong></label>
			</td>
			<td class="acf-input">
				<div class="acf-fc-field-layouts" data-prefix="<?php echo esc_attr( $prefix ); ?>">
					<div class="acf-fc-field-layout-list">
						<?php
						foreach ( $field['layouts'] as $index => $layout ) {
							$this->render_layout_setting( $field, $layout, $index, $prefix );
						}
						?>
					</div>
					<a href="#" class="acf-btn acf-btn-secondary acf-fc-add-layout">
						<i class="acf-icon acf-icon-plus"></i><?php esc_html_e( 'Add Layout', 'acf' ); ?>
					</a>
					<p class="description"><?php esc_html_e( 'Add layouts and configure sub fields for each layout.', 'acf' ); ?></p>
					<script type="text/template" class="acf-fc-layout-template">
						<?php
						$clone_layout = array(
							'key' => 'layout_new',
							'name' => 'new_layout',
							'label' => __( 'New Layout', 'acf' ),
							'display' => 'block',
							'min' => 0,
							'max' => 0,
							'sub_fields' => array(),
						);
						$this->render_layout_setting( $field, $clone_layout, '__INDEX__', $prefix );
						?>
					</script>
				</div>
			</td>
		</tr>
		<?php

		acf_render_field_setting( $field, array(
			'label' => __( 'Button Label', 'acf' ),
			'type' => 'text',
			'name' => 'button_label',
			'placeholder' => __( 'Add Layout', 'acf' ),
		) );
		acf_render_field_setting( $field, array(
			'label' => __( 'Display', 'acf' ),
			'type' => 'select',
			'name' => 'display',
			'choices' => array(
				'block' => __( 'Block', 'acf' ),
				'table' => __( 'Table', 'acf' ),
				'row' => __( 'Row', 'acf' ),
			),
		) );
		acf_render_field_setting( $field, array(
			'label' => __( 'Minimum Layouts', 'acf' ),
			'type' => 'number',
			'name' => 'min',
			'placeholder' => '0',
		) );
		acf_render_field_setting( $field, array(
			'label' => __( 'Maximum Layouts', 'acf' ),
			'type' => 'number',
			'name' => 'max',
			'placeholder' => '0',
		) );
	}

	public function update_field( $field ) {
		if ( isset( $field['layouts'] ) && is_array( $field['layouts'] ) ) {
			foreach ( $field['layouts'] as &$layout ) {
				unset( $layout['sub_fields'] );
			}
			unset( $layout );
		}
		unset( $field['sub_fields'] );
		return $field;
	}

	public function prepare_field_for_export( $field ) {
		if ( empty( $field['layouts'] ) ) {
			return $field;
		}
		foreach ( $field['layouts'] as &$layout ) {
			if ( ! empty( $layout['sub_fields'] ) ) {
				$layout['sub_fields'] = acf_prepare_fields_for_export( $layout['sub_fields'] );
			}
		}
		unset( $layout );
		return $field;
	}

	public function prepare_field_for_import( $field ) {
		if ( empty( $field['layouts'] ) ) {
			return $field;
		}

		$flattened = array();
		foreach ( $field['layouts'] as &$layout ) {
			if ( empty( $layout['sub_fields'] ) ) {
				continue;
			}
			$sub_fields = acf_extract_var( $layout, 'sub_fields' );
			foreach ( $sub_fields as &$sub_field ) {
				$sub_field['parent'] = $field['key'];
				$sub_field['parent_layout'] = $layout['key'];
			}
			unset( $sub_field );
			$flattened = array_merge( $flattened, $sub_fields );
		}
		unset( $layout );

		if ( $flattened ) {
			array_unshift( $flattened, $field );
			return $flattened;
		}
		return $field;
	}

	public function duplicate_field( $field ) {
		$layouts = $field['layouts'] ?? array();
		$field = acf_update_field( $field );
		foreach ( $layouts as $layout ) {
			if ( ! empty( $layout['sub_fields'] ) ) {
				acf_duplicate_fields( $layout['sub_fields'], $field['ID'] );
			}
		}
		return $field;
	}
}

acf_register_field_type( 'acf_field_flexible_content' );

endif;
