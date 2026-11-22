<?php
/**
 * Admin screens for registered options pages.
 *
 * @package ACF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ACF_Admin_Options_Page' ) ) :
	class ACF_Admin_Options_Page {

		/**
		 * Register WordPress admin menu entries for options pages.
		 */
		public function __construct() {
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 99 );
		}

		/**
		 * Create registered admin menu entries.
		 */
		public function admin_menu() {
			if ( ! acf_get_setting( 'show_admin' ) ) {
				return;
			}

			$top_level_pages = array();

			foreach ( acf_get_options_pages() as $page ) {
				if ( empty( $page['parent_slug'] ) ) {
					$top_level_pages[] = $page['menu_slug'];
					$callback = $this->has_child_page( $page['menu_slug'] ) && $page['redirect']
						? array( $this, 'redirect_to_child' )
						: array( $this, 'render' );
					$hook = add_menu_page(
						$page['page_title'],
						$page['menu_title'],
						$page['capability'],
						$page['menu_slug'],
						$callback,
						$page['icon_url'],
						$page['position']
					);
				} else {
					$hook = add_submenu_page(
						$page['parent_slug'],
						$page['page_title'],
						$page['menu_title'],
						$page['capability'],
						$page['menu_slug'],
						array( $this, 'render' ),
						$page['position']
					);
				}

				if ( $hook ) {
					add_action( 'load-' . $hook, array( $this, 'load_page' ) );
				}
			}

			foreach ( $top_level_pages as $menu_slug ) {
				remove_submenu_page( $menu_slug, $menu_slug );
			}
		}

		/**
		 * Check whether a registered page has child pages.
		 *
		 * @param string $parent_slug Parent menu slug.
		 * @return bool
		 */
		private function has_child_page( $parent_slug ) {
			foreach ( acf_get_options_pages() as $page ) {
				if ( $parent_slug === $page['parent_slug'] ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Redirect a parent page to its first child page.
		 */
		public function redirect_to_child() {
			global $plugin_page;

			$parent = acf_get_options_page( $plugin_page );
			if ( ! $parent || ! current_user_can( $parent['capability'] ) ) {
				wp_die( esc_html__( 'You are not allowed to access this options page.', 'acf' ) );
			}

			foreach ( acf_get_options_pages() as $page ) {
				if ( $plugin_page === $page['parent_slug'] ) {
					if ( ! current_user_can( $page['capability'] ) ) {
						wp_die( esc_html__( 'You are not allowed to access this options page.', 'acf' ) );
					}

					wp_safe_redirect( admin_url( 'admin.php?page=' . rawurlencode( $page['menu_slug'] ) ) );
					exit;
				}
			}
		}

		/**
		 * Verify and process an options page submission.
		 */
		public function load_page() {
			global $plugin_page;

			$page = acf_get_options_page( $plugin_page );
			if ( ! $page || ! current_user_can( $page['capability'] ) ) {
				wp_die( esc_html__( 'You are not allowed to access this options page.', 'acf' ) );
			}

			if ( isset( $_SERVER['REQUEST_METHOD'] ) && 'POST' === strtoupper( $_SERVER['REQUEST_METHOD'] ) ) {
				if ( ! acf_verify_nonce( 'options' ) ) {
					wp_die( esc_html__( 'The options page could not be saved because the security check failed.', 'acf' ) );
				}

				if ( acf_validate_save_post( true ) ) {
					acf_update_setting( 'autoload', (bool) $page['autoload'] );
					acf_save_post( $page['post_id'] );

					do_action( 'acf/options_page/save', $page['post_id'], $page['menu_slug'] );

					wp_safe_redirect(
						add_query_arg(
							array(
								'page'    => $page['menu_slug'],
								'message' => '1',
							),
							admin_url( 'admin.php' )
						)
					);
					exit;
				}
			}

			acf_enqueue_scripts();
		}

		/**
		 * Render the options page form and its matching field groups.
		 */
		public function render() {
			global $plugin_page;

			$page = acf_get_options_page( $plugin_page );
			if ( ! $page ) {
				wp_die( esc_html__( 'Options page not found.', 'acf' ) );
			}

			$field_groups = acf_get_field_groups(
				array(
					'options_page' => $page['menu_slug'],
				)
			);
			?>
			<div class="wrap acf-options-page">
				<h1><?php echo esc_html( $page['page_title'] ); ?></h1>
				<?php if ( isset( $_GET['message'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['message'] ) ) ) : ?>
					<div class="notice notice-success is-dismissible"><p><?php echo esc_html( $page['updated_message'] ); ?></p></div>
				<?php endif; ?>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin.php?page=' . rawurlencode( $page['menu_slug'] ) ) ); ?>">
					<?php acf_form_data( array( 'screen' => 'options', 'post_id' => $page['post_id'] ) ); ?>
					<?php
					if ( $field_groups ) {
						foreach ( $field_groups as $field_group ) {
							acf_render_fields(
								acf_get_fields( $field_group ),
								$page['post_id'],
								'div',
								$field_group['instruction_placement']
							);
						}
					} else {
						echo '<div class="notice notice-info inline"><p>' . esc_html__( 'No field groups are assigned to this options page yet.', 'acf' ) . '</p>';
						if ( current_user_can( acf_get_setting( 'capability' ) ) ) {
							$url = add_query_arg(
								'post_type',
								'acf-field-group',
								admin_url( 'post-new.php' )
							);
							echo '<p><a class="button button-primary" href="' . esc_url( $url ) . '">' . esc_html__( 'Create Field Group', 'acf' ) . '</a></p>';
						}
						echo '</div>';
					}
					?>
					<?php submit_button( $page['update_button'] ); ?>
				</form>
			</div>
			<?php
		}
	}

	new ACF_Admin_Options_Page();
endif;
