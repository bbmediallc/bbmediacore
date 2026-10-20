<?php
// The admin footer, and who sees this plugin. Only the brand admin sees it
// on the Plugins page, or its updates anywhere in wp-admin, so it keeps its
// real name, BB Media Core, whichever company is active.

if (!defined('ABSPATH')) {
    exit;
}

function bbcore_hide_plugin_row_from_others($plugins) {
    if (isset($plugins[BBCORE_BASENAME]) && !bbcore_is_brand_admin()) {
        unset($plugins[BBCORE_BASENAME]);
    }
    return $plugins;
}
add_filter('all_plugins', 'bbcore_hide_plugin_row_from_others');

// Hides this plugin's available update from everyone but the brand admin:
// the Updates screen, the update counts in the menu and toolbar, and the
// Plugins page. Only applies to someone logged in to wp-admin, so
// background auto-updates (WP-Cron, no one logged in) still see it.
function bbcore_hide_update_from_others($transient) {
    if (!is_object($transient) || !isset($transient->response[BBCORE_BASENAME])) {
        return $transient;
    }
    // wp_get_current_user() doesn't exist yet if another plugin reads the
    // update list while plugins are still loading.
    if (!is_admin() || wp_doing_cron() || !function_exists('wp_get_current_user') || !is_user_logged_in() || bbcore_is_brand_admin()) {
        return $transient;
    }
    // Change a copy, not WordPress's cached object. If something saves this
    // trimmed list, bbcore_check_for_update() adds the update back on save.
    $transient = clone $transient;
    unset($transient->response[BBCORE_BASENAME]);
    return $transient;
}
add_filter('site_transient_update_plugins', 'bbcore_hide_update_from_others');

function bbcore_admin_footer_text() {
    $url = bbcore_brand('website_url');
    $name = bbcore_brand('name');
    return '<span id="footer-thankyou">Powered by <a href="' . esc_url($url) . '" target="_blank">' . esc_html($name) . '</a> | Version ' . esc_html(BBCORE_VERSION) . '</span>';
}
add_filter('admin_footer_text', 'bbcore_admin_footer_text');
