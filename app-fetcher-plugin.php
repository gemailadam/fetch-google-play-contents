<?php
/*
Plugin Name: App Fetcher Plugin
Description: Fetch app details from Google Play and Apple Store and create posts automatically.
Version: 1.0
Author: Your Name
*/

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

// إنشاء القائمة في لوحة التحكم
add_action('admin_menu', 'app_fetcher_menu');
function app_fetcher_menu() {
    add_menu_page(
        'App Fetcher',
        'App Fetcher',
        'manage_options',
        'app-fetcher',
        'app_fetcher_page',
        'dashicons-cloud',
        20
    );
}

// صفحة الإعدادات
function app_fetcher_page() {
    ?>
    <div class="wrap">
        <h1>App Fetcher Settings</h1>
        <form method="post" action="options.php">
            <?php
            settings_fields('app_fetcher_settings');
            do_settings_sections('app-fetcher');
            submit_button();
            ?>
        </form>
    </div>
    <?php
}

// تسجيل الإعدادات
add_action('admin_init', 'app_fetcher_settings');
function app_fetcher_settings() {
    register_setting('app_fetcher_settings', 'default_category');
    add_settings_section(
        'app_fetcher_main_section',
        'Main Settings',
        null,
        'app-fetcher'
    );
    add_settings_field(
        'default_category',
        'Default Category',
        'app_fetcher_category_field',
        'app-fetcher',
        'app_fetcher_main_section'
    );
}

function app_fetcher_category_field() {
    $categories = get_categories();
    $default_category = get_option('default_category');
    echo '<select name="default_category">';
    foreach ($categories as $category) {
        echo '<option value="' . $category->term_id . '"' . selected($default_category, $category->term_id, false) . '>' . $category->name . '</option>';
    }
    echo '</select>';
}

// جلب بيانات التطبيق
function fetch_app_details($url) {
    $response = wp_remote_get($url);
    if (is_wp_error($response)) {
        return null;
    }

    $body = wp_remote_retrieve_body($response);
    $data = json_decode($body, true);

    if (!$data) {
        return null;
    }

    // مثال للبيانات المطلوبة
    return [
        'title' => $data['title'] ?? '',
        'description' => $data['description'] ?? '',
        'images' => $data['images'] ?? [],
        'download_link' => $data['download_link'] ?? ''
    ];
}

// إنشاء منشور تلقائي
function create_app_post($app_data) {
    $post_data = [
        'post_title'   => wp_strip_all_tags($app_data['title']),
        'post_content' => $app_data['description'],
        'post_status'  => 'publish',
        'post_author'  => 1,
        'post_category' => [get_option('default_category')]
    ];

    $post_id = wp_insert_post($post_data);

    if ($post_id && !is_wp_error($post_id)) {
        foreach ($app_data['images'] as $image) {
            // رفع الصور وربطها بالمنشور
        }
    }

    return $post_id;
}

// إضافة دعم الـ SEO
add_action('save_post', 'add_seo_meta', 10, 3);
function add_seo_meta($post_id, $post, $update) {
    if ($post->post_type != 'post') return;

    $seo_title = get_post_meta($post_id, '_seo_title', true) ?: $post->post_title;
    $seo_description = get_post_meta($post_id, '_seo_description', true) ?: substr($post->post_content, 0, 150);

    update_post_meta($post_id, '_yoast_wpseo_title', $seo_title);
    update_post_meta($post_id, '_yoast_wpseo_metadesc', $seo_description);
}
