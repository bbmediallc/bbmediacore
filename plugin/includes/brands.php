<?php
// Brand registry.
//
// Built-in brands live in brands/<slug>/brand.php and ship with every
// release. Edits made in Brand Settings are saved per site in the
// bbcore_brands option: for a built-in brand only the changed fields are
// stored, so "Reset to defaults" just deletes them. Brands added in
// Brand Settings exist only on that site until their exported settings
// are committed as a new brands/<slug>/brand.php. (PHP rather than JSON so
// visitors who open the file in a browser see nothing.)

if (!defined('ABSPATH')) {
    exit;
}

define('BBCORE_DEFAULT_BRAND', 'bbmedia');

// Every setting a brand has, in the order Brand Settings shows them.
function bbcore_brand_fields() {
    return array(
        'name' => array('section' => 'general', 'type' => 'text', 'label' => 'Company name', 'help' => 'Used in the admin footer and the logo alt text.'),
        'plugin_name' => array('section' => 'general', 'type' => 'text', 'label' => 'Plugin name', 'help' => 'What the Plugins page calls this plugin.'),
        'plugin_description' => array('section' => 'general', 'type' => 'textarea', 'label' => 'Plugin description', 'help' => 'Shown under the name on the Plugins page.'),
        'website_url' => array('section' => 'general', 'type' => 'url', 'label' => 'Company website', 'help' => 'The "Powered by" link in the admin footer.'),

        'support_emails' => array('section' => 'support', 'type' => 'emails', 'label' => 'Support ticket recipients', 'help' => 'Comma-separated. Every address gets each ticket.'),
        'blog_url' => array('section' => 'support', 'type' => 'url', 'label' => 'Blog source site', 'help' => 'The dashboard shows the 3 latest posts from this WordPress site.'),
        'blog_author_fallback' => array('section' => 'support', 'type' => 'text', 'label' => 'Fallback author name', 'help' => 'Shown when a post has no public author.'),

        'logo' => array('section' => 'images', 'type' => 'image', 'label' => 'Logo', 'help' => 'Dashboard support box and login page.'),
        'menu_icon' => array('section' => 'images', 'type' => 'image', 'label' => 'Menu icon', 'help' => 'The Dashboard icon in the admin sidebar. A square SVG works best.'),
        'login_background' => array('section' => 'images', 'type' => 'image', 'label' => 'Login background', 'help' => 'Leave blank to use the default photo.'),

        'color_primary' => array('section' => 'dashboard_colors', 'type' => 'color', 'label' => 'Primary', 'help' => 'Card borders and the support form button.'),
        'color_link' => array('section' => 'dashboard_colors', 'type' => 'color', 'label' => 'Links', 'help' => 'Blog post links.'),
        'color_accent' => array('section' => 'dashboard_colors', 'type' => 'color', 'label' => 'Accent', 'help' => 'Tutorial video icons.'),

        'login_border' => array('section' => 'login_colors', 'type' => 'color', 'label' => 'Login box top border'),
        'login_shadow' => array('section' => 'login_colors', 'type' => 'color', 'label' => 'Login box glow', 'help' => '8-digit hex allows transparency, e.g. #cc3e006b.'),
        'login_gradient_start' => array('section' => 'login_colors', 'type' => 'color', 'label' => 'Background overlay start'),
        'login_gradient_end' => array('section' => 'login_colors', 'type' => 'color', 'label' => 'Background overlay end'),
        'login_button' => array('section' => 'login_colors', 'type' => 'color', 'label' => 'Log In button'),
        'login_button_hover' => array('section' => 'login_colors', 'type' => 'color', 'label' => 'Log In button (hover)'),
        'login_button_alt' => array('section' => 'login_colors', 'type' => 'color', 'label' => 'Other login buttons', 'help' => 'Generate Password, two-factor resend, etc.'),
        'login_button_alt_hover' => array('section' => 'login_colors', 'type' => 'color', 'label' => 'Other login buttons (hover)'),
    );
}

function bbcore_brand_sections() {
    return array(
        'general' => 'General',
        'support' => 'Support & blog',
        'images' => 'Images',
        'dashboard_colors' => 'Dashboard colors',
        'login_colors' => 'Login page colors',
    );
}

// Brands bundled with the plugin, keyed by folder name.
function bbcore_builtin_brands() {
    static $brands = null;
    if ($brands !== null) {
        return $brands;
    }
    $brands = array();
    $files = glob(BBCORE_DIR . 'brands/*/brand.php');
    foreach ($files ? $files : array() as $file) {
        $data = include $file;
        if (is_array($data)) {
            $brands[basename(dirname($file))] = $data;
        }
    }
    return $brands;
}

function bbcore_saved_brands() {
    $saved = get_option('bbcore_brands', array());
    return is_array($saved) ? $saved : array();
}

// All brands with their final values. Each also carries:
// _slug, _builtin (ships with the plugin), _customized (has site edits).
function bbcore_get_brands() {
    $builtin = bbcore_builtin_brands();
    $saved = bbcore_saved_brands();
    $blank = array_fill_keys(array_keys(bbcore_brand_fields()), '');
    $brands = array();

    foreach ($builtin as $slug => $values) {
        $edits = isset($saved[$slug]) ? (array) $saved[$slug] : array();
        $brands[$slug] = array_merge($blank, $values, $edits, array(
            '_slug' => $slug,
            '_builtin' => true,
            '_customized' => !empty($edits),
        ));
    }
    foreach ($saved as $slug => $values) {
        if (!isset($brands[$slug])) {
            $brands[$slug] = array_merge($blank, (array) $values, array(
                '_slug' => $slug,
                '_builtin' => false,
                '_customized' => false,
            ));
        }
    }
    return $brands;
}

function bbcore_brand_exists($slug) {
    $brands = bbcore_get_brands();
    return is_string($slug) && isset($brands[$slug]);
}

// The brand this site shows. Until setup is finished, a brand detected from
// an old K&C or Pace plugin is used, otherwise B&B Media.
function bbcore_active_brand_slug() {
    foreach (array(get_option('bbcore_active_brand'), get_option('bbcore_suggested_brand')) as $slug) {
        if (bbcore_brand_exists($slug)) {
            return $slug;
        }
    }
    return BBCORE_DEFAULT_BRAND;
}

function bbcore_get_brand($slug = null) {
    $brands = bbcore_get_brands();
    if ($slug === null || !isset($brands[$slug])) {
        $slug = bbcore_active_brand_slug();
    }
    if (isset($brands[$slug])) {
        return $brands[$slug];
    }
    return reset($brands);
}

// One brand value, e.g. bbcore_brand('name').
function bbcore_brand($field) {
    $brand = bbcore_get_brand();
    return isset($brand[$field]) ? $brand[$field] : '';
}

// Turns an image setting into a URL. Bare file names point into the
// brand's own folder; full URLs (e.g. from the media library) are kept.
function bbcore_brand_image_url($field, $brand = null) {
    if ($brand === null) {
        $brand = bbcore_get_brand();
    }
    $value = isset($brand[$field]) ? trim($brand[$field]) : '';

    if ($value === '') {
        return $field === 'login_background' ? BBCORE_URL . 'assets/images/login-bg.jpg' : '';
    }
    if (bbcore_is_bundled_file_name($value)) {
        return BBCORE_URL . 'brands/' . $brand['_slug'] . '/' . $value;
    }
    return $value;
}

function bbcore_is_bundled_file_name($value) {
    return (bool) preg_match('/^[A-Za-z0-9._-]+$/', $value);
}

function bbcore_brand_emails($brand = null) {
    if ($brand === null) {
        $brand = bbcore_get_brand();
    }
    $emails = array();
    foreach (explode(',', $brand['support_emails']) as $email) {
        $email = sanitize_email(trim($email));
        if ($email !== '' && is_email($email)) {
            $emails[] = $email;
        }
    }
    return $emails;
}

// Cleans submitted Brand Settings values. Unknown keys are dropped.
function bbcore_sanitize_brand_values($input) {
    $clean = array();
    foreach (bbcore_brand_fields() as $key => $field) {
        $value = isset($input[$key]) && is_scalar($input[$key]) ? trim(wp_unslash((string) $input[$key])) : '';
        switch ($field['type']) {
            case 'textarea':
                $value = sanitize_textarea_field($value);
                break;
            case 'url':
                $value = esc_url_raw($value);
                break;
            case 'image':
                $value = bbcore_is_bundled_file_name($value) ? $value : esc_url_raw($value);
                break;
            case 'emails':
                $value = implode(', ', bbcore_brand_emails(array('support_emails' => $value)));
                break;
            case 'color':
                $value = preg_match('/^#([0-9a-f]{3,4}|[0-9a-f]{6}|[0-9a-f]{8})$/i', $value) ? strtolower($value) : '';
                break;
            default:
                $value = sanitize_text_field($value);
        }
        $clean[$key] = $value;
    }
    return $clean;
}

// Saves a brand. For built-in brands only the fields that differ from the
// bundled brand.php are stored.
function bbcore_save_brand($slug, $values) {
    $saved = bbcore_saved_brands();
    $builtin = bbcore_builtin_brands();

    if (isset($builtin[$slug])) {
        $diff = array();
        foreach ($values as $key => $value) {
            $default = isset($builtin[$slug][$key]) ? $builtin[$slug][$key] : '';
            if ($value !== $default) {
                $diff[$key] = $value;
            }
        }
        $values = $diff;
        if (empty($values)) {
            unset($saved[$slug]);
            update_option('bbcore_brands', $saved);
            return;
        }
    }
    $saved[$slug] = $values;
    update_option('bbcore_brands', $saved);
}

// Deletes a custom brand, or clears a built-in brand's site edits.
function bbcore_delete_saved_brand($slug) {
    $saved = bbcore_saved_brands();
    unset($saved[$slug]);
    update_option('bbcore_brands', $saved);
}

// The brand as the text of a brand.php file, for adding it to the codebase.
function bbcore_brand_file_contents($brand) {
    $lines = array();
    foreach (array_keys(bbcore_brand_fields()) as $key) {
        $lines[] = '    ' . var_export($key, true) . ' => ' . var_export((string) $brand[$key], true) . ',';
    }
    return "<?php\n"
        . '// ' . str_replace(array("\r", "\n", '?>'), ' ', $brand['name']) . " brand settings. A PHP file so the web server runs it\n"
        . "// instead of showing its contents (support emails etc.) to visitors.\n\n"
        . "if (!defined('ABSPATH')) {\n    exit;\n}\n\n"
        . "return array(\n" . implode("\n", $lines) . "\n);\n";
}
