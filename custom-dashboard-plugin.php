<?php
/*
Plugin Name: BB Media Core
Plugin URI: https://bandbmedia.com
Description: A plugin that provides core functionality for B&B Media websites.
Version: 1.2
Author: B&B Media, LLC
Author URI: https://bandbmedia.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

// Admin footer modification
function swp_modify_footer_admin() {
    echo '<span id="footer-thankyou">Powered by <a href="https://bandbmedia.com" target="_blank">B&B Media</a></span>';
}
add_filter('admin_footer_text', 'swp_modify_footer_admin');

// Replace WordPress login logo
function custom_login_logo() {
    ?>
    <style type="text/css">
        body.login div#login h1 a {
            background-image: url('<?php echo esc_url(plugin_dir_url(__FILE__) . 'images/logo.png'); ?>');
            background-size: contain;
            width: 320px;
            height: 80px;
        }

        body.login {
            background: url('<?php echo esc_url(plugin_dir_url(__FILE__) . 'images/login-bg.jpg'); ?>');
            background-size: cover;
            background-position: 50%;
        }

        .login form {
            border: unset !important;
        }

        div#login {
            background: #ffffff;
            padding: 25px;
            border-top: solid 4px #35479d;
            box-shadow: 0 0 40px #36489d;
            position: relative;
            top: 25%;
            transform: translateY(-25%) !important;
        }

        body.login:before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: transparent;
            background-image: linear-gradient(-45deg, #26c2e0 1%, #35479d 100%);
            opacity: .9;
        }

    </style>
    <?php
}
add_action('login_enqueue_scripts', 'custom_login_logo');

// Create a Settings Tab
function bb_media_settings_page() {
    // Check if the user has submitted the form to save the token
    if (isset($_POST['bb_media_save_token']) && check_admin_referer('bb_media_save_token_action', 'bb_media_nonce')) {
        $token = sanitize_text_field($_POST['bb_media_license_token']);
        update_option('bb_media_license_token', $token); // Save the token in the database
        echo '<div class="updated"><p>Token saved successfully!</p></div>';
    }

    // Check if the user has submitted the form to deactivate the token
    if (isset($_POST['bb_media_deactivate_token']) && check_admin_referer('bb_media_deactivate_token_action', 'bb_media_nonce')) {
        delete_option('bb_media_license_token'); // Remove the token from the database
        echo '<div class="updated"><p>Token deactivated successfully!</p></div>';
    }

    // Retrieve the saved token
    $saved_token = get_option('bb_media_license_token', '');

    ?>
    <div class="wrap">
        <h1>B&B Media Plugin Settings</h1>
        <?php if (empty($saved_token)): ?>
            <!-- Show input field if token is not set -->
            <p>Enter your GitHub token below to enable plugin updates.</p>
            <form method="post">
                <?php wp_nonce_field('bb_media_save_token_action', 'bb_media_nonce'); ?>
                <table class="form-table">
                    <tr>
                        <th scope="row"><label for="bb_media_license_token">License Token</label></th>
                        <td>
                            <input type="text" name="bb_media_license_token" id="bb_media_license_token" 
                                   class="regular-text" required />
                        </td>
                    </tr>
                </table>
                <p class="submit">
                    <input type="submit" name="bb_media_save_token" id="bb_media_save_token" 
                           class="button button-primary" value="Save Token">
                </p>
            </form>
        <?php else: ?>
            <!-- Show message and deactivate button if token is set -->
            <p>Your token is active and updates are enabled.</p>
            <form method="post">
                <?php wp_nonce_field('bb_media_deactivate_token_action', 'bb_media_nonce'); ?>
                <p class="submit">
                    <input type="submit" name="bb_media_deactivate_token" id="bb_media_deactivate_token" 
                           class="button button-secondary" value="Deactivate Token">
                </p>
            </form>
        <?php endif; ?>
    </div>
    <?php
}

// Remove default dashboard menu item
function remove_default_dashboard_menu() {
    remove_menu_page('index.php'); // Removes "Dashboard"
}
add_action('admin_menu', 'remove_default_dashboard_menu', 999);

// Add the custom dashboard and updates page links
function add_custom_dashboard_page() {
    add_menu_page(
        'Custom Dashboard',          // Page title
        'Dashboard',                 // Menu title
        'manage_options',            // Capability
        'custom_dashboard',          // Slug
        'custom_dashboard_content',  // Callback to render content
        'dashicons-admin-home',      // Icon
        2                            // Position
    );

    // Add the settings submenu
    add_submenu_page(
        'custom_dashboard',          // Parent slug
        'Settings',                  // Page title
        'Settings',                  // Menu title
        'manage_options',            // Capability
        'bb-media-settings',         // Slug
        'bb_media_settings_page'     // Callback to render settings page
    );

    // Add Updates link
    add_submenu_page(
        'custom_dashboard',          // Parent slug
        'WordPress Updates',         // Page title
        'Updates',                   // Menu title
        'manage_options',            // Capability
        'update-core.php',           // Slug (core WP Updates page)
        null                         // No callback needed, WordPress handles this
    );
}
add_action('admin_menu', 'add_custom_dashboard_page');


// Force redirection from default dashboard to the custom dashboard
function redirect_to_custom_dashboard() {
    global $pagenow;

    // Skip redirection for specific pages
    $excluded_pages = [
        'custom_dashboard',    // Custom dashboard
        'bb-media-settings',   // Plugin settings
        'update-core.php'      // WordPress Updates page
    ];

    if (isset($_GET['page']) && in_array($_GET['page'], $excluded_pages)) {
        return;
    }

    // Allow access to Updates page
    if ($pagenow === 'update-core.php') {
        return;
    }

    // Redirect only from the default dashboard (index.php)
    if ($pagenow === 'index.php' && is_user_logged_in() && current_user_can('manage_options')) {
        wp_redirect(admin_url('admin.php?page=custom_dashboard'));
        exit;
    }
}
add_action('admin_init', 'redirect_to_custom_dashboard');

// Custom dashboard page content
function custom_dashboard_content() {
    // Get current user info
    $current_user = wp_get_current_user();
    $first_name = !empty($current_user->user_firstname) ? $current_user->user_firstname : 'there'; // Use 'there' if no first name is set

    ?>
    <div class="wrap">
        <div class="custom-dashboard-layout">
            <div class="welcome-message">
                <h1>Hi <?php echo esc_html($first_name); ?>, welcome back.</h1>
                <p>Working on your website should be simple and easy, but if you need help, we're available via the form below. Just drop us a line and we'll get back with you ASAP. In the meantime, feel free to explore our latest blogs below. Happy editing!</p>
            </div>
            <div class="custom-dashboard-left">
                <div class="custom-dashboard-blog-feed">
                    <?php echo do_shortcode('[latest_blog_posts]'); ?>
                </div>
            </div>
            <div class="custom-dashboard-right">
                <div class="support-form">
                    <div class="support-header">
                        <div class="form-left">
                            <h2>Need Some Help?</h2>
                            <p>Submit a support ticket here, and our team will be in touch as soon as possible.</p>
                        </div>
                        <div class="form-right">
                            <div style="text-align: right; margin-bottom: 10px;">
                                <img src="<?php echo esc_url(plugin_dir_url(__FILE__) . 'images/logo.png'); ?>" alt="B&B Media Logo" style="max-width: 150px; height: auto;">
                            </div>
                        </div>    
                    </div>          
                    <?php display_support_form(); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Display the support form
function display_support_form() {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_ticket'])) {
        handle_support_form_submission();
    }

    ?>
    <form id="support-form" method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
        <?php wp_nonce_field('support_form_action', 'support_form_nonce'); ?>
        <input type="hidden" name="action" value="handle_support_form_submission">

        <label for="full_name">Full Name:</label>
        <input type="text" id="full_name" name="full_name" required>

        <label for="email_address">Email Address:</label>
        <input type="email" id="email_address" name="email_address" required>

        <label for="urgency">Urgency (1-5):</label>
        <select id="urgency" name="urgency" required>
            <option value="1">1 - Low</option>
            <option value="2">2 - Medium</option>
            <option value="3">3 - Normal</option>
            <option value="4">4 - High</option>
            <option value="5">5 - Urgent</option>
        </select>

        <label for="description">Description of the issue:</label>
        <textarea id="description" name="description" rows="4" cols="50" required></textarea>

        <input type="submit" name="submit_ticket" value="Submit Support Ticket">
    </form>
    <div id="form-message" style="display: none;"></div>
    <?php
}

add_action('wp_ajax_handle_support_form_submission', 'handle_support_form_submission'); // Logged-in users
add_action('wp_ajax_nopriv_handle_support_form_submission', 'handle_support_form_submission'); // Guests

function handle_support_form_submission() {
    if (!isset($_POST['support_form_nonce']) || !wp_verify_nonce($_POST['support_form_nonce'], 'support_form_action')) {
        wp_send_json_error(['message' => 'Security check failed.']);
        return;
    }

    // Retrieve and sanitize form data
    $full_name = sanitize_text_field($_POST['full_name']);
    $email_address = sanitize_email($_POST['email_address']);
    $urgency = intval($_POST['urgency']);
    $description = sanitize_textarea_field($_POST['description']);

    // Attempt to send the email
    $to = 'support@bandbmedia.com';
    $subject = 'New Support Ticket';
    $message = "Name: $full_name\nEmail: $email_address\nUrgency: $urgency\nDescription: $description";
    $headers = ['Content-Type: text/plain; charset=UTF-8'];

    if (wp_mail($to, $subject, $message, $headers)) {
        wp_send_json_success(['message' => 'Your support ticket has been submitted successfully.']);
    } else {
        wp_send_json_error(['message' => 'Failed to send email. Please try again later.']);
    }
}

// Function to display the latest blog posts with featured images, excerpts, and author names
function display_latest_blog_posts() {
    include_once(ABSPATH . WPINC . '/feed.php'); // Load WordPress feed functionality

    $feed_url = 'https://bandbmedia.com/feed/';
    $feed = fetch_feed($feed_url);

    if (is_wp_error($feed)) {
        return '<p>Unable to fetch blog posts at this time.</p>';
    }

    $feed_items = $feed->get_items(0, 3); // Get the latest 3 items

    if (empty($feed_items)) {
        return '<p>No recent posts found.</p>';
    }

    $output = '<ul class="latest-blog-posts">';
    foreach ($feed_items as $item) {
        $title = esc_html($item->get_title());
        $link = esc_url($item->get_permalink());
        $date = $item->get_date('F j, Y');

        // Fetch the post ID from the feed URL
        $post_id = url_to_postid($link);

        // Get the author name
        $author = $post_id ? get_the_author_meta('display_name', get_post_field('post_author', $post_id)) : 'Unknown Author';

        // Get the featured image ID
        $featured_image_id = get_post_thumbnail_id($post_id);

        // Get the 300x300 cropped image size
        $featured_image_url = wp_get_attachment_image_url($featured_image_id, 'custom-300x300'); // Custom size

        // Get the excerpt and trim it to 100 characters
        $excerpt = get_the_excerpt($post_id);
        $excerpt = wp_trim_words($excerpt, 20, '...'); // Limit to 20 words (approx. 100 characters)

        // Add a "Continue Reading" link
        $continue_reading_link = "<a href='{$link}' target='_blank' class='continue-reading'>Continue Reading</a>";

        $output .= "<li class='blog-post-item'>";
        $output .= "<div class='blog-post-content'>";

        // Image on the left (33%) - Now as a background
        if ($featured_image_url) {
            $output .= "<div class='post-image' style='background-image: url({$featured_image_url});'></div>";
        }

        // Title, Date, Author, and Excerpt on the right (67%)
        $output .= "<div class='post-details'>";
        $output .= "<a href='{$link}' target='_blank' class='post-title'>{$title}</a>";
        $output .= "<span class='post-date'>{$date} by {$author}</span>";
        $output .= "<p class='excerpt'>{$excerpt} {$continue_reading_link}</p>"; // Display the excerpt with Continue Reading link
        $output .= "</div>"; // End of post-details
        $output .= "</div>"; // End of blog-post-content
        $output .= "</li>";
    }
    $output .= '</ul>';

    return $output;
}

// Register shortcode to display the latest blog posts
function register_blog_feed_shortcode() {
    add_shortcode('latest_blog_posts', 'display_latest_blog_posts');
}
add_action('init', 'register_blog_feed_shortcode');

// Enqueue custom CSS for the custom dashboard
function enqueue_custom_dashboard_styles() {
    wp_enqueue_style('custom-dashboard-css', plugin_dir_url(__FILE__) . 'css/styles.css');
}
add_action('admin_enqueue_scripts', 'enqueue_custom_dashboard_styles');

// Load jQuery and Enqueue Submit Lock
function enqueue_submit_lock_script() {
    // Ensure jQuery is loaded before your script
    if (!wp_script_is('jquery', 'enqueued')) {
        wp_enqueue_script('jquery');
    }

    // Enqueue the submit-lock script
    wp_enqueue_script(
        'submit-lock',
        plugin_dir_url(__FILE__) . 'js/submit-lock.js',
        ['jquery'], // Dependency
        '1.1', // Version
        true // Load in footer
    );
}
add_action('admin_enqueue_scripts', 'enqueue_submit_lock_script');


// Suppress WordPress admin notices and alerts
function hide_admin_notices_on_custom_dashboard() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'toplevel_page_custom_dashboard') {
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
    }
}
add_action('admin_head', 'hide_admin_notices_on_custom_dashboard');

// Auto-update copyright year
function bb_display_current_year($atts, $content = null) {
    return date('Y'); // Returns the current year
}
add_shortcode('bbyear', 'bb_display_current_year');




// Hook into plugin update checks
function bb_media_core_check_for_updates($transient) {
    if (empty($transient->checked)) {
        error_log('No plugins checked in transient.');
        return $transient;
    }

    $plugin_slug = 'bbmediacore'; // Folder name
    $plugin_file = 'bbmediacore/custom-dashboard-plugin.php'; // Full file path
    $plugin_data = get_plugin_data(__FILE__);
    $plugin_version = $plugin_data['Version'];

    error_log('Checking updates for: ' . $plugin_file . ' (current version: ' . $plugin_version . ')');

    // Fetch release data
    $response = wp_remote_get('https://api.github.com/repos/bbmediallc/bbmediacore/releases/latest');

    if (is_wp_error($response)) {
        error_log('Error fetching release data: ' . $response->get_error_message());
        return $transient;
    }

    $release = json_decode(wp_remote_retrieve_body($response), true);

    if (!isset($release['tag_name']) || !isset($release['assets'][0]['browser_download_url'])) {
        error_log('Release data incomplete: ' . print_r($release, true));
        return $transient;
    }

    $latest_version = $release['tag_name'];
    $download_url = $release['assets'][0]['browser_download_url'];

    error_log('Latest version: ' . $latest_version . ', Download URL: ' . $download_url);

    if (version_compare($plugin_version, $latest_version, '<')) {
        $transient->response[$plugin_file] = (object) [
            'slug'        => $plugin_slug,
            'plugin'      => $plugin_file,
            'new_version' => $latest_version,
            'package'     => $download_url,
            'url'         => $release['html_url'],
        ];
        error_log('Update available: ' . print_r($transient->response[$plugin_file], true));
    } else {
        error_log('No update required.');
    }

    return $transient;
}


// Debugging
add_action('admin_init', function () {
    $transient = get_site_transient('update_plugins');
    error_log(print_r($transient, true));
});

add_action('admin_init', function () {
    delete_site_transient('update_plugins'); // Clear the cached plugin updates
    wp_update_plugins(); // Trigger a fresh update check
});
