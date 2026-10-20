<?php
// Shows the active brand's name and description on the Plugins page,
// and in the admin footer.

if (!defined('ABSPATH')) {
    exit;
}

function bbcore_brand_plugin_row($plugins) {
    if (!isset($plugins[BBCORE_BASENAME])) {
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

function bbcore_admin_footer_text() {
    $url = bbcore_brand('website_url');
    $name = bbcore_brand('name');
    return '<span id="footer-thankyou">Powered by <a href="' . esc_url($url) . '" target="_blank">' . esc_html($name) . '</a> | Version ' . esc_html(BBCORE_VERSION) . '</span>';
}
add_filter('admin_footer_text', 'bbcore_admin_footer_text');
