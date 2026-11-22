<?php
/**
 * Admin UI for registering ACF options pages.
 *
 * @package ACF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'ACF_Admin_Options_Page_Manager' ) ) :
	class ACF_Admin_Options_Page_Manager {

		/**
		 * Option name used to store pages created in the admin UI.
		 *
		 * @var string
		 */
		private $option_name = 'acf_custom_options_pages';

		/**
		 * Register hooks.
		 */
		public function __construct() {
			add_action( 'acf/init', array( $this, 'register_options_pages' ), 20 );
			add_action( 'admin_menu', array( $this, 'admin_menu' ), 10 );
			add_action( 'admin_post_acf_custom_save_options_page', array( $this, 'save_options_page' ) );
			add_action( 'admin_post_acf_custom_delete_options_page', array( $this, 'delete_options_page' ) );
		}

		/**
		 * Register saved options pages before ACF creates their admin menu items.
		 */
		public function register_options_pages() {
			$pages = get_option( $this->option_name, array() );

			if ( ! is_array( $pages ) ) {
				return;
			}

			foreach ( $pages as $page ) {
				if ( ! is_array( $page ) || ! empty( $page['parent_slug'] ) ) {
					continue;
				}

				acf_add_options_page( $page );
			}

			foreach ( $pages as $page ) {
				if ( ! is_array( $page ) || empty( $page['parent_slug'] ) ) {
					continue;
				}

				acf_add_options_sub_page( $page );
			}
		}

		/**
		 * Add the options pages manager under the ACF menu.
		 */
		public function admin_menu() {
			if ( ! acf_get_setting( 'show_admin' ) ) {
				return;
			}

			$parent_slug = 'edit.php?post_type=acf-field-group';
			$capability  = acf_get_setting( 'capability' );

			$page = add_submenu_page(
				$parent_slug,
				__( 'Options Pages', 'acf' ),
				__( 'Options Pages', 'acf' ),
				$capability,
				'acf_options_preview',
				array( $this, 'render' )
			);
			add_action( 'load-' . $page, array( $this, 'load' ) );
		}

		/**
		 * Load ACF styles and navigation for the options pages manager.
		 */
		public function load() {
			add_action( 'admin_body_class', array( $this, 'admin_body_class' ) );
			acf_enqueue_scripts();
		}

		/**
		 * Add the ACF admin classes used by its internal post type screens.
		 *
		 * @param string $classes Current admin body classes.
		 * @return string
		 */
		public function admin_body_class( $classes ) {
			return $classes . ' acf-admin-page acf-internal-post-type acf-admin-options-pages';
		}

		/**
		 * Save an options page submitted from the manager.
		 */
		public function save_options_page() {
			if ( ! current_user_can( acf_get_setting( 'capability' ) ) ) {
				wp_die( esc_html__( 'You are not allowed to manage options pages.', 'acf' ) );
			}

			check_admin_referer( 'acf_custom_save_options_page' );
			$editing_slug = isset( $_POST['editing_menu_slug'] ) && is_string( $_POST['editing_menu_slug'] )
				? sanitize_title( wp_unslash( $_POST['editing_menu_slug'] ) )
				: '';

			$page_title = isset( $_POST['page_title'] ) && is_string( $_POST['page_title'] )
				? sanitize_text_field( wp_unslash( $_POST['page_title'] ) )
				: '';
			$menu_title = isset( $_POST['menu_title'] ) && is_string( $_POST['menu_title'] )
				? sanitize_text_field( wp_unslash( $_POST['menu_title'] ) )
				: '';
			$menu_slug = isset( $_POST['menu_slug'] ) && is_string( $_POST['menu_slug'] )
				? wp_unslash( $_POST['menu_slug'] )
				: '';
			$capability = isset( $_POST['page_capability'] ) && is_string( $_POST['page_capability'] )
				? sanitize_key( wp_unslash( $_POST['page_capability'] ) )
				: 'edit_posts';
			$parent_slug = isset( $_POST['parent_slug'] ) && is_string( $_POST['parent_slug'] )
				? sanitize_text_field( wp_unslash( $_POST['parent_slug'] ) )
				: '';

			$notice        = 'invalid';
			$pages         = get_option( $this->option_name, array() );
			$editing_index = false;

			if ( ! is_array( $pages ) ) {
				$pages = array();
			}
			$original_pages = array_values( $pages );

			if ( $editing_slug ) {
				foreach ( $pages as $index => $saved_page ) {
					if ( isset( $saved_page['menu_slug'] ) && $editing_slug === $saved_page['menu_slug'] ) {
						$editing_index = $index;
						$menu_slug     = $editing_slug;
						if ( ! isset( $_POST['parent_slug'] ) && $this->has_registered_children( $editing_slug ) ) {
							$parent_slug = isset( $saved_page['parent_slug'] ) ? $saved_page['parent_slug'] : '';
						}
						break;
					}
				}
			}

			if (
				$page_title &&
				$menu_title &&
				preg_match( '/^[a-z0-9_-]+$/', $menu_slug ) &&
				$capability &&
				( ! $editing_slug || false !== $editing_index )
			) {
				$valid_parent = '' === $parent_slug;
				foreach ( $this->get_parent_pages( $editing_slug ) as $slug => $title ) {
					if ( $slug === $parent_slug ) {
						$valid_parent = true;
						break;
					}
				}

				if (
					$editing_slug &&
					$this->has_registered_children( $editing_slug ) &&
					$parent_slug !== ( isset( $pages[ $editing_index ]['parent_slug'] ) ? $pages[ $editing_index ]['parent_slug'] : '' )
				) {
					$valid_parent = false;
				}

				if ( $valid_parent ) {
					$duplicate = false;
					if ( ! $editing_slug ) {
						foreach ( acf_get_options_pages() as $registered_page ) {
							if ( isset( $registered_page['menu_slug'] ) && $menu_slug === $registered_page['menu_slug'] ) {
								$duplicate = true;
								break;
							}
						}
					}

					if ( ! $duplicate ) {
						$page = array(
							'page_title'  => $page_title,
							'menu_title'  => $menu_title,
							'menu_slug'   => $menu_slug,
							'capability'  => $capability,
							'parent_slug' => $parent_slug,
						);

						if ( $editing_slug ) {
							$pages[ $editing_index ] = $page;
							$notice                  = 'updated';
						} else {
							$pages[] = $page;
							$notice  = 'created';
						}

						$updated_pages = array_values( $pages );
						if ( ! update_option( $this->option_name, $updated_pages, false ) && $updated_pages !== $original_pages ) {
							$notice = 'save_failed';
						}
					} else {
						$notice = 'duplicate';
					}
				}
			}

			$redirect_url = add_query_arg(
				array(
					'post_type'                 => 'acf-field-group',
					'page'                      => 'acf_options_preview',
					'notice'                    => $notice,
					'acf_options_page_action'   => $editing_slug ? 'edit' : '',
					'menu_slug'                 => $editing_slug,
				),
				admin_url( 'edit.php' )
			);

			wp_safe_redirect( $redirect_url );
			exit;
		}

		/**
		 * Delete an options page created in the manager.
		 */
		public function delete_options_page() {
			if ( ! current_user_can( acf_get_setting( 'capability' ) ) ) {
				wp_die( esc_html__( 'You are not allowed to manage options pages.', 'acf' ) );
			}

			$menu_slug = isset( $_GET['menu_slug'] ) && is_string( $_GET['menu_slug'] )
				? sanitize_title( wp_unslash( $_GET['menu_slug'] ) )
				: '';
			check_admin_referer( 'acf_custom_delete_options_page_' . $menu_slug );

			$pages = get_option( $this->option_name, array() );
			if ( ! is_array( $pages ) || ! $menu_slug ) {
				$notice = 'delete_failed';
			} else {
				$delete_index = false;
				foreach ( $pages as $index => $page ) {
					if ( isset( $page['menu_slug'] ) && $menu_slug === $page['menu_slug'] ) {
						$delete_index = $index;
						break;
					}
				}

				if ( false === $delete_index ) {
					$notice = 'delete_failed';
				} elseif ( $this->has_registered_children( $menu_slug ) ) {
					$notice = 'has_children';
				} else {
					unset( $pages[ $delete_index ] );
					$notice = update_option( $this->option_name, array_values( $pages ), false )
						? 'deleted'
						: 'delete_failed';
				}
			}

			$redirect_url = add_query_arg(
				array(
					'post_type' => 'acf-field-group',
					'page'      => 'acf_options_preview',
					'notice'    => $notice,
				),
				admin_url( 'edit.php' )
			);

			wp_safe_redirect( $redirect_url );
			exit;
		}

		/**
		 * Get registered top-level ACF option pages for the parent selector.
		 *
		 * @param string $exclude_slug Menu slug to omit from the choices.
		 * @return array
		 */
		private function get_parent_pages( $exclude_slug = '' ) {
			$parents = array();

			foreach ( acf_get_options_pages() as $page ) {
				if ( empty( $page['menu_slug'] ) || $exclude_slug === $page['menu_slug'] || ! empty( $page['parent_slug'] ) ) {
					continue;
				}

				$parents[ $page['menu_slug'] ] = ! empty( $page['menu_title'] )
					? $page['menu_title']
					: $page['page_title'];
			}

			return $parents;
		}

		/**
		 * Get an options page created in the manager by its menu slug.
		 *
		 * @param string $menu_slug Options page menu slug.
		 * @return array|false
		 */
		private function get_managed_page( $menu_slug ) {
			$pages = get_option( $this->option_name, array() );
			if ( ! is_array( $pages ) ) {
				return false;
			}

			foreach ( $pages as $page ) {
				if ( isset( $page['menu_slug'] ) && $menu_slug === $page['menu_slug'] ) {
					return $page;
				}
			}

			return false;
		}

		/**
		 * Check whether any registered options page uses this page as its parent.
		 *
		 * @param string $menu_slug Options page menu slug.
		 * @return bool
		 */
		private function has_registered_children( $menu_slug ) {
			foreach ( acf_get_options_pages() as $page ) {
				if ( isset( $page['parent_slug'] ) && $menu_slug === $page['parent_slug'] ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Keep each options page next to its registered parent page.
		 *
		 * @param array $pages Registered options pages.
		 * @return array
		 */
		private function sort_pages_by_parent( $pages ) {
			$top_level_pages = array();
			$child_pages     = array();
			$sorted_pages    = array();

			foreach ( $pages as $page ) {
				if ( empty( $page['parent_slug'] ) ) {
					$top_level_pages[] = $page;
				} else {
					$child_pages[ $page['parent_slug'] ][] = $page;
				}
			}

			foreach ( $top_level_pages as $page ) {
				$sorted_pages[] = $page;
				$parent_slug    = $page['menu_slug'];

				if ( ! empty( $child_pages[ $parent_slug ] ) ) {
					foreach ( $child_pages[ $parent_slug ] as $child_page ) {
						$sorted_pages[] = $child_page;
					}

					unset( $child_pages[ $parent_slug ] );
				}
			}

			foreach ( $child_pages as $orphaned_children ) {
				foreach ( $orphaned_children as $child_page ) {
					$sorted_pages[] = $child_page;
				}
			}

			return $sorted_pages;
		}

		/**
		 * Render the options pages list and add form.
		 */
		public function render() {
			if ( ! current_user_can( acf_get_setting( 'capability' ) ) ) {
				wp_die( esc_html__( 'You are not allowed to manage options pages.', 'acf' ) );
			}

			$pages = $this->sort_pages_by_parent( acf_get_options_pages() );
			$editing_slug               = isset( $_GET['acf_options_page_action'], $_GET['menu_slug'] ) &&
				is_string( $_GET['acf_options_page_action'] ) &&
				'edit' === sanitize_key( wp_unslash( $_GET['acf_options_page_action'] ) ) &&
				is_string( $_GET['menu_slug'] )
				? sanitize_title( wp_unslash( $_GET['menu_slug'] ) )
				: '';
			$editing_page               = $editing_slug ? $this->get_managed_page( $editing_slug ) : false;
			$editing_page_has_children = $editing_page ? $this->has_registered_children( $editing_slug ) : false;
			if ( ! $editing_page ) {
				$editing_slug = '';
			}
			?>
			<div class="wrap acf-admin-options-pages">
				<h1 class="wp-heading-inline"><?php esc_html_e( 'Options Pages', 'acf' ); ?></h1>
				<?php $this->render_notice(); ?>

					<form id="acf-options-page-form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" <?php echo $editing_page ? '' : 'hidden'; ?>>
						<input type="hidden" name="action" value="acf_custom_save_options_page">
						<?php wp_nonce_field( 'acf_custom_save_options_page' ); ?>
						<?php if ( $editing_page ) : ?>
							<input type="hidden" name="editing_menu_slug" value="<?php echo esc_attr( $editing_slug ); ?>">
							<h2><?php esc_html_e( 'Edit Options Page', 'acf' ); ?></h2>
						<?php endif; ?>

						<div class="acf-fields -border">
							<?php
							acf_render_field_wrap(
								array(
									'type'     => 'text',
									'name'     => 'page_title',
									'label'    => __( 'Page Title', 'acf' ),
									'value'    => $editing_page ? $editing_page['page_title'] : '',
									'required' => true,
								)
							);
							acf_render_field_wrap(
								array(
									'type'     => 'text',
									'name'     => 'menu_title',
									'label'    => __( 'Menu Title', 'acf' ),
									'value'    => $editing_page ? $editing_page['menu_title'] : '',
									'required' => true,
								)
							);
							acf_render_field_wrap(
								array(
									'type'         => 'text',
									'name'         => 'menu_slug',
									'label'        => __( 'Menu Slug', 'acf' ),
									'instructions' => $editing_page
										? __( 'The menu slug cannot be changed after the options page is created.', 'acf' )
										: __( 'Lowercase letters, numbers, underscores, and dashes only.', 'acf' ),
									'value'        => $editing_page ? $editing_page['menu_slug'] : '',
									'readonly'     => (bool) $editing_page,
									'required'     => true,
								)
							);
							acf_render_field_wrap(
								array(
									'type'     => 'text',
									'name'     => 'page_capability',
									'label'    => __( 'Capability', 'acf' ),
									'value'    => $editing_page ? $editing_page['capability'] : 'edit_posts',
									'required' => true,
								)
							);
							$parent_choices = array( '' => __( 'Top-level page', 'acf' ) );
							foreach ( $this->get_parent_pages( $editing_slug ) as $slug => $title ) {
								$parent_choices[ $slug ] = $title;
							}
							acf_render_field_wrap(
								array(
									'type'         => 'select',
									'name'         => 'parent_slug',
									'label'        => __( 'Parent Page', 'acf' ),
									'choices'      => $parent_choices,
									'value'        => $editing_page ? $editing_page['parent_slug'] : '',
									'disabled'     => $editing_page_has_children,
									'instructions' => $editing_page_has_children
										? __( 'The parent cannot be changed while this page has sub-pages.', 'acf' )
										: '',
								)
							);
							?>
							<?php if ( $editing_page_has_children ) : ?>
								<input type="hidden" name="parent_slug" value="<?php echo esc_attr( $editing_page['parent_slug'] ); ?>">
							<?php endif; ?>
						</div>

						<p class="acf-form-submit" style="display: flex; align-items: center; gap: 8px;">
							<button type="submit" class="acf-btn"><i class="acf-icon acf-icon-<?php echo $editing_page ? 'regenerate' : 'plus'; ?>"></i><?php echo $editing_page ? esc_html__( 'Update Options Page', 'acf' ) : esc_html__( 'Create Option Page', 'acf' ); ?></button>
							<?php if ( $editing_page ) : ?>
								<a class="acf-btn" href="<?php echo esc_url( add_query_arg( array( 'post_type' => 'acf-field-group', 'page' => 'acf_options_preview' ), admin_url( 'edit.php' ) ) ); ?>"><?php esc_html_e( 'Cancel', 'acf' ); ?></a>
							<?php endif; ?>
						</p>
					</form>

				<script>
					(function() {
						var button = document.getElementById('acf-toggle-options-page-form');
						var form = document.getElementById('acf-options-page-form');

						if (!button || !form) {
							return;
						}

						button.addEventListener('click', function(event) {
							event.preventDefault();
							var isExpanded = 'true' === button.getAttribute('aria-expanded');
							button.setAttribute('aria-expanded', isExpanded ? 'false' : 'true');
							form.hidden = isExpanded;
						});
					})();
				</script>

				<h2><?php esc_html_e( 'Registered Options Pages', 'acf' ); ?></h2>
				<?php if ( empty( $pages ) ) : ?>
					<div class="acf-notice -info"><p><?php esc_html_e( 'No options pages have been registered yet.', 'acf' ); ?></p></div>
				<?php else : ?>
					<table class="wp-list-table widefat fixed striped">
						<thead>
							<tr>
								<th><?php esc_html_e( 'Page Title', 'acf' ); ?></th>
								<th><?php esc_html_e( 'Menu Title', 'acf' ); ?></th>
								<th><?php esc_html_e( 'Menu Slug', 'acf' ); ?></th>
								<th><?php esc_html_e( 'Parent Page', 'acf' ); ?></th>
								<th><?php esc_html_e( 'Actions', 'acf' ); ?></th>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $pages as $page ) : ?>
								<?php
								$parent = ! empty( $page['parent_slug'] )
									? acf_get_options_page( $page['parent_slug'] )
									: false;
								$managed_page = $this->get_managed_page( $page['menu_slug'] );
								?>
								<tr>
									<td><?php echo esc_html( $page['page_title'] ); ?></td>
									<td><?php echo esc_html( $page['menu_title'] ); ?></td>
									<td><code><?php echo esc_html( $page['menu_slug'] ); ?></code></td>
									<td><?php echo esc_html( $parent ? $parent['menu_title'] : ( $page['parent_slug'] ?: '—' ) ); ?></td>
									<td>
										<?php if ( $managed_page ) : ?>
											<?php
											$edit_url = add_query_arg(
												array(
													'post_type'               => 'acf-field-group',
													'page'                    => 'acf_options_preview',
													'acf_options_page_action' => 'edit',
													'menu_slug'               => $page['menu_slug'],
												),
												admin_url( 'edit.php' )
											);
											$delete_url = add_query_arg(
												array(
													'action'    => 'acf_custom_delete_options_page',
													'menu_slug' => $page['menu_slug'],
												),
												admin_url( 'admin-post.php' )
											);
											$delete_url = wp_nonce_url( $delete_url, 'acf_custom_delete_options_page_' . $page['menu_slug'] );
											?>
											<a href="<?php echo esc_url( $edit_url ); ?>"><?php esc_html_e( 'Edit', 'acf' ); ?></a>
											|
											<a href="<?php echo esc_url( $delete_url ); ?>" onclick="return confirm('<?php echo esc_js( __( 'Are you sure you want to delete this options page?', 'acf' ) ); ?>');"><?php esc_html_e( 'Delete', 'acf' ); ?></a>
										<?php else : ?>
											<span aria-hidden="true">—</span>
											<span class="screen-reader-text"><?php esc_html_e( 'Registered in code; cannot be managed here.', 'acf' ); ?></span>
										<?php endif; ?>
									</td>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				<?php endif; ?>
			</div>
			<?php
		}

		/**
		 * Render feedback after a create attempt.
		 */
		private function render_notice() {
			$notice = isset( $_GET['notice'] ) && is_string( $_GET['notice'] )
				? sanitize_key( wp_unslash( $_GET['notice'] ) )
				: '';

			$messages = array(
				'created'       => __( 'Options page created.', 'acf' ),
				'updated'       => __( 'Options page updated.', 'acf' ),
				'deleted'       => __( 'Options page deleted.', 'acf' ),
				'duplicate'     => __( 'An options page with that menu slug already exists.', 'acf' ),
				'invalid'       => __( 'Please check the page details and selected parent page.', 'acf' ),
				'save_failed'   => __( 'The options page could not be saved. Please try again.', 'acf' ),
				'delete_failed' => __( 'The options page could not be deleted. Please try again.', 'acf' ),
				'has_children'  => __( 'This options page cannot be deleted while it has sub-pages. Delete its sub-pages first.', 'acf' ),
			);

			if ( isset( $messages[ $notice ] ) ) {
				$class = in_array( $notice, array( 'created', 'updated', 'deleted' ), true ) ? 'notice-success' : 'notice-error';
				printf(
					'<div class="notice %1$s is-dismissible"><p>%2$s</p></div>',
					esc_attr( $class ),
					esc_html( $messages[ $notice ] )
				);
			}
		}
	}

	new ACF_Admin_Options_Page_Manager();
endif;
