<?php
/**
 * @package ACF
 * @author  WP Engine
 *
 * © 2026 Advanced Custom Fields (ACF®). All rights reserved.
 * "ACF" is a trademark of WP Engine.
 * Licensed under the GNU General Public License v2 or later.
 * https://www.gnu.org/licenses/gpl-2.0.html
 */

//phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- included template file.
/**
 * The template for displaying admin navigation.
 *
 * @date    27/3/20
 * @since   5.9.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $submenu, $submenu_file, $plugin_page, $acf_page_title;

// Setup default vars and generate array of navigation items.
$parent_slug         = 'edit.php?post_type=acf-field-group';
$core_tabs           = array();

// Class names for the ACF toolbar tabs.
$core_tabs_classes      = array( 'acf-field-group', 'acf-post-type', 'acf-taxonomy', 'acf_options_preview', 'acf-tools' );

if ( isset( $submenu[ $parent_slug ] ) ) {
	foreach ( $submenu[ $parent_slug ] as $i => $sub_item ) {

		// Check user can access page.
		if ( ! current_user_can( $sub_item[1] ) ) {
			continue;
		}

		// Define tab.
		$menu_item = array(
			'text' => $sub_item[0],
			'url'  => $sub_item[2],
		);

		// Convert submenu slug "test" to "$parent_slug&page=test".
		if ( ! strpos( $sub_item[2], '.php' ) ) {
			$menu_item['url']   = add_query_arg( array( 'page' => $sub_item[2] ), $parent_slug );
			$menu_item['class'] = $sub_item[2];
		} else {
			// Build class from URL.
			$menu_item['class'] = str_replace( 'edit.php?post_type=', '', $sub_item[2] );
		}

		if ( 'acf_options_preview' === $menu_item['class'] ) {
			$menu_item['icon_class'] = 'acf-icon acf-icon-more';
			$menu_item['extra_class'] = 'acf-header-tab-acf-more';
		}

		// Detect active state.
		if ( $submenu_file === $sub_item[2] || $plugin_page === $sub_item[2] ) {
			$menu_item['is_active'] = true;
		}

		// Handle "Add New" versions of edit page.
		if ( str_replace( 'edit', 'post-new', $sub_item[2] ) === $submenu_file ) {
			$menu_item['is_active'] = true;
		}

		// Organize the menu items.
		if ( in_array( $menu_item['class'], $core_tabs_classes, true ) ) {
			$core_tabs[] = $menu_item;
		}
	}
}

// Bail early if set to false.
if ( $core_tabs === false ) {
	return;
}

$acf_wpengine_logo_link = acf_add_url_utm_tags(
	'https://wpengine.com/',
	'bx_prod_referral',
	acf_is_pro() ? 'acf_pro_plugin_topbar_logo' : 'acf_free_plugin_topbar_logo',
	false,
	'acf_plugin',
	'referral'
);

/**
 * Helper function for looping over the provided menu items
 * and echoing out the necessary markup.
 *
 * @since 6.2
 *
 * @param array  $menu_items An array of menu items to print.
 * @param string $section    The section being printed.
 * @return void
 */
function acf_print_menu_section( $menu_items, $section = '' ) {
	// Bail if no menu items.
	if ( ! is_array( $menu_items ) || empty( $menu_items ) ) {
		return;
	}

	$section_html = '';

	foreach ( $menu_items as $menu_item ) {
		$class      = ! empty( $menu_item['class'] ) ? $menu_item['class'] : $menu_item['text'];
		$icon_class = ! empty( $menu_item['icon_class'] ) ? $menu_item['icon_class'] : 'acf-icon';
		$extra_class = ! empty( $menu_item['extra_class'] ) ? ' ' . esc_attr( $menu_item['extra_class'] ) : '';
		$target     = ! empty( $menu_item['target'] ) ? ' target="' . esc_attr( $menu_item['target'] ) . '"' : '';
		$aria_label = ! empty( $menu_item['aria-label'] ) ? ' aria-label="' . esc_attr( $menu_item['aria-label'] ) . '"' : '';
		$li_class   = ! empty( $menu_item['li_class'] ) ? esc_attr( $menu_item['li_class'] ) : '';

		$html = sprintf(
			'<a class="acf-tab%s %s%s" href="%s"%s%s><i class="%s"></i>%s</a>',
			! empty( $menu_item['is_active'] ) ? ' is-active' : '',
			'acf-header-tab-' . esc_attr( acf_slugify( $class ) ),
			$extra_class,
			esc_url( $menu_item['url'] ),
			$target,
			$aria_label,
			esc_attr( $icon_class ),
			acf_esc_html( $menu_item['text'] )
		);

		if ( 'core' !== $section ) {
			if ( $li_class === '' ) {
				$html = '<li>' . $html . '</li>';
			} else {
				$html = sprintf( '<li class="%s">', $li_class ) . $html . '</li>';
			}
		}

		$section_html .= $html;
	}

	echo $section_html; //phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- safely built and escaped HTML above.
}
?>
<div class="acf-admin-toolbar">
	<div class="acf-admin-toolbar-inner">
		<div class="acf-nav-wrap">
			<?php if ( acf_is_pro() && acf_pro_is_license_active() ) { ?>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=acf-field-group' ) ); ?>" class="acf-logo pro" aria-label="<?php esc_attr_e( 'Edit ACF Field Groups', 'acf' ); ?>">
					<img src="<?php echo esc_url( acf_get_url( 'assets/images/acf-pro-logo.svg' ) ); ?>" alt="<?php esc_attr_e( 'Advanced Custom Fields logo', 'acf' ); ?>">
				</a>
			<?php } else { ?>
				<a href="<?php echo esc_url( admin_url( 'edit.php?post_type=acf-field-group' ) ); ?>" class="acf-logo" aria-label="<?php esc_attr_e( 'Edit ACF Field Groups', 'acf' ); ?>">
					<img src="<?php echo esc_url( acf_get_url( 'assets/images/acf-logo.svg' ) ); ?>" alt="<?php esc_attr_e( 'Advanced Custom Fields logo', 'acf' ); ?>">
				</a>
			<?php } ?>

			<h2><?php echo esc_html( acf_get_setting( 'name' ) ); ?></h2>
			<?php acf_print_menu_section( $core_tabs, 'core' ); ?>
		</div>
	</div>
</div>

<?php

global $plugin_page;
$screen = get_current_screen();

if ( ! in_array( $screen->id, acf_get_internal_post_types(), true ) ) {
	if ( $plugin_page == 'acf-tools' ) {
		$acf_page_title = __( 'Tools', 'acf' );
	} elseif ( $plugin_page == 'acf-settings-updates' ) {
		$acf_page_title = __( 'Updates', 'acf' );
	} elseif ( $plugin_page == 'acf_options_preview' && ! acf_is_pro() ) {
		$acf_page_title = __( 'Options Pages', 'acf' );
	}
	acf_get_view( 'global/header' );
}
?>
