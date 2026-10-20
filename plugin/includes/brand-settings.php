<?php
// Dashboard > Brand Settings. Visible only to bbcore_is_brand_admin().

if (!defined('ABSPATH')) {
    exit;
}

function bbcore_add_brand_settings_page() {
    if (!bbcore_is_brand_admin()) {
        return;
    }
    $GLOBALS['bbcore_brand_settings_hook'] = add_submenu_page('custom_dashboard', 'Brand Settings', 'Brand Settings', 'manage_options', 'bbcore-brand-settings', 'bbcore_render_brand_settings');
}
// After the Dashboard menu (priority 10) and the client Tutorials page (11).
add_action('admin_menu', 'bbcore_add_brand_settings_page', 12);

function bbcore_brand_settings_assets($hook) {
    if (empty($GLOBALS['bbcore_brand_settings_hook']) || $hook !== $GLOBALS['bbcore_brand_settings_hook']) {
        return;
    }
    wp_enqueue_media();
    wp_enqueue_style('bbcore-brand-settings', BBCORE_URL . 'assets/css/brand-settings.css', array(), BBCORE_VERSION);
    wp_enqueue_script('bbcore-brand-settings', BBCORE_URL . 'assets/js/brand-settings.js', array('jquery'), BBCORE_VERSION, true);
}
add_action('admin_enqueue_scripts', 'bbcore_brand_settings_assets');

// "Brand Settings" link on this plugin's row in the Plugins list.
function bbcore_plugin_action_links($links) {
    if (bbcore_is_brand_admin()) {
        array_unshift($links, '<a href="' . esc_url(bbcore_brand_settings_url()) . '">Brand Settings</a>');
    }
    return $links;
}
add_filter('plugin_action_links_' . BBCORE_BASENAME, 'bbcore_plugin_action_links');

// ---------------------------------------------------------------------------
// Form handlers (admin-post.php)
// ---------------------------------------------------------------------------

function bbcore_verify_brand_request($action) {
    if (!bbcore_is_brand_admin()) {
        wp_die('You do not have permission to change brand settings.', 403);
    }
    check_admin_referer($action);
}

function bbcore_brand_settings_redirect($args = array()) {
    wp_safe_redirect(add_query_arg($args, bbcore_brand_settings_url()));
    exit;
}

function bbcore_handle_set_active_brand() {
    bbcore_verify_brand_request('bbcore_set_active_brand');
    $slug = isset($_POST['brand']) ? sanitize_key(wp_unslash($_POST['brand'])) : '';
    if (!bbcore_brand_exists($slug)) {
        bbcore_brand_settings_redirect(array('bbcore_msg' => 'unknown'));
    }
    update_option('bbcore_active_brand', $slug);
    delete_option('bbcore_needs_setup');
    delete_option('bbcore_suggested_brand');
    bbcore_brand_settings_redirect(array('bbcore_msg' => 'activated'));
}
add_action('admin_post_bbcore_set_active_brand', 'bbcore_handle_set_active_brand');

function bbcore_handle_save_brand() {
    bbcore_verify_brand_request('bbcore_save_brand');
    $is_new = !empty($_POST['is_new']);
    $values = bbcore_sanitize_brand_values(isset($_POST['brand_fields']) ? (array) $_POST['brand_fields'] : array());

    if ($is_new) {
        $requested = isset($_POST['slug']) ? wp_unslash($_POST['slug']) : '';
        $slug = sanitize_key($requested !== '' ? $requested : $values['name']);
        if ($slug === '' || bbcore_brand_exists($slug)) {
            bbcore_brand_settings_redirect(array('bbcore_msg' => 'slug_taken', 'edit' => 'new'));
        }
    } else {
        $slug = isset($_POST['slug']) ? sanitize_key(wp_unslash($_POST['slug'])) : '';
        if (!bbcore_brand_exists($slug)) {
            bbcore_brand_settings_redirect(array('bbcore_msg' => 'unknown'));
        }
    }

    if ($values['name'] === '') {
        bbcore_brand_settings_redirect(array('bbcore_msg' => 'name_required', 'edit' => $is_new ? 'new' : $slug));
    }

    bbcore_save_brand($slug, $values);
    bbcore_brand_settings_redirect(array('bbcore_msg' => 'saved', 'edit' => $slug));
}
add_action('admin_post_bbcore_save_brand', 'bbcore_handle_save_brand');

// Deletes a brand added on this site, or resets a built-in brand.
function bbcore_handle_delete_brand() {
    bbcore_verify_brand_request('bbcore_delete_brand');
    $slug = isset($_POST['slug']) ? sanitize_key(wp_unslash($_POST['slug'])) : '';
    $brands = bbcore_get_brands();
    if (!isset($brands[$slug])) {
        bbcore_brand_settings_redirect(array('bbcore_msg' => 'unknown'));
    }
    if (!$brands[$slug]['_builtin'] && $slug === bbcore_active_brand_slug()) {
        bbcore_brand_settings_redirect(array('bbcore_msg' => 'delete_active'));
    }
    bbcore_delete_saved_brand($slug);
    bbcore_brand_settings_redirect(array('bbcore_msg' => $brands[$slug]['_builtin'] ? 'reset' : 'deleted'));
}
add_action('admin_post_bbcore_delete_brand', 'bbcore_handle_delete_brand');

// Per-site features (not tied to a brand).
function bbcore_handle_save_features() {
    bbcore_verify_brand_request('bbcore_save_features');
    update_option('bbcore_tutorials_enabled', empty($_POST['tutorials_enabled']) ? 0 : 1);
    bbcore_brand_settings_redirect(array('bbcore_msg' => 'features_saved'));
}
add_action('admin_post_bbcore_save_features', 'bbcore_handle_save_features');

// ---------------------------------------------------------------------------
// Page
// ---------------------------------------------------------------------------

function bbcore_brand_settings_messages() {
    return array(
        'activated' => array('success', 'Active company saved.'),
        'saved' => array('success', 'Company saved.'),
        'deleted' => array('success', 'Company deleted.'),
        'reset' => array('success', 'Company reset to its built-in settings.'),
        'features_saved' => array('success', 'Features saved.'),
        'unknown' => array('error', 'That company does not exist.'),
        'slug_taken' => array('error', 'That ID is empty or already used. Choose another.'),
        'name_required' => array('error', 'Company name is required.'),
        'delete_active' => array('error', 'You cannot delete the active company. Switch to another one first.'),
    );
}

function bbcore_render_brand_settings() {
    if (!bbcore_is_brand_admin()) {
        return;
    }
    $messages = bbcore_brand_settings_messages();
    $msg = isset($_GET['bbcore_msg']) ? sanitize_key($_GET['bbcore_msg']) : '';
    $edit = isset($_GET['edit']) ? sanitize_key($_GET['edit']) : '';

    echo '<div class="wrap bbcore-brand-settings"><h1>Brand Settings</h1>';
    if (isset($messages[$msg])) {
        printf('<div class="notice notice-%s is-dismissible"><p>%s</p></div>', esc_attr($messages[$msg][0]), esc_html($messages[$msg][1]));
    }

    if ($edit !== '') {
        bbcore_render_brand_editor($edit);
    } else {
        bbcore_render_active_brand_picker();
        if (!bbcore_needs_setup()) {
            bbcore_render_features();
        }
        bbcore_render_brand_list();
    }
    echo '</div>';
}

function bbcore_render_active_brand_picker() {
    $active = bbcore_active_brand_slug();
    $setup = bbcore_needs_setup();
    ?>
    <div class="bbcore-card<?php echo $setup ? ' bbcore-setup' : ''; ?>">
        <h2><?php echo $setup ? 'Which company is this site for?' : 'Active company'; ?></h2>
        <p><?php echo $setup
            ? 'This sets the plugin name, logo, colors, login page, support email and blog feed for this site. You can change it here later.'
            : 'Everything this site shows comes from the company selected here.'; ?></p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('bbcore_set_active_brand'); ?>
            <input type="hidden" name="action" value="bbcore_set_active_brand">
            <div class="bbcore-brand-tiles">
                <?php foreach (bbcore_get_brands() as $slug => $brand) : ?>
                    <label class="bbcore-brand-tile">
                        <input type="radio" name="brand" value="<?php echo esc_attr($slug); ?>" <?php checked($slug, $active); ?>>
                        <span class="bbcore-brand-tile-logo"><?php bbcore_render_logo_preview($brand); ?></span>
                        <span class="bbcore-brand-tile-name"><?php echo esc_html($brand['name']); ?></span>
                    </label>
                <?php endforeach; ?>
            </div>
            <?php submit_button($setup ? 'Use this company' : 'Save active company'); ?>
        </form>
    </div>
    <?php
}

function bbcore_render_features() {
    ?>
    <div class="bbcore-card">
        <h2>Features</h2>
        <p>These apply to this site only, whichever company is active.</p>
        <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
            <?php wp_nonce_field('bbcore_save_features'); ?>
            <input type="hidden" name="action" value="bbcore_save_features">
            <table class="form-table" role="presentation"><tr>
                <th scope="row">Tutorials</th>
                <td>
                    <label><input type="checkbox" name="tutorials_enabled" value="1" <?php checked(bbcore_tutorials_enabled()); ?>> Enable tutorials</label>
                    <p class="description">Adds a Tutorials page under Dashboard for clients. <a href="<?php echo esc_url(bbcore_tutorials_manager_url()); ?>">Manage tutorial videos</a> (<?php echo (int) count(bbcore_get_tutorials()); ?> added)</p>
                </td>
            </tr></table>
            <?php submit_button('Save features', 'secondary'); ?>
        </form>
    </div>
    <?php
}

function bbcore_render_logo_preview($brand) {
    $logo = bbcore_brand_image_url('logo', $brand);
    if ($logo) {
        printf('<img src="%s" alt="">', esc_url($logo));
    }
}

function bbcore_render_brand_list() {
    $active = bbcore_active_brand_slug();
    ?>
    <div class="bbcore-card">
        <h2>Companies</h2>
        <p>Built-in companies ship with every plugin update. Changes you save here apply to this site only.</p>
        <table class="widefat striped">
            <thead><tr><th>Company</th><th>Plugin name</th><th>Type</th><th></th></tr></thead>
            <tbody>
            <?php foreach (bbcore_get_brands() as $slug => $brand) : ?>
                <tr>
                    <td><strong><?php echo esc_html($brand['name']); ?></strong><?php echo $slug === $active ? ' <span class="bbcore-pill">Active</span>' : ''; ?><br><code><?php echo esc_html($slug); ?></code></td>
                    <td><?php echo esc_html($brand['plugin_name']); ?></td>
                    <td><?php
                        if (!$brand['_builtin']) {
                            echo 'Added on this site';
                        } elseif ($brand['_customized']) {
                            echo 'Built-in, edited on this site';
                        } else {
                            echo 'Built-in';
                        }
                    ?></td>
                    <td class="bbcore-actions">
                        <a class="button" href="<?php echo esc_url(add_query_arg('edit', $slug, bbcore_brand_settings_url())); ?>">Edit</a>
                        <?php if ($brand['_customized'] || (!$brand['_builtin'] && $slug !== $active)) : ?>
                            <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" data-confirm="<?php echo esc_attr($brand['_builtin'] ? 'Reset this company to its built-in settings?' : 'Delete this company?'); ?>">
                                <?php wp_nonce_field('bbcore_delete_brand'); ?>
                                <input type="hidden" name="action" value="bbcore_delete_brand">
                                <input type="hidden" name="slug" value="<?php echo esc_attr($slug); ?>">
                                <button class="button button-link-delete"><?php echo $brand['_builtin'] ? 'Reset' : 'Delete'; ?></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <p><a class="button button-primary" href="<?php echo esc_url(add_query_arg('edit', 'new', bbcore_brand_settings_url())); ?>">Add a company</a></p>
    </div>
    <?php
}

function bbcore_render_brand_editor($slug) {
    $is_new = $slug === 'new';
    if ($is_new) {
        // New companies start as a copy of B&B Media's settings.
        $brand = bbcore_get_brand(BBCORE_DEFAULT_BRAND);
        $brand['name'] = '';
        $brand['plugin_name'] = '';
        $brand['plugin_description'] = '';
        foreach (array('logo', 'menu_icon') as $image) {
            $brand[$image] = bbcore_brand_image_url($image, $brand);
        }
    } else {
        if (!bbcore_brand_exists($slug)) {
            echo '<p>That company does not exist.</p>';
            return;
        }
        $brand = bbcore_get_brand($slug);
    }
    $fields = bbcore_brand_fields();
    ?>
    <p><a href="<?php echo esc_url(bbcore_brand_settings_url()); ?>">&larr; All companies</a></p>
    <h2><?php echo $is_new ? 'Add a company' : 'Edit ' . esc_html($brand['name']); ?></h2>

    <form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>" class="bbcore-brand-editor">
        <?php wp_nonce_field('bbcore_save_brand'); ?>
        <input type="hidden" name="action" value="bbcore_save_brand">
        <?php if ($is_new) : ?>
            <input type="hidden" name="is_new" value="1">
            <div class="bbcore-card">
                <table class="form-table" role="presentation"><tr>
                    <th scope="row"><label for="bbcore-slug">ID</label></th>
                    <td><input type="text" id="bbcore-slug" name="slug" class="regular-text">
                    <p class="description">Short lowercase name with no spaces, e.g. <code>acme</code>. Leave blank to make one from the company name. It can't be changed later.</p></td>
                </tr></table>
            </div>
        <?php else : ?>
            <input type="hidden" name="slug" value="<?php echo esc_attr($slug); ?>">
        <?php endif; ?>

        <?php foreach (bbcore_brand_sections() as $section => $title) : ?>
            <div class="bbcore-card">
                <h2><?php echo esc_html($title); ?></h2>
                <table class="form-table" role="presentation">
                <?php foreach ($fields as $key => $field) :
                    if ($field['section'] !== $section) {
                        continue;
                    }
                    $id = 'bbcore-field-' . $key;
                    $name = 'brand_fields[' . $key . ']';
                    $value = $brand[$key];
                    ?>
                    <tr>
                        <th scope="row"><label for="<?php echo esc_attr($id); ?>"><?php echo esc_html($field['label']); ?></label></th>
                        <td>
                        <?php if ($field['type'] === 'textarea') : ?>
                            <textarea id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" rows="2" class="large-text"><?php echo esc_textarea($value); ?></textarea>
                        <?php elseif ($field['type'] === 'color') : ?>
                            <span class="bbcore-color">
                                <input type="color" value="<?php echo esc_attr(substr($value, 0, 7)); ?>" aria-label="Pick <?php echo esc_attr($field['label']); ?>">
                                <input type="text" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" class="code" size="10" placeholder="#000000">
                            </span>
                        <?php elseif ($field['type'] === 'image') : ?>
                            <span class="bbcore-image">
                                <span class="bbcore-image-preview"><?php
                                    $preview = bbcore_brand_image_url($key, $brand);
                                    if ($preview) {
                                        printf('<img src="%s" alt="">', esc_url($preview));
                                    }
                                ?></span>
                                <input type="text" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" class="regular-text">
                                <button type="button" class="button bbcore-choose-image">Choose image</button>
                            </span>
                        <?php else : ?>
                            <input type="<?php echo $field['type'] === 'url' ? 'url' : 'text'; ?>" id="<?php echo esc_attr($id); ?>" name="<?php echo esc_attr($name); ?>" value="<?php echo esc_attr($value); ?>" class="regular-text">
                        <?php endif; ?>
                        <?php if (!empty($field['help'])) : ?>
                            <p class="description"><?php echo esc_html($field['help']); ?></p>
                        <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </table>
            </div>
        <?php endforeach; ?>

        <?php submit_button($is_new ? 'Add company' : 'Save company'); ?>
    </form>

    <?php if (!$is_new) : ?>
        <div class="bbcore-card">
            <h2>Use this company on every site</h2>
            <p>Changes saved here only apply to this site. To ship this company with the plugin, save this text as <code>brands/<?php echo esc_html($slug); ?>/brand.json</code> in the codebase, put its logo and icon files in the same folder, and release an update.</p>
            <textarea class="large-text code" rows="12" readonly onclick="this.select()"><?php echo esc_textarea(bbcore_brand_json($brand)); ?></textarea>
        </div>
    <?php endif;
}
