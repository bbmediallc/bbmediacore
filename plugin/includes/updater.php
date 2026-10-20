<?php
// Updates from GitHub releases.
//
// Each release on github.com/bbmediallc/bbmediacore must have a
// bbmediacore.zip asset (scripts/build.sh makes it, and the GitHub Action
// attaches it). WordPress shows the update like any other plugin update.

if (!defined('ABSPATH')) {
    exit;
}

define('BBCORE_RELEASE_CACHE', 'bbcore_latest_release');

// Latest release info from GitHub, cached for 6 hours (1 hour after a failure)
// so 200+ sites don't hit GitHub's rate limit.
function bbcore_latest_release() {
    $cached = get_site_transient(BBCORE_RELEASE_CACHE);
    if ($cached !== false) {
        return $cached ? $cached : null;
    }

    $response = wp_remote_get('https://api.github.com/repos/' . BBCORE_GITHUB_REPO . '/releases/latest', array(
        'timeout' => 10,
        'headers' => array(
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
        ),
    ));

    $release = null;
    if (!is_wp_error($response) && wp_remote_retrieve_response_code($response) === 200) {
        $body = json_decode(wp_remote_retrieve_body($response), true);
        if (is_array($body) && !empty($body['tag_name'])) {
            $package = '';
            foreach (isset($body['assets']) ? (array) $body['assets'] : array() as $asset) {
                if (isset($asset['name']) && $asset['name'] === 'bbmediacore.zip') {
                    $package = $asset['browser_download_url'];
                }
            }
            if ($package !== '') {
                $release = array(
                    'version' => ltrim($body['tag_name'], 'vV'),
                    'package' => $package,
                    'url' => isset($body['html_url']) ? $body['html_url'] : '',
                    'notes' => isset($body['body']) ? $body['body'] : '',
                    'published' => isset($body['published_at']) ? $body['published_at'] : '',
                );
            }
        }
    }

    // Cache failures as 0 so a GitHub outage isn't retried on every page load.
    set_site_transient(BBCORE_RELEASE_CACHE, $release ? $release : 0, $release ? 6 * HOUR_IN_SECONDS : HOUR_IN_SECONDS);
    return $release;
}

function bbcore_plugin_slug() {
    return dirname(BBCORE_BASENAME);
}

// Runs when WordPress refreshes its list of plugin updates (about twice a day).
function bbcore_check_for_update($transient) {
    if (!is_object($transient) || empty($transient->checked)) {
        return $transient;
    }

    $release = bbcore_latest_release();
    if (!$release) {
        return $transient;
    }

    $item = (object) array(
        'id' => 'github.com/' . BBCORE_GITHUB_REPO,
        'slug' => bbcore_plugin_slug(),
        'plugin' => BBCORE_BASENAME,
        'new_version' => $release['version'],
        'url' => $release['url'],
        'package' => $release['package'],
        'icons' => array('svg' => bbcore_brand_image_url('menu_icon')),
    );

    if (version_compare(BBCORE_VERSION, $release['version'], '<')) {
        $transient->response[BBCORE_BASENAME] = $item;
    } else {
        unset($transient->response[BBCORE_BASENAME]);
        $transient->no_update[BBCORE_BASENAME] = $item;
    }
    return $transient;
}
add_filter('pre_set_site_transient_update_plugins', 'bbcore_check_for_update');

// The "View details" popup, shown with the active brand's name.
function bbcore_plugin_info($result, $action, $args) {
    if ($action !== 'plugin_information' || !isset($args->slug) || $args->slug !== bbcore_plugin_slug()) {
        return $result;
    }
    $release = bbcore_latest_release();
    $notes = $release && $release['notes'] !== '' ? wpautop(esc_html($release['notes'])) : '';

    return (object) array(
        'name' => bbcore_brand('plugin_name'),
        'slug' => bbcore_plugin_slug(),
        'version' => $release ? $release['version'] : BBCORE_VERSION,
        'author' => esc_html(bbcore_brand('name')),
        'homepage' => bbcore_brand('website_url'),
        'download_link' => $release ? $release['package'] : '',
        'last_updated' => $release ? $release['published'] : '',
        'requires' => '5.0',
        'requires_php' => '5.6',
        'sections' => array(
            'description' => esc_html(bbcore_brand('plugin_description')),
            'changelog' => $notes,
        ),
    );
}
add_filter('plugins_api', 'bbcore_plugin_info', 20, 3);

// After this plugin updates, forget the cached release so the next check is fresh.
function bbcore_after_update($upgrader, $options) {
    if (!isset($options['action'], $options['type']) || $options['action'] !== 'update' || $options['type'] !== 'plugin') {
        return;
    }
    $plugins = isset($options['plugins']) ? (array) $options['plugins'] : array();
    if (isset($options['plugin'])) {
        $plugins[] = $options['plugin'];
    }
    if (in_array(BBCORE_BASENAME, $plugins, true)) {
        delete_site_transient(BBCORE_RELEASE_CACHE);
    }
}
add_action('upgrader_process_complete', 'bbcore_after_update', 10, 2);
