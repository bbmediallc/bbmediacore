<?php
// Shows the active brand's name and description on the Plugins page,
// and in the admin footer. Only the brand admin sees this plugin on the
// Plugins page or its updates anywhere in wp-admin.

if (!defined('ABSPATH')) {
    exit;
}

function bbcore_brand_plugin_row($plugins) {
    if (!isset($plugins[BBCORE_BASENAME])) {
        return $plugins;
    }
    if (!bbcore_is_brand_admin()) {
        unset($plugins[BBCORE_BASENAME]);
        return $plugins;
    }
    $name = bbcore_brand('plugin_name');
    $description = bbcore_brand('plugin_description');
    if ($name !== '') {
        $plugins[BBCORE_BASENAME]['Name'] = $name;
        $plugins[BBCORE_BASENAME]['Title'] = $name;
    }
    if ($description !== '') {
        $plugins[BBCORE_BASENAME]['Description'] = $description;
    }
    return $plugins;
}
add_filter('all_plugins', 'bbcore_brand_plugin_row');

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
