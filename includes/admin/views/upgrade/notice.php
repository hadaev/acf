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

// calculate add-ons (non pro only)
$plugins = array();

if ( ! acf_get_setting( 'pro' ) ) {
	if ( is_plugin_active( 'acf-repeater/acf-repeater.php' ) ) {
		$plugins[] = __( 'Repeater', 'acf' );
	}
	if ( is_plugin_active( 'acf-flexible-content/acf-flexible-content.php' ) ) {
		$plugins[] = __( 'Flexible Content', 'acf' );
	}
	if ( is_plugin_active( 'acf-gallery/acf-gallery.php' ) ) {
		$plugins[] = __( 'Gallery', 'acf' );
	}
	if ( is_plugin_active( 'acf-options-page/acf-options-page.php' ) ) {
		$plugins[] = __( 'Options Page', 'acf' );
	}
}

?>


