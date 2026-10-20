<?php
// [latest_blog_posts]: the 3 latest posts from the active brand's blog site.

if (!defined('ABSPATH')) {
    exit;
}

function bbcore_latest_blog_posts() {
    $brand = bbcore_get_brand();
    $blog_url = untrailingslashit($brand['blog_url']);
    if ($blog_url === '') {
        return '';
    }

    // Keyed by blog URL, so switching brands never shows another brand's posts.
    $cache_key = 'bbcore_blog_' . md5($blog_url);
    $cached = get_transient($cache_key);
    if ($cached) {
        return $cached;
    }

    $response = wp_remote_get($blog_url . '/wp-json/wp/v2/posts?_embed&per_page=3');
    if (is_wp_error($response)) {
        return '<p>Unable to fetch blog posts at this time. Please try again later.</p>';
    }

    $posts = json_decode(wp_remote_retrieve_body($response));
    if (empty($posts) || !is_array($posts)) {
        return '<p>No recent posts found.</p>';
    }

    $output = '<ul class="latest-blog-posts">';
    foreach ($posts as $post) {
        $title = esc_html($post->title->rendered);
        $link = esc_url($post->link);
        $date = date('F j, Y', strtotime($post->date));
        $author_name = isset($post->_embedded->author[0]->name) ? esc_html($post->_embedded->author[0]->name) : esc_html($brand['blog_author_fallback']);
        $featured_image_url = isset($post->_embedded->{'wp:featuredmedia'}[0]->source_url) ? esc_url($post->_embedded->{'wp:featuredmedia'}[0]->source_url) : '';
        $excerpt = wp_kses_post(wp_trim_words($post->excerpt->rendered, 20, '...'));

        $output .= "<li class='blog-post-item'>";
        $output .= $featured_image_url ? "<div class='post-image' style='background-image: url({$featured_image_url});'></div>" : '';
        $output .= "<div class='post-details'><a href='{$link}' target='_blank' class='post-title'>{$title}</a>";
        $output .= "<span class='post-date'>{$date} by {$author_name}</span>";
        $output .= "<p class='excerpt'>{$excerpt} <a href='{$link}' target='_blank' class='continue-reading'>Continue Reading</a></p></div></li>";
    }
    $output .= '</ul>';

    set_transient($cache_key, $output, 12 * HOUR_IN_SECONDS);

    return $output;
}

function bbcore_register_blog_feed_shortcode() {
    add_shortcode('latest_blog_posts', 'bbcore_latest_blog_posts');
}
add_action('init', 'bbcore_register_blog_feed_shortcode');
