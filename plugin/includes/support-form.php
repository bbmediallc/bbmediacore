<?php
// Support ticket form on the custom dashboard.

if (!defined('ABSPATH')) {
    exit;
}

function bbcore_render_support_form() {
    ?>
    <form id="support-form" method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>">
        <?php wp_nonce_field('support_form_action', 'support_form_nonce'); ?>
        <input type="hidden" name="action" value="bbcore_support_ticket">

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

// Logged-in users only. The form is only shown in wp-admin, and accepting
// logged-out submissions let anyone send mail to the support inboxes.
function bbcore_handle_support_ticket() {
    if (!isset($_POST['support_form_nonce']) || !wp_verify_nonce($_POST['support_form_nonce'], 'support_form_action')) {
        wp_send_json_error(array('message' => 'Security check failed.'));
    }
    // Same people who can see the form (not subscribers or WooCommerce customers).
    if (!bbcore_can_access_dashboard()) {
        wp_send_json_error(array('message' => 'You do not have permission to submit support tickets.'));
    }

    $full_name = isset($_POST['full_name']) ? sanitize_text_field(wp_unslash($_POST['full_name'])) : '';
    $email_address = isset($_POST['email_address']) ? sanitize_email(wp_unslash($_POST['email_address'])) : '';
    $urgency = isset($_POST['urgency']) ? intval($_POST['urgency']) : 3;
    $description = isset($_POST['description']) ? sanitize_textarea_field(wp_unslash($_POST['description'])) : '';

    if (!is_email($email_address)) {
        wp_send_json_error(array('message' => 'Invalid email format.'));
    }

    $to = bbcore_brand_emails();
    if (empty($to)) {
        $to = array('support@bandbmedia.com');
    }
    $subject = 'New Support Ticket';
    $message = "Name: $full_name\nEmail: $email_address\nUrgency: $urgency\nDescription: $description\n\nSite: " . home_url();
    $headers = array(
        'Content-Type: text/plain; charset=UTF-8',
        'Reply-To: ' . $email_address,
    );

    if (wp_mail($to, $subject, $message, $headers)) {
        wp_send_json_success(array('message' => 'Your support ticket has been submitted successfully.'));
    }
    wp_send_json_error(array('message' => 'Failed to send email. Please try again later.'));
}
add_action('wp_ajax_bbcore_support_ticket', 'bbcore_handle_support_ticket');
