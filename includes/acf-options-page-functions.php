<?php
/**
 * Options page registration and lookup functions.
 *
 * @package ACF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'acf_options_page' ) ) :
	class acf_options_page {

		/**
		 * Registered options pages indexed by menu slug.
		 *
		 * @var array
		 */
		public $pages = array();

		/**
		 * Normalize an options page configuration.
		 *
		 * @param array|string $page Page settings or page title.
		 * @return array
		 */
		public function validate_page( $page ) {
			if ( empty( $page ) ) {
				$page = array(
					'page_title' => __( 'Options', 'acf' ),
					'menu_title' => __( 'Options', 'acf' ),
					'menu_slug'  => 'acf-options',
				);
			} elseif ( is_string( $page ) ) {
				$page = array(
					'page_title' => $page,
					'menu_title' => $page,
				);
			} elseif ( ! is_array( $page ) ) {
				_doing_it_wrong( __FUNCTION__, __( 'Options page settings must be an array or string.', 'acf' ), '1.0.0' );
				$page = array();
			}

			$page = wp_parse_args(
				$page,
				array(
					'page_title'      => '',
					'menu_title'      => '',
					'menu_slug'       => '',
					'capability'      => 'edit_posts',
					'parent_slug'     => '',
					'position'        => null,
					'redirect'        => true,
					'icon_url'        => 'dashicons-admin-generic',
					'post_id'         => 'options',
					'autoload'        => false,
					'update_button'   => __( 'Save Changes', 'acf' ),
					'updated_message' => __( 'Options Updated', 'acf' ),
				)
			);

			if ( empty( $page['menu_title'] ) ) {
				$page['menu_title'] = $page['page_title'];
			}

			if ( empty( $page['menu_slug'] ) ) {
				$page['menu_slug'] = 'acf-options-' . sanitize_title( $page['menu_title'] );
			}

			$page['menu_slug'] = sanitize_key( $page['menu_slug'] );
			$page['position']  = is_numeric( $page['position'] ) ? (int) $page['position'] : null;

			return apply_filters( 'acf/validate_options_page', $page );
		}

		/**
		 * Register an options page.
		 *
		 * @param array|string $page Page settings.
		 * @return array|false
		 */
		public function add_page( $page ) {
			$page = $this->validate_page( $page );

			if ( empty( $page['page_title'] ) || empty( $page['menu_slug'] ) || isset( $this->pages[ $page['menu_slug'] ] ) ) {
				return false;
			}

			$this->pages[ $page['menu_slug'] ] = $page;
			return $page;
		}

		/**
		 * Register a child page, creating the default parent when needed.
		 *
		 * @param array|string $page Page settings.
		 * @return array|false
		 */
		public function add_sub_page( $page ) {
			$page = $this->validate_page( $page );
			if ( empty( $page['parent_slug'] ) ) {
				$page['parent_slug'] = 'acf-options';
			}

			if ( 'acf-options' === $page['parent_slug'] && ! $this->get_page( 'acf-options' ) ) {
				$this->add_page( '' );
			}

			return $this->add_page( $page );
		}

		/**
		 * Retrieve a registered page.
		 *
		 * @param string $slug Menu slug.
		 * @return array|false
		 */
		public function get_page( $slug ) {
			return isset( $this->pages[ $slug ] ) ? $this->pages[ $slug ] : false;
		}

		/**
		 * Retrieve all registered pages.
		 *
		 * @return array
		 */
		public function get_pages() {
			return $this->pages;
		}
	}

	/**
	 * Get the options page registry.
	 *
	 * @return acf_options_page
	 */
	function acf_options_page() {
		static $instance;
		if ( ! $instance ) {
			$instance = new acf_options_page();
		}
		return $instance;
	}
endif;

if ( ! function_exists( 'acf_add_options_page' ) ) {
	/**
	 * Register an options page.
	 *
	 * @param array|string $page Page settings.
	 * @return array|false
	 */
	function acf_add_options_page( $page = '' ) {
		return acf_options_page()->add_page( $page );
	}
}

if ( ! function_exists( 'acf_add_options_sub_page' ) ) {
	/**
	 * Register an options sub page.
	 *
	 * @param array|string $page Page settings.
	 * @return array|false
	 */
	function acf_add_options_sub_page( $page = '' ) {
		return acf_options_page()->add_sub_page( $page );
	}
}

if ( ! function_exists( 'acf_get_options_page' ) ) {
	/**
	 * Retrieve an options page by menu slug.
	 *
	 * @param string $slug Menu slug.
	 * @return array|false
	 */
	function acf_get_options_page( $slug ) {
		$page = acf_options_page()->get_page( $slug );
		return $page ? apply_filters( 'acf/get_options_page', $page, $slug ) : false;
	}
}

if ( ! function_exists( 'acf_get_options_pages' ) ) {
	/**
	 * Retrieve registered options pages.
	 *
	 * @return array
	 */
	function acf_get_options_pages() {
		$pages = acf_options_page()->get_pages();
		foreach ( $pages as $slug => $page ) {
			$pages[ $slug ] = acf_get_options_page( $slug );
		}
		return apply_filters( 'acf/get_options_pages', $pages );
	}
}
