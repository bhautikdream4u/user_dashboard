<?php
/*
|--------------------------------------------------------------------------
| 1. SHORTCODE: Add New Banner
|--------------------------------------------------------------------------
*/

function wps247_add_banner_shortcode() {
    if (!is_user_logged_in()) return '<p>Please login to add a new ad.</p>';

    $plugin_path = plugin_dir_path(__FILE__);
    ob_start();
    include $plugin_path . 'add.php';
    return ob_get_clean();
}
add_shortcode('wps247_add_banner', 'wps247_add_banner_shortcode');

/*
|--------------------------------------------------------------------------
| 2. SHORTCODE: banner Ads List
|--------------------------------------------------------------------------
*/
function wps247_banner_list_shortcode() {
    if (!is_user_logged_in()) return '<p>Please login to view your ads.</p>';

    $plugin_path = plugin_dir_path(__FILE__);
    ob_start();
    include $plugin_path . 'list.php';
    return ob_get_clean();
}
add_shortcode('wps247_banner_list', 'wps247_banner_list_shortcode');

/*
|--------------------------------------------------------------------------
| 3. Add Banner Handler function
|--------------------------------------------------------------------------
*/

add_action('init', 'wps247_handle_add_banner_form');
function wps247_handle_add_banner_form() {

    if (!isset($_POST['wps247_add_banner'])) return;
    if (!is_user_logged_in()) return;

    if (!wp_verify_nonce($_POST['wps247_add_banner_nonce'], 'wps247_add_banner_action')) {
        wp_die('Security check failed');
    }

    if ($_POST['email_verified'] !== "1") {
        wp_die('Please verify your email before submitting.');
    }

    $user_id = get_current_user_id();

    // Sanitize
    $business_name = sanitize_text_field($_POST['business_name']);
    $contact_name  = sanitize_text_field($_POST['contact_name']);
    $contact_email = sanitize_email($_POST['contact_email']);
    $phone         = sanitize_text_field($_POST['phone_number']);
    $instructions  = sanitize_textarea_field($_POST['instructions']);
    $category_id   = intval($_POST['banner_category']);

    // Create post
    $post_id = wp_insert_post([
        'post_type'   => 'adv_banner',
        'post_status' => 'pending',
        'post_title'  => $business_name,
        'post_author' => $user_id
    ]);

    if (is_wp_error($post_id)) {
        wp_die('Unable to create banner');
    }

    // Save meta
    update_post_meta($post_id, 'contact_name', $contact_name);
    update_post_meta($post_id, 'contact_email', $contact_email);
    update_post_meta($post_id, 'phone_number', $phone);
    update_post_meta($post_id, 'instructions', $instructions);

    // Taxonomy
    wp_set_post_terms($post_id, [$category_id], 'banner_category');

    /* =============================
       FILE UPLOAD
    ============================== */
    $attachments = [];

    if (!empty($_FILES['banner_images']['name'])) {

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/media.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $file_id = media_handle_upload('banner_images', $post_id);

        if (!is_wp_error($file_id)) {
            // ACF FILE FIELD (field key)
            update_field('field_694952d9c5fb7', $file_id, $post_id);

            // Get absolute file path for email attachment
            $file_path = get_attached_file($file_id);
            if ($file_path && file_exists($file_path)) {
                $attachments[] = $file_path;
            }
        }
    }

    /* =============================
       ADMIN EMAIL (WITH DATA + FILE)
    ============================== */

    $category_name = '';
    $term = get_term($category_id, 'banner_category');
    if ($term && !is_wp_error($term)) {
        $category_name = $term->name;
    }

    $admin_email = get_option('admin_email');

    $subject = 'New Banner Submitted (Pending Review)';

    $message  = "A new banner ad has been submitted.\n\n";
    $message .= "Post ID: {$post_id}\n";
    $message .= "Business Name: {$business_name}\n";
    $message .= "Contact Name: {$contact_name}\n";
    $message .= "Contact Email: {$contact_email}\n";
    $message .= "Phone Number: {$phone}\n";
    $message .= "Category: {$category_name}\n\n";
    $message .= "Instructions:\n{$instructions}\n\n";
    $message .= "Status: Pending Review\n";

    wp_mail(
        $admin_email,
        $subject,
        $message,
        [],
        $attachments // 
    );

    /* =============================
       REDIRECT
    ============================== */
    $redirect_url = $siteurl."/dashboard/advertise-banners/?msg=added";
    wp_redirect($redirect_url);
    exit;
}