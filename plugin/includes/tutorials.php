<?php
// Tutorial videos (e.g. Loom links).
//
// Turned on or off per site in Brand Settings. When on, clients get a
// Dashboard > Tutorials page. The list is edited at Dashboard > Tutorial
// Videos, which only the brand admin can see.
//
// Replaces the ACF repeater ("tutorial_links" on an ACF options page) used by
// an earlier one-off version; bbcore_import_acf_tutorials() copies that data
// over, without needing ACF installed.

if (!defined('ABSPATH')) {
    exit;
}

function bbcore_tutorials_enabled() {
    return (bool) get_option('bbcore_tutorials_enabled');
}

// Saved tutorials as a list of array('title' => ..., 'url' => ...).
function bbcore_get_tutorials() {
    $tutorials = get_option('bbcore_tutorials', array());
    return is_array($tutorials) ? array_values($tutorials) : array();
}

function bbcore_tutorials_manager_url() {
    return admin_url('admin.php?page=bbcore-tutorials');
}

function bbcore_add_tutorial_pages() {
    if (bbcore_tutorials_enabled() && bbcore_can_access_dashboard()) {
        $GLOBALS['bbcore_tutorials_hook'] = add_submenu_page(
            'custom_dashboard',
            'How To Videos',
            'Tutorials',
            'read',
            'bb_tutorials',
            'bbcore_render_tutorials_page'
        );
    }
}
// After bbcore_add_dashboard_page(), so the Dashboard menu exists.
add_action('admin_menu', 'bbcore_add_tutorial_pages', 11);

// Listed after Brand Settings (priority 12). Hidden while tutorials are off.
function bbcore_add_tutorials_manager_page() {
    if (bbcore_tutorials_enabled() && bbcore_is_brand_admin()) {
        $GLOBALS['bbcore_tutorials_manager_hook'] = add_submenu_page('custom_dashboard', 'Tutorial Videos', 'Tutorial Videos', 'manage_options', 'bbcore-tutorials', 'bbcore_render_tutorials_manager');
    }
}
add_action('admin_menu', 'bbcore_add_tutorials_manager_page', 13);

// ---------------------------------------------------------------------------
// Client page: Dashboard > Tutorials
// ---------------------------------------------------------------------------

function bbcore_render_tutorials_page() {
    $tutorials = bbcore_get_tutorials();
    ?>
    <div class="wrap">
        <div class="custom-dashboard-layout">
            <div class="custom-dashboard-left">
                <div class="bb-tutorials">
                    <div class="welcome-message">
                        <h2>Tutorials</h2>
                        <p>Explore these step-by-step WordPress tutorials to learn how to update your content, manage menus, and more. Something missing? Let us know via the form and we'll add it.</p>
                    </div>
                    <?php if (empty($tutorials)) : ?>
                        <p>No tutorials have been added yet.</p>
                    <?php else : ?>
                        <ul class="bb-tutorial-list">
                            <?php foreach ($tutorials as $tutorial) : ?>
                                <li class="bb-tutorial-item">
                                    <span class="dashicons dashicons-video-alt3 bb-tutorial-icon" aria-hidden="true"></span>
                                    <a href="<?php echo esc_url($tutorial['url']); ?>" target="_blank" rel="noopener"><?php echo esc_html($tutorial['title']); ?></a>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                </div>
            </div>
            <?php bbcore_render_support_box(); ?>
        </div>
    </div>
    <?php
}

// ---------------------------------------------------------------------------
// Brand admin page: Dashboard > Tutorial Videos
// ---------------------------------------------------------------------------

function bbcore_tutorials_manager_assets($hook) {
    if (empty($GLOBALS['bbcore_tutorials_manager_hook']) || $hook !== $GLOBALS['bbcore_tutorials_manager_hook']) {
        return;
    }
    wp_enqueue_style('bbcore-brand-settings', BBCORE_URL . 'assets/css/brand-settings.css', array(), BBCORE_VERSION);
    wp_enqueue_script('bbcore-tutorials-manager', BBCORE_URL . 'assets/js/tutorials-manager.js', array('jquery', 'jquery-ui-sortable'), BBCORE_VERSION, true);
}
add_action('admin_enqueue_scripts', 'bbcore_tutorials_manager_assets');

function bbcore_render_tutorial_row($index, $tutorial) {
    ?>
    <tr class="bbcore-tutorial-row">
        <td class="bbcore-drag" title="Drag to reorder"><span class="dashicons dashicons-menu" aria-hidden="true"></span></td>
        <td><input type="text" name="tutorials[<?php echo esc_attr($index); ?>][title]" value="<?php echo esc_attr($tutorial['title']); ?>" class="large-text" placeholder="How to edit a page" aria-label="Title"></td>
        <td><input type="url" name="tutorials[<?php echo esc_attr($index); ?>][url]" value="<?php echo esc_attr($tutorial['url']); ?>" class="large-text code" placeholder="https://www.loom.com/share/..." aria-label="Video link"></td>
        <td><button type="button" class="button-link button-link-delete bbcore-remove-tutorial">Remove</button></td>
    </tr>
    <?php
}

function bbcore_render_tutorials_manager() {
    if (!bbcore_is_brand_admin()) {
        return;
    }
    $tutorials = bbcore_get_tutorials();
    ?>
    <div class="wrap bbcore-brand-settings">
        <h1>Tutorial Videos</h1>
        <?php if (isset($_GET['bbcore_msg']) && $_GET['bbcore_msg'] === 'saved') : ?>
            <div class="notice notice-success is-dismissible"><p>Tutorials saved.</p></div>
        <?php endif; ?>

        <?php if (bbcore_tutorials_enabled()) : ?>
            <p>Clients see these under <strong>Dashboard &gt; Tutorials</strong>, in this order. <a href="<?php echo esc_url(bbcore_brand_settings_url()); ?>">Turn tutorials off</a></p>
        <?php else : ?>
            <div class="notice notice-warning inline"><p>Tutorials are turned off, so clients can't see this list. <a href="<?php echo esc_url(bbcore_brand_settings_url()); ?>">Turn them on in Brand Settings</a></p></div>
        <?php endif; ?>

        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('bbcore_save_tutorials'); ?>
            <input type="hidden" name="action" value="bbcore_save_tutorials">
            <div class="bbcore-card">
                <table class="widefat bbcore-tutorials-table">
                    <thead><tr><th class="bbcore-drag"></th><th>Title</th><th>Video link</th><th></th></tr></thead>
                    <tbody>
                    <?php
                    foreach ($tutorials as $index => $tutorial) {
                        bbcore_render_tutorial_row($index, $tutorial);
                    }
                    ?>
                    </tbody>
                </table>
                <p><button type="button" class="button bbcore-add-tutorial">Add tutorial</button></p>
            </div>
            <?php submit_button('Save tutorials'); ?>
        </form>

        <script type="text/html" id="bbcore-tutorial-row-template">
            <?php bbcore_render_tutorial_row('__INDEX__', array('title' => '', 'url' => '')); ?>
        </script>
    </div>
    <?php
}

function bbcore_handle_save_tutorials() {
    if (!bbcore_is_brand_admin()) {
        wp_die('You do not have permission to change tutorials.', 403);
    }
    check_admin_referer('bbcore_save_tutorials');

    $submitted = isset($_POST['tutorials']) && is_array($_POST['tutorials']) ? wp_unslash($_POST['tutorials']) : array();
    update_option('bbcore_tutorials', bbcore_clean_tutorials($submitted));

    wp_safe_redirect(add_query_arg('bbcore_msg', 'saved', bbcore_tutorials_manager_url()));
    exit;
}
add_action('admin_post_bbcore_save_tutorials', 'bbcore_handle_save_tutorials');

// Keeps rows that have a valid http(s) link. A missing title becomes "Tutorial".
function bbcore_clean_tutorials($rows) {
    $clean = array();
    foreach ((array) $rows as $row) {
        if (!is_array($row)) {
            continue;
        }
        $title = isset($row['title']) && is_scalar($row['title']) ? sanitize_text_field((string) $row['title']) : '';
        $url = isset($row['url']) && is_scalar($row['url']) ? esc_url_raw(trim((string) $row['url']), array('http', 'https')) : '';
        if ($url === '') {
            continue;
        }
        $clean[] = array(
            'title' => $title !== '' ? $title : 'Tutorial',
            'url' => $url,
        );
    }
    return $clean;
}

// ---------------------------------------------------------------------------
// One-time import from the old ACF repeater
// ---------------------------------------------------------------------------

// ACF saves an options-page repeater as plain options:
//   options_tutorial_links                  number of rows
//   options_tutorial_links_0_tutorial_title
//   options_tutorial_links_0_tutorial_url
// Runs only while this plugin has no tutorials saved yet. Turns the feature
// on if anything was imported, since that site was already showing them.
function bbcore_import_acf_tutorials() {
    if (get_option('bbcore_tutorials') !== false) {
        return;
    }
    $count = get_option('options_tutorial_links');
    if (!is_numeric($count) || (int) $count < 1) {
        return;
    }

    $rows = array();
    for ($i = 0; $i < (int) $count; $i++) {
        $url = get_option('options_tutorial_links_' . $i . '_tutorial_url');
        // An ACF "Link" field stores array('url' => ..., 'title' => ...).
        if (is_array($url)) {
            $url = isset($url['url']) ? $url['url'] : '';
        }
        $rows[] = array(
            'title' => get_option('options_tutorial_links_' . $i . '_tutorial_title'),
            'url' => $url,
        );
    }

    $rows = bbcore_clean_tutorials($rows);
    if (!empty($rows)) {
        update_option('bbcore_tutorials', $rows);
        update_option('bbcore_tutorials_enabled', 1);
    }
}
