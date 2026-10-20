<?php
/*
Plugin Name: BB Media Core
Plugin URI: https://github.com/bbmediallc/bbmediacore
Description: A plugin that provides core functionality for B&B Media websites.
Version: 2.0.0
Author: B&B Media, LLC
Author URI: https://bandbmedia.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 5.6
*/

// The Name and Description above are what WordPress reads from the file.
// includes/plugin-list.php swaps them for the active brand's values.
// Never rename this file or the bbmediacore folder: WordPress identifies
// the active plugin as "bbmediacore/custom-dashboard-plugin.php", and
// renaming either one would deactivate it on every site that updates.

if (!defined('ABSPATH')) {
    exit;
}

// Must match the Version header above and "Stable tag" in readme.txt.
// scripts/build.sh refuses to build if they differ.
define('BBCORE_VERSION', '2.0.0');
define('BBCORE_FILE', __FILE__);
define('BBCORE_DIR', plugin_dir_path(__FILE__));
define('BBCORE_URL', plugin_dir_url(__FILE__));
define('BBCORE_BASENAME', plugin_basename(__FILE__));
define('BBCORE_GITHUB_REPO', 'bbmediallc/bbmediacore');

// Only these WordPress accounts can see the brand setup and Brand Settings
// screens. To change it on a single site, add this to wp-config.php:
// define('BBCORE_ADMIN_EMAILS', 'support@bandbmedia.com, someone@example.com');
if (!defined('BBCORE_ADMIN_EMAILS')) {
    define('BBCORE_ADMIN_EMAILS', 'support@bandbmedia.com');
}

require_once BBCORE_DIR . 'includes/access.php';
require_once BBCORE_DIR . 'includes/brands.php';
require_once BBCORE_DIR . 'includes/setup.php';
require_once BBCORE_DIR . 'includes/brand-settings.php';
require_once BBCORE_DIR . 'includes/plugin-list.php';
require_once BBCORE_DIR . 'includes/dashboard.php';
require_once BBCORE_DIR . 'includes/tutorials.php';
require_once BBCORE_DIR . 'includes/support-form.php';
require_once BBCORE_DIR . 'includes/blog-feed.php';
require_once BBCORE_DIR . 'includes/login.php';
require_once BBCORE_DIR . 'includes/updater.php';

register_activation_hook(__FILE__, 'bbcore_activate');
