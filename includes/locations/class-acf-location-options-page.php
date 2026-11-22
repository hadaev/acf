<?php
/**
 * Options page field-group location rule.
 *
 * @package ACF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ACF_Location_Options_Page' ) ) :
	class ACF_Location_Options_Page extends ACF_Location {

		/**
		 * Initialize location rule properties.
		 */
		public function initialize() {
			$this->name        = 'options_page';
			$this->label       = __( 'Options Page', 'acf' );
			$this->category    = 'forms';
			$this->object_type = 'option';
		}

		/**
		 * Match a field-group rule against the current options page.
		 *
		 * @param array $rule        Location rule.
		 * @param array $screen      Screen data.
		 * @param array $field_group Field group.
		 * @return bool
		 */
		public function match( $rule, $screen, $field_group ) {
			if ( ! isset( $screen['options_page'] ) ) {
				return false;
			}

			return $this->compare_to_rule( $screen['options_page'], $rule );
		}

		/**
		 * Return the registered options pages for the rule selector.
		 *
		 * @param array $rule Location rule.
		 * @return array
		 */
		public function get_values( $rule ) {
			$choices = array();
			foreach ( acf_get_options_pages() as $page ) {
				$choices[ $page['menu_slug'] ] = $page['page_title'];
			}

			return $choices ? $choices : array( '' => __( 'No options pages registered', 'acf' ) );
		}
	}

	acf_register_location_type( 'ACF_Location_Options_Page' );
endif;
