<?php
// Branded login screen.

if (!defined('ABSPATH')) {
    exit;
}

function bbcore_enqueue_login_styles() {
    wp_enqueue_style('bbcore-login', BBCORE_URL . 'assets/css/login.css', array(), BBCORE_VERSION);
    wp_add_inline_style('bbcore-login', bbcore_brand_css_variables());
}
add_action('login_enqueue_scripts', 'bbcore_enqueue_login_styles');

// The login logo links to the brand's site instead of wordpress.org.
function bbcore_login_logo_url() {
    return bbcore_brand('website_url');
}
add_filter('login_headerurl', 'bbcore_login_logo_url');

function bbcore_login_logo_text() {
    return bbcore_brand('name');
}
add_filter('login_headertext', 'bbcore_login_logo_text');
