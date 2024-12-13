<?php
/*
Plugin Name: BB Media Core
Plugin URI: https://bandbmedia.com
Description: A plugin that provides core functionality for B&B Media websites.
Version: 1.1
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
            background-color: #f4f4f4;
        }
    </style>
    <?php
}
add_action('login_enqueue_scripts', 'custom_login_logo');

// Force redirection from default dashboard to the custom dashboard
function redirect_to_custom_dashboard() {
    // Ensure the user is logged in and has access to the admin area
    if (is_user_logged_in() && is_admin()) {
        // Redirect from the default dashboard to the custom dashboard
        wp_redirect(admin_url('admin.php?page=custom_dashboard'));
        exit;
    }
}
add_action('load-index.php', 'redirect_to_custom_dashboard');

// Remove default dashboard menu item
function remove_default_dashboard_menu() {
    remove_menu_page('index.php'); // Remove default dashboard
}
add_action('admin_menu', 'remove_default_dashboard_menu', 999);

// Add custom dashboard page
function add_custom_dashboard_page() {
    add_menu_page(
        'Dashboard', // Page title
        'Dashboard',        // Menu title
        'read',             // Required capability
        'custom_dashboard', // Page slug (used for redirect)
        'custom_dashboard_content', // Callback function to display the content
        'dashicons-admin-home', // Icon for the menu item
        2 // Position in the menu
    );
}
add_action('admin_menu', 'add_custom_dashboard_page');

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

// Add GitHub Updater

// Hook into plugin update checks
add_filter('pre_set_site_transient_update_plugins', 'bb_media_core_check_for_updates');
add_filter('plugins_api', 'bb_media_core_plugin_info', 10, 3);

function bb_media_core_check_for_updates($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    $plugin_slug = 'bbmediacore'; // Your plugin folder name
    $plugin_data = get_plugin_data(__FILE__);
    $plugin_version = $plugin_data['Version'];

    // GitHub API URL for your plugin's release
    $remote_url = 'https://api.github.com/repos/bbmediallc/your-repo/releases/latest';

    $response = wp_remote_get($remote_url);

    if (is_wp_error($response)) {
        return $transient;
    }

    $remote = json_decode(wp_remote_retrieve_body($response));

    if (isset($remote->tag_name) && version_compare($plugin_version, $remote->tag_name, '<')) {
        $plugin = plugin_basename(__FILE__);
        $transient->response[$plugin] = (object) [
            'slug'        => $plugin_slug,
            'plugin'      => $plugin,
            'new_version' => $remote->tag_name,
            'package'     => $remote->assets[0]->browser_download_url,
            'url'         => $remote->html_url,
        ];
    }

    return $transient;
}

function bb_media_core_plugin_info($result, $action, $args) {
    if ($action !== 'plugin_information') {
        return $result;
    }

    if ($args->slug !== 'bb-media-core') {
        return $result;
    }

    $remote_url = 'https://api.github.com/repos/yourusername/your-repo/releases/latest';

    $response = wp_remote_get($remote_url);

    if (is_wp_error($response)) {
        return $result;
    }

    $remote = json_decode(wp_remote_retrieve_body($response));

    $result = (object) [
        'name'          => 'BB Media Core',
        'slug'          => 'bb-media-core',
        'version'       => $remote->tag_name,
        'author'        => '<a href="https://bandbmedia.com">B&B Media</a>',
        'homepage'      => $remote->html_url,
        'download_link' => $remote->assets[0]->browser_download_url,
        'sections'      => [
            'description' => 'BB Media Core is a plugin that enhances WordPress admin functionality for B&B Media websites.',
        ],
    ];

    return $result;
}
