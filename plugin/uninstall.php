<?php
// Runs when the plugin is deleted from the Plugins page (not on deactivate
// or update). Removes this plugin's saved settings.

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

foreach (array('bbcore_active_brand', 'bbcore_brands', 'bbcore_needs_setup', 'bbcore_suggested_brand', 'bbcore_db_version', 'bbcore_tutorials', 'bbcore_tutorials_enabled') as $option) {
    delete_option($option);
}
delete_site_transient('bbcore_release_stable');
delete_site_transient('bbcore_release_beta');
delete_transient('bbcore_setup_redirect');
