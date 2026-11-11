=== Addition Custom Fields (ACF®) ===
Contributors: deliciousbrains, wpengine, elliotcondon, mattshaw, lgladdy, antpb, johnstonphilip, dalewilliams, polevaultweb
Tags: acf, fields, custom fields, meta, repeater
Requires at least: 6.2
Tested up to: 7.4
Requires PHP: 8.4
Stable tag: 0.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

ACF helps customize WordPress with powerful, professional and intuitive fields. Proudly powering over 2 million sites, WordPress developers love ACF.

= Options Pages =

Register a settings page in your theme or custom plugin:

    add_action( 'acf/init', function() {
        acf_add_options_page( array(
            'page_title' => 'Theme Settings',
            'menu_title' => 'Theme Settings',
            'menu_slug'  => 'theme-settings',
            'capability' => 'edit_posts',
        ) );
    } );

In ACF, create a field group and set its location to “Options Page” → “is equal to” → “Theme Settings”. The fields will appear on the new admin page. Read their values with the `options` post ID:

    $header_logo = get_field( 'header_logo', 'options' );

Use `acf_add_options_sub_page()` to register a child page. Values are stored in the WordPress options table; the page’s `post_id` setting can be changed to use a separate option-name prefix.
