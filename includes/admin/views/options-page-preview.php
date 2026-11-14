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

$acf_options_pages_desc = sprintf(
	/* translators: %s URL to ACF options pages documentation */
	__( 'ACF <a href="%s" target="_blank">options pages</a> are custom admin pages for managing global settings via fields. You can create multiple pages and sub-pages.', 'acf' ),
	acf_add_url_utm_tags( 'https://www.advancedcustomfields.com/resources/options-page/', 'docs', 'no-options-pages' )
);

$acf_getting_started = sprintf(
	/* translators: %s url to getting started guide */
	__( 'New to ACF? Take a look at our <a href="%s" target="_blank">getting started guide</a>.', 'acf' ),
	acf_add_url_utm_tags( 'https://www.advancedcustomfields.com/resources/getting-started-with-acf/', 'docs', 'no-options-pages' )
);

?>
<div class="wrap acf_options_preview_wrap">
	<table class="wp-list-table widefat fixed striped">
		<tbody id="the-list">
			<tr class="no-items">
				<td class="colspanchange" colspan="6">
					<div class="acf-no-field-groups-wrapper">
						<div class="acf-no-field-groups-inner">
							<img src="<?php echo esc_url( acf_get_url( 'assets/images/empty-post-types.svg' ) ); ?>" />
								<h2><?php esc_html_e( 'No options pages found', 'acf' ); ?></h2>
								<p><?php echo acf_esc_html( $acf_options_pages_desc ); ?></p>
								<p class="acf-small"><?php echo acf_esc_html( $acf_getting_started ); ?></p>
						</div>
					</div>
				</td>
			</tr>
		</tbody>
	</table>
</div>
