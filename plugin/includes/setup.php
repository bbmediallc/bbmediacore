<?php
// First-run setup and upgrades.
//
// Fresh activation (a new site, or replacing an old K&C/Pace plugin):
// the brand admin is sent to Brand Settings to choose the company.
// Updating in place from the old single-brand BB Media Core never runs the
// activation hook, so those sites are set to B&B Media automatically.

if (!defined('ABSPATH')) {
    exit;
}

// Folder names of the old per-company plugins, and the brand each one was.
function bbcore_legacy_plugins() {
    return array(
        'knccore' => 'knc',
        'pacecore' => 'pace',
    );
}

// Active old per-company plugins, as plugin basename => brand slug.
function bbcore_find_active_legacy_plugins() {
    $found = array();
    foreach ((array) get_option('active_plugins', array()) as $basename) {
        $folder = dirname($basename);
        foreach (bbcore_legacy_plugins() as $legacy_folder => $brand) {
            if (strpos($folder, $legacy_folder) === 0) {
                $found[$basename] = $brand;
            }
        }
    }
    return $found;
}

function bbcore_activate() {
    if (!function_exists('deactivate_plugins')) {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';
    }

    // An old K&C or Pace plugin left active would add a second dashboard,
    // so turn it off (it is not deleted) and remember which brand it was.
    foreach (bbcore_find_active_legacy_plugins() as $basename => $brand) {
        deactivate_plugins($basename);
        if (!get_option('bbcore_suggested_brand')) {
            update_option('bbcore_suggested_brand', $brand);
        }
    }

    bbcore_import_acf_tutorials();

    if (!get_option('bbcore_active_brand')) {
        update_option('bbcore_needs_setup', 1);
        set_transient('bbcore_setup_redirect', 1, 5 * MINUTE_IN_SECONDS);
    }

    update_option('bbcore_db_version', BBCORE_VERSION);
}

function bbcore_needs_setup() {
    return (bool) get_option('bbcore_needs_setup');
}

// Runs once per new version.
function bbcore_maybe_upgrade() {
    if (get_option('bbcore_db_version') === BBCORE_VERSION) {
        return;
    }

    if (get_option('bbcore_active_brand') === false && !bbcore_needs_setup()) {
        // Updated in place from the old single-brand BB Media Core.
        update_option('bbcore_active_brand', BBCORE_DEFAULT_BRAND);
    }

    // A site running the one-off ACF tutorials version keeps its videos.
    bbcore_import_acf_tutorials();

    // Leftovers from 1.6.x.
    delete_option('bb_media_core_reactivate_plugin');
    delete_transient('bb_media_core_update');
    delete_transient('bb_latest_blog_posts');

    update_option('bbcore_db_version', BBCORE_VERSION);
}
add_action('plugins_loaded', 'bbcore_maybe_upgrade');

function bbcore_brand_settings_url() {
    return admin_url('options-general.php?page=bbcore-brand-settings');
}

// Right after activation, take the brand admin straight to setup.
function bbcore_setup_redirect() {
    if (!get_transient('bbcore_setup_redirect') || !bbcore_is_brand_admin()) {
        return;
    }
    if (wp_doing_ajax() || isset($_GET['activate-multi'])) {
        return;
    }
    delete_transient('bbcore_setup_redirect');
    wp_safe_redirect(bbcore_brand_settings_url());
    exit;
}
add_action('admin_init', 'bbcore_setup_redirect', 1);

// Reminder on every other admin screen until a company is chosen.
function bbcore_setup_notice() {
    if (!bbcore_needs_setup() || !bbcore_is_brand_admin()) {
        return;
    }
    $screen = get_current_screen();
    if ($screen && $screen->id === 'settings_page_bbcore-brand-settings') {
        return;
    }
    printf(
        '<div class="notice notice-warning"><p><strong>Core plugin:</strong> choose which company this site belongs to. <a href="%s">Finish setup</a></p></div>',
        esc_url(bbcore_brand_settings_url())
    );
}
add_action('admin_notices', 'bbcore_setup_notice');
