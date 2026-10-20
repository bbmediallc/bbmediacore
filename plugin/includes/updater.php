<?php
// Updates from GitHub releases.
//
// Each release on github.com/bbmediallc/bbmediacore must have a
// bbmediacore.zip asset (scripts/build.sh makes it, and the GitHub Action
// attaches it). WordPress shows the update like any other plugin update.
//
// Sites can only see releases while the repository is public. Austin keeps
// it private and makes it public only while rolling out an update, so
// results are cached briefly, and Dashboard > Updates > "Check again"
// always asks GitHub fresh.
//
// Test versions (tags like v1.7.0-beta.1, published as GitHub pre-releases)
// are offered only to sites with this line in wp-config.php:
//   define('BBCORE_UPDATE_CHANNEL', 'beta');

if (!defined('ABSPATH')) {
    exit;
}

function bbcore_update_channel() {
    return defined('BBCORE_UPDATE_CHANNEL') && BBCORE_UPDATE_CHANNEL === 'beta' ? 'beta' : 'stable';
}

function bbcore_release_cache_key() {
    return 'bbcore_release_' . bbcore_update_channel();
}

// A GitHub release as the fields we need, or null if it has no bbmediacore.zip.
function bbcore_parse_release($body) {
    if (!is_array($body) || empty($body['tag_name']) || !empty($body['draft'])) {
        return null;
    }
    $package = '';
    foreach (isset($body['assets']) ? (array) $body['assets'] : array() as $asset) {
        if (isset($asset['name']) && $asset['name'] === 'bbmediacore.zip') {
            $package = $asset['browser_download_url'];
        }
    }
    if ($package === '') {
        return null;
    }
    return array(
        'version' => ltrim($body['tag_name'], 'vV'),
        'package' => $package,
        'url' => isset($body['html_url']) ? $body['html_url'] : '',
        'notes' => isset($body['body']) ? $body['body'] : '',
        'published' => isset($body['published_at']) ? $body['published_at'] : '',
    );
}

function bbcore_github_get($path) {
    $response = wp_remote_get('https://api.github.com/repos/' . BBCORE_GITHUB_REPO . $path, array(
        'timeout' => 10,
        'headers' => array(
            'Accept' => 'application/vnd.github+json',
            'User-Agent' => 'WordPress/' . get_bloginfo('version') . '; ' . home_url(),
        ),
    ));
    if (is_wp_error($response) || wp_remote_retrieve_response_code($response) !== 200) {
        return null;
    }
    return json_decode(wp_remote_retrieve_body($response), true);
}

// Newest release for this site's channel. Cached for 1 hour, or 10 minutes
// after a failure (e.g. while the repository is private).
function bbcore_latest_release($force = false) {
    $cache_key = bbcore_release_cache_key();
    if (!$force) {
        $cached = get_site_transient($cache_key);
        if ($cached !== false) {
            return $cached ? $cached : null;
        }
    }

    $release = null;
    if (bbcore_update_channel() === 'beta') {
        // Newest of the recent releases, pre-releases included.
        foreach ((array) bbcore_github_get('/releases?per_page=20') as $body) {
            $candidate = bbcore_parse_release($body);
            if ($candidate && (!$release || version_compare($candidate['version'], $release['version'], '>'))) {
                $release = $candidate;
            }
        }
    } else {
        // GitHub's "latest" never includes pre-releases.
        $release = bbcore_parse_release(bbcore_github_get('/releases/latest'));
    }

    // Failures are cached as 0 so they aren't retried on every page load.
    set_site_transient($cache_key, $release ? $release : 0, $release ? HOUR_IN_SECONDS : 10 * MINUTE_IN_SECONDS);
    return $release;
}

// True once per request when someone clicked "Check again" on Dashboard > Updates.
function bbcore_is_forced_update_check() {
    static $used = false;
    if ($used || !is_admin() || empty($_GET['force-check'])) {
        return false;
    }
    $used = true;
    return true;
}

function bbcore_plugin_slug() {
    return dirname(BBCORE_BASENAME);
}

// Runs when WordPress refreshes its list of plugin updates (about twice a day).
function bbcore_check_for_update($transient) {
    if (!is_object($transient) || empty($transient->checked)) {
        return $transient;
    }

    $release = bbcore_latest_release(bbcore_is_forced_update_check());
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
        delete_site_transient(bbcore_release_cache_key());
    }
}
add_action('upgrader_process_complete', 'bbcore_after_update', 10, 2);
