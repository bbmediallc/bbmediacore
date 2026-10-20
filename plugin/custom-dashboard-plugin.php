<?php
/*
Plugin Name: BB Media Core
Plugin URI: https://github.com/bbmediallc/bbmediacore
Description: A plugin that provides core functionality for B&B Media websites.
Version: 1.6.6
Author: B&B Media, LLC
Author URI: https://bandbmedia.com
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Requires at least: 5.0
Tested up to: 6.7.1
Requires PHP: 5.6
Icon URI: icon.svg
*/

// Admin footer modification

function swp_modify_footer_admin() {
    $plugin_data = get_file_data(__FILE__, ['Version' => 'Version'], 'plugin'); // Get plugin version
    $version = isset($plugin_data['Version']) ? $plugin_data['Version'] : 'Unknown'; // Ensure a fallback

    echo '<span id="footer-thankyou">Powered by <a href="https://bandbmedia.com" target="_blank">B&B Media</a> | Version ' . esc_html($version) . '</span>';
}
add_filter('admin_footer_text', 'swp_modify_footer_admin');

// Check if current user can access the custom dashboard
function bb_can_access_dashboard() {
    if (!is_user_logged_in()) return false;
    $user = wp_get_current_user();
    return !in_array('subscriber', (array) $user->roles);
}

// Remove default dashboard menu item

function remove_default_dashboard_menu() {
    remove_menu_page('index.php');
}
add_action('admin_menu', 'remove_default_dashboard_menu', 999);

// Hide default WordPress dashboard menu for all users
add_action('admin_head', function () {
    echo '<style>#menu-dashboard { display: none !important; }</style>';
});

// Add the custom dashboard and updates page links

function add_custom_dashboard_page() {
    if (!bb_can_access_dashboard()) {
        return;
    }

    // Add main custom dashboard menu (use Dashicon as a placeholder)
    add_menu_page(
        'Dashboard',
        'Dashboard',
        'read',
        'custom_dashboard',
        'custom_dashboard_content',
        plugin_dir_url(__FILE__) . 'icon.svg',
        2
    );

    // Add a submenu link to WordPress Updates (Safe method)
    add_submenu_page(
        'custom_dashboard',
        'WordPress Updates',
        'Updates',
        'manage_options',
        'update-core.php' // Directly link to WP updates page
    );
}
add_action('admin_menu', 'add_custom_dashboard_page');

// Redirect to Custom Dashboard

function redirect_to_custom_dashboard() {
    global $pagenow;

    // Do not redirect during WooCommerce email preview
    if (isset($_GET['preview_woocommerce_mail'])) {
        return;
    }

    // Only redirect when visiting the main Dashboard screen
    if (
        $pagenow === 'index.php' &&
        !isset($_GET['preview_woocommerce_mail']) &&
        !isset($_GET['page']) &&
        is_user_logged_in() &&
        bb_can_access_dashboard()
    ) {
        wp_safe_redirect(admin_url('admin.php?page=custom_dashboard'));
        exit;
    }
}
add_action('admin_init', 'redirect_to_custom_dashboard');

// Custom dashboard page content

function custom_dashboard_content() {
    $current_user = wp_get_current_user();
    $first_name = !empty($current_user->user_firstname) ? $current_user->user_firstname : 'there';

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

// Enhanced Support Form Submission Security
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

    if (!is_email($email_address)) {
        wp_send_json_error(['message' => 'Invalid email format.']);
        return;
    }

    // Attempt to send the email
    $to = 'support@bandbmedia.com';
    $subject = 'New Support Ticket';
    $message = "Name: $full_name\nEmail: $email_address\nUrgency: $urgency\nDescription: $description";
    $headers = [
    	'Content-Type: text/plain; charset=UTF-8',
    	'Reply-To: ' . $email_address
	];

    if (wp_mail($to, $subject, $message, $headers)) {
        wp_send_json_success(['message' => 'Your support ticket has been submitted successfully.']);
    } else {
        wp_send_json_error(['message' => 'Failed to send email. Please try again later.']);
    }
}

// Function to display the latest blog posts with featured images, excerpts, and author names
// Prevent XSS in Blog Feed Shortcode

function display_latest_blog_posts() {
    $cache_key = 'bb_latest_blog_posts';
    $cached_posts = get_transient($cache_key);

    if ($cached_posts) {
        return $cached_posts;
    }

    $response = wp_remote_get('https://bandbmedia.com/wp-json/wp/v2/posts?_embed&per_page=3');

    if (is_wp_error($response)) {
        return '<p>Unable to fetch blog posts at this time. Please try again later.</p>';
    }

    $posts = json_decode(wp_remote_retrieve_body($response));

    if (empty($posts)) {
        return '<p>No recent posts found.</p>';
    }

    $output = '<ul class="latest-blog-posts">';
    foreach ($posts as $post) {
        $title = esc_html($post->title->rendered);
        $link = esc_url($post->link);
        $date = date('F j, Y', strtotime($post->date));
        $author_name = isset($post->_embedded->author[0]->name) ? esc_html($post->_embedded->author[0]->name) : 'The B&B Media Team';
        $featured_image_url = isset($post->_embedded->{'wp:featuredmedia'}[0]->source_url) ? esc_url($post->_embedded->{'wp:featuredmedia'}[0]->source_url) : '';
        $excerpt = wp_kses_post(wp_trim_words($post->excerpt->rendered, 20, '...'));

        $output .= "<li class='blog-post-item'>";
        $output .= $featured_image_url ? "<div class='post-image' style='background-image: url({$featured_image_url});'></div>" : '';
        $output .= "<div class='post-details'><a href='{$link}' target='_blank' class='post-title'>{$title}</a>";
        $output .= "<span class='post-date'>{$date} by {$author_name}</span>";
        $output .= "<p class='excerpt'>{$excerpt} <a href='{$link}' target='_blank' class='continue-reading'>Continue Reading</a></p></div></li>";
    }
    $output .= '</ul>';

    // Cache the result for 12 hours
    set_transient($cache_key, $output, 12 * HOUR_IN_SECONDS);

    return $output;
}

// Register the shortcode to display the latest blog posts

function register_blog_feed_shortcode() {
    add_shortcode('latest_blog_posts', 'display_latest_blog_posts');
}
add_action('init', 'register_blog_feed_shortcode');

// Load Custom Dashboard Assets Only On Custom Dashboard Page

function enqueue_custom_dashboard_assets($hook) {

    if ($hook !== 'toplevel_page_custom_dashboard') {
        return;
    }

    wp_enqueue_style(
        'custom-dashboard-css',
        plugin_dir_url(__FILE__) . 'css/styles.css'
    );

    wp_enqueue_style(
        'bb-admin-styles',
        plugin_dir_url(__FILE__) . 'css/admin-styles.css'
    );

    if (!wp_script_is('jquery', 'enqueued')) {
        wp_enqueue_script('jquery');
    }

    wp_enqueue_script(
        'submit-lock',
        plugin_dir_url(__FILE__) . 'js/submit-lock.js',
        ['jquery'],
        '1.1',
        true
    );
}
add_action('admin_enqueue_scripts', 'enqueue_custom_dashboard_assets');

// Enqueue styles for login page
function bb_enqueue_login_styles() {
    wp_enqueue_style(
        'bb-login-styles',
        plugin_dir_url(__FILE__) . 'css/admin-styles.css'
    );
}
add_action('login_enqueue_scripts', 'bb_enqueue_login_styles');

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
    return date('Y');
}
add_shortcode('bbyear', 'bb_display_current_year');

function bb_media_core_check_for_updates($transient) {
    if (empty($transient->checked)) {
        return $transient;
    }

    $plugin_slug = 'bbmediacore';
    $plugin_file = plugin_basename(__FILE__);
    $plugin_version = '1.6.5'; // Ensure this matches your latest release


    // Use caching to avoid frequent API requests
    $cached_response = get_transient('bb_media_core_update');
    if ($cached_response) {
        return $cached_response;
    }

    $response = wp_remote_get('https://api.github.com/repos/bbmediallc/bbmediacore/releases/latest', [
        'headers' => ['User-Agent' => 'WordPress/' . get_bloginfo('version')],
    ]);

    if (is_wp_error($response)) {
        return $transient;
    }

    $release = json_decode(wp_remote_retrieve_body($response), true);
    if (!isset($release['tag_name'])) {
        return $transient;
    }

    $latest_version = ltrim($release['tag_name'], 'v');

    if (version_compare($plugin_version, $latest_version, '<')) {
        $update = (object) [
            'slug'        => $plugin_slug,
            'plugin'      => $plugin_file,
            'new_version' => $latest_version,
            'package'     => "https://github.com/bbmediallc/bbmediacore/releases/download/{$release['tag_name']}/bbmediacore.zip",
            'url'         => $release['html_url'],
        ];

        $transient->response[$plugin_file] = $update;
    } else {
        // If versions are equal, ensure no update is listed
        unset($transient->response[$plugin_file]);
    }

    // Store update check result in cache for 6 hours
    set_site_transient('update_plugins', $transient, 6 * HOUR_IN_SECONDS);

    return $transient;
}
add_filter('site_transient_update_plugins', 'bb_media_core_check_for_updates', 99);

//Clear the Update Transients

function bb_media_core_clear_update_transients($upgrader_object, $options) {
    if ($options['action'] === 'update' && $options['type'] === 'plugin') {
        delete_site_transient('update_plugins'); // Forces WordPress to refresh plugin updates
        delete_transient('bb_media_core_update'); // Clears custom update transient
    }
}
add_action('upgrader_process_complete', 'bb_media_core_clear_update_transients', 10, 2);

function bb_media_core_force_plugin_reactivation($upgrader_object, $options) {
    if ($options['action'] === 'update' && $options['type'] === 'plugin') {
        update_option('bb_media_core_reactivate_plugin', true);
    }
}
add_action('upgrader_process_complete', 'bb_media_core_force_plugin_reactivation', 10, 2);

function bb_media_core_maybe_reactivate_plugin() {
    if (get_option('bb_media_core_reactivate_plugin')) {
        delete_option('bb_media_core_reactivate_plugin');
        activate_plugin(plugin_basename(__FILE__));
    }
}
add_action('admin_init', 'bb_media_core_maybe_reactivate_plugin');

// Keep menu icon full color in all states
function bb_menu_icon_styles() {
    wp_add_inline_style('wp-admin', '
        #toplevel_page_custom_dashboard .wp-menu-image img {
            opacity: 1 !important;
            width: 20px !important;
            padding: 6px 0 0 !important;
        }
    ');
}
add_action('admin_enqueue_scripts', 'bb_menu_icon_styles');