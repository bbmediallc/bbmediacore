<?php
// Custom dashboard that replaces the default WordPress dashboard.

if (!defined('ABSPATH')) {
    exit;
}

// Remove the default dashboard menu item.
function bbcore_remove_default_dashboard_menu() {
    remove_menu_page('index.php');
}
add_action('admin_menu', 'bbcore_remove_default_dashboard_menu', 999);

// Hide the default dashboard menu for all users.
function bbcore_hide_default_dashboard_menu() {
    echo '<style>#menu-dashboard { display: none !important; }</style>';
}
add_action('admin_head', 'bbcore_hide_default_dashboard_menu');

// Add the custom dashboard and its Updates link.
function bbcore_add_dashboard_page() {
    if (!bbcore_can_access_dashboard()) {
        return;
    }

    add_menu_page(
        'Dashboard',
        'Dashboard',
        'read',
        'custom_dashboard',
        'bbcore_render_dashboard',
        bbcore_brand_image_url('menu_icon'),
        2
    );

    add_submenu_page(
        'custom_dashboard',
        'WordPress Updates',
        'Updates',
        'manage_options',
        'update-core.php'
    );
}
add_action('admin_menu', 'bbcore_add_dashboard_page');

// Send the default dashboard screen to the custom one.
function bbcore_redirect_to_dashboard() {
    global $pagenow;

    // Do not redirect during WooCommerce email preview.
    if (isset($_GET['preview_woocommerce_mail'])) {
        return;
    }

    if ($pagenow === 'index.php' && !isset($_GET['page']) && bbcore_can_access_dashboard()) {
        wp_safe_redirect(admin_url('admin.php?page=custom_dashboard'));
        exit;
    }
}
add_action('admin_init', 'bbcore_redirect_to_dashboard');

function bbcore_render_dashboard() {
    $current_user = wp_get_current_user();
    $first_name = !empty($current_user->user_firstname) ? $current_user->user_firstname : 'there';
    $logo = bbcore_brand_image_url('logo');
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
                            <?php if ($logo) : ?>
                                <div style="text-align: right; margin-bottom: 10px;">
                                    <img src="<?php echo esc_url($logo); ?>" alt="<?php echo esc_attr(bbcore_brand('name') . ' Logo'); ?>" style="width: 150px; max-width: 150px; height: auto;">
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php bbcore_render_support_form(); ?>
                </div>
            </div>
        </div>
    </div>
    <?php
}

// Hide admin notices on the custom dashboard.
function bbcore_hide_dashboard_notices() {
    $screen = get_current_screen();
    if ($screen && $screen->id === 'toplevel_page_custom_dashboard') {
        remove_all_actions('admin_notices');
        remove_all_actions('all_admin_notices');
    }
}
add_action('admin_head', 'bbcore_hide_dashboard_notices');

// The active brand's colors as CSS variables, used by dashboard.css and login.css.
function bbcore_brand_css_variables() {
    $brand = bbcore_get_brand();
    $map = array(
        '--bbcore-primary' => 'color_primary',
        '--bbcore-link' => 'color_link',
        '--bbcore-login-border' => 'login_border',
        '--bbcore-login-shadow' => 'login_shadow',
        '--bbcore-login-gradient-start' => 'login_gradient_start',
        '--bbcore-login-gradient-end' => 'login_gradient_end',
        '--bbcore-login-button' => 'login_button',
        '--bbcore-login-button-hover' => 'login_button_hover',
        '--bbcore-login-button-alt' => 'login_button_alt',
        '--bbcore-login-button-alt-hover' => 'login_button_alt_hover',
    );
    $css = '';
    foreach ($map as $var => $field) {
        // Values were checked as hex colors when saved; check again since
        // they end up inside a style tag.
        if (preg_match('/^#[0-9a-fA-F]{3,8}$/', $brand[$field])) {
            $css .= $var . ':' . $brand[$field] . ';';
        }
    }
    foreach (array('logo' => '--bbcore-logo', 'login_background' => '--bbcore-login-background') as $field => $var) {
        $url = bbcore_brand_image_url($field, $brand);
        if ($url) {
            // esc_url_raw strips quotes and angle brackets, so the URL
            // can't break out of url("...").
            $css .= $var . ':url("' . esc_url_raw($url) . '");';
        }
    }
    return ':root{' . $css . '}';
}

// Load dashboard assets only on the custom dashboard page.
function bbcore_enqueue_dashboard_assets($hook) {
    if ($hook !== 'toplevel_page_custom_dashboard') {
        return;
    }

    wp_enqueue_style('bbcore-dashboard', BBCORE_URL . 'assets/css/dashboard.css', array(), BBCORE_VERSION);
    wp_add_inline_style('bbcore-dashboard', bbcore_brand_css_variables());

    wp_enqueue_script('bbcore-support-form', BBCORE_URL . 'assets/js/support-form.js', array('jquery'), BBCORE_VERSION, true);
}
add_action('admin_enqueue_scripts', 'bbcore_enqueue_dashboard_assets');

// Keep the menu icon full color in all states.
function bbcore_menu_icon_styles() {
    wp_add_inline_style('wp-admin', '
        #toplevel_page_custom_dashboard .wp-menu-image img {
            opacity: 1 !important;
            width: 20px !important;
            padding: 6px 0 0 !important;
        }
    ');
}
add_action('admin_enqueue_scripts', 'bbcore_menu_icon_styles');

// [bbyear] shortcode: the current year, for copyright lines.
function bbcore_current_year_shortcode() {
    return date('Y');
}
add_shortcode('bbyear', 'bbcore_current_year_shortcode');
