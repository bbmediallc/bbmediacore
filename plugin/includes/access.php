<?php
// Who can see what.

if (!defined('ABSPATH')) {
    exit;
}

// Lowercased list of the emails in BBCORE_ADMIN_EMAILS.
function bbcore_admin_emails() {
    $emails = array();
    foreach (explode(',', BBCORE_ADMIN_EMAILS) as $email) {
        $email = strtolower(trim($email));
        if ($email !== '') {
            $emails[] = $email;
        }
    }
    return $emails;
}

// True only for an administrator whose account email is in BBCORE_ADMIN_EMAILS.
// This keeps brand controls out of sight of clients. It is not a hard security
// boundary: any administrator can already install plugins or edit code.
function bbcore_is_brand_admin() {
    if (!is_user_logged_in() || !current_user_can('manage_options')) {
        return false;
    }
    $user = wp_get_current_user();
    return in_array(strtolower($user->user_email), bbcore_admin_emails(), true);
}

// Everyone logged in except subscribers and WooCommerce customers gets the
// custom dashboard and support form.
function bbcore_can_access_dashboard() {
    if (!is_user_logged_in()) {
        return false;
    }
    $user = wp_get_current_user();
    return !array_intersect(array('subscriber', 'customer'), (array) $user->roles);
}
