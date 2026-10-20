<?php
// Where the company settings live.
//
// Network-activated on multisite: one set for the whole network (the active
// company, company edits, setup state), so every site follows it.
// Otherwise: per site, as normal options. Tutorials are always per site.

if (!defined('ABSPATH')) {
    exit;
}

function bbcore_is_network_active() {
    if (!is_multisite()) {
        return false;
    }
    $plugins = get_site_option('active_sitewide_plugins', array());
    return is_array($plugins) && isset($plugins[BBCORE_BASENAME]);
}

// $network: null works it out; true/false forces it (the activation hook
// runs before WordPress records a network activation, so it passes it in).
function bbcore_network_scope($network = null) {
    return $network === null ? bbcore_is_network_active() : (bool) $network;
}

function bbcore_get_brand_option($name, $default = false, $network = null) {
    return bbcore_network_scope($network) ? get_site_option($name, $default) : get_option($name, $default);
}

function bbcore_update_brand_option($name, $value, $network = null) {
    return bbcore_network_scope($network) ? update_site_option($name, $value) : update_option($name, $value);
}

function bbcore_delete_brand_option($name, $network = null) {
    return bbcore_network_scope($network) ? delete_site_option($name) : delete_option($name);
}

// "Send the brand admin to setup" flag, set on activation.
function bbcore_set_setup_redirect($network = null) {
    if (bbcore_network_scope($network)) {
        set_site_transient('bbcore_setup_redirect', 1, 5 * MINUTE_IN_SECONDS);
    } else {
        set_transient('bbcore_setup_redirect', 1, 5 * MINUTE_IN_SECONDS);
    }
}

function bbcore_setup_redirect_pending() {
    return bbcore_network_scope() ? (bool) get_site_transient('bbcore_setup_redirect') : (bool) get_transient('bbcore_setup_redirect');
}

function bbcore_clear_setup_redirect() {
    delete_site_transient('bbcore_setup_redirect');
    delete_transient('bbcore_setup_redirect');
}
