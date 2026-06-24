<?php
/**
 * Plugin Name: User Dashboard
 * Plugin URI: https://wps247.com/
 * Description: A modular user dashboard system for Classified Ads, Yellow Pages, and Banner Ads.
 * Version: 1.7
 * Author: Bhautik Radiya
 * Author URI: https://wps247.com/
 * Text Domain: user-dashboard-wps247
 */

if (!defined('ABSPATH')) exit;

$siteurl  = get_site_url();
// Load global email and status handler
require_once plugin_dir_path(__FILE__) . 'global-status-email.php';

/* LOAD ACCOUNT SYSTEM */
require_once plugin_dir_path(__FILE__) . 'account/account-init.php';

/*
|--------------------------------------------------------------------------
| 1. HANDLE CLASSIFIED ADS
|--------------------------------------------------------------------------
*/
require_once plugin_dir_path(__FILE__) . 'classified-ads/classified-ads.php';


 
/*
|--------------------------------------------------------------------------
| 2. SHORTCODE: Dashboard
|--------------------------------------------------------------------------
*/
function wps247_dashboard_shortcode() {
    if (!is_user_logged_in()) return '<p>Please login to view dashboard.</p>';

    $plugin_path = plugin_dir_path(__FILE__);
    ob_start();
    include $plugin_path . 'dashboard/dashboard-page.php';
    return ob_get_clean();
}
add_shortcode('wps247_dashboard', 'wps247_dashboard_shortcode');






/*
|--------------------------------------------------------------------------
| 3. HANDLE "Yellow Pages"
|--------------------------------------------------------------------------
*/
require_once plugin_dir_path(__FILE__) . 'yellow-pages/yellow-pages.php';


/*
|--------------------------------------------------------------------------
| 3.1. HANDLE "Offer & Deals"
|--------------------------------------------------------------------------
*/
require_once plugin_dir_path(__FILE__) . 'offer/offer.php';


/*
|--------------------------------------------------------------------------
|4. Email Verification functions. - Generate & Send Code
|--------------------------------------------------------------------------
*/
add_action('wp_ajax_send_verification_code', 'wps247_send_verification_code');
add_action('wp_ajax_nopriv_send_verification_code', 'wps247_send_verification_code');

function wps247_send_verification_code() {
    $email = sanitize_email($_POST['email']);
    $code  = rand(100000, 999999);

    set_transient("verify_code_ads_" . $email, $code, 30 * MINUTE_IN_SECONDS);

    wp_mail($email, "Your Verification Code", "Your verification code is: $code");

    echo "sent"; 
    wp_die();
}
/*
|--------------------------------------------------------------------------
5. Email Verification functions. - Verify the Code
|--------------------------------------------------------------------------
*/
add_action('wp_ajax_verify_code', 'wps247_verify_code');
add_action('wp_ajax_nopriv_verify_code', 'wps247_verify_code');

function wps247_verify_code() {
    $code = sanitize_text_field($_POST['code']);
    $email = $_POST['email'] ?? '';

    foreach($_POST as $k=>$v){
        if(strpos($k,"email")>-1){
            $email = $v;
        }
    }

    $stored = get_transient("verify_code_ads_" . $email);

    if ($stored && $stored == $code) {
        echo "verified";
    } else {
        echo $stored;
    }
    wp_die();
}
/*
|--------------------------------------------------------------------------
6. Register Both Statuses (Expired + Rejected)
|--------------------------------------------------------------------------
*/
function wps247_register_custom_statuses() {

    // Rejected
    register_post_status('rejected', array(
        'label'                     => _x('Rejected', 'post'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop(
            'Rejected <span class="count">(%s)</span>',
            'Rejected <span class="count">(%s)</span>'
        ),
    ));

    // Expired
    register_post_status('expired', array(
        'label'                     => _x('Expired', 'post'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop(
            'Expired <span class="count">(%s)</span>',
            'Expired <span class="count">(%s)</span>'
        ),
    ));
}
add_action('init', 'wps247_register_custom_statuses');

/*
|--------------------------------------------------------------------------
7. Ensure custom statuses are preserved when saving via Quick Edit / Bulk Edit / REST API
|--------------------------------------------------------------------------
*/
add_filter('wp_insert_post_data', 'wps247_preserve_custom_status', 10, 2);
function wps247_preserve_custom_status($data, $postarr) {
    $allowed = array('rejected','expired');
    if (isset($postarr['post_status']) && in_array($postarr['post_status'], $allowed)) {
        $data['post_status'] = $postarr['post_status'];
    }
    return $data;
}

/*
|--------------------------------------------------------------------------
8. Show custom statuses in the post list (post states)
|--------------------------------------------------------------------------
*/
add_filter('display_post_states', 'wps247_display_post_states', 10, 2);
function wps247_display_post_states($states, $post) {
    $st = get_post_status($post->ID);
    if ($st === 'rejected') {
        $states['rejected'] = __('Rejected');
    } elseif ($st === 'expired') {
        $states['expired'] = __('Expired');
    }
    return $states;
}




/*
|--------------------------------------------------------------------------
| 9. ADD CUSTOM STATUSES TO ADMIN EDIT PAGE (POST EDIT SCREEN)
|--------------------------------------------------------------------------
*/
add_action('admin_footer-post.php', 'wps247_add_status_to_dropdown');
add_action('admin_footer-post-new.php', 'wps247_add_status_to_dropdown');

function wps247_add_status_to_dropdown() {
    global $post;

    if (!isset($post->post_type)) return;

    // Only apply to classified ads + yellow pages
    if ($post->post_type !== 'free_ads' && $post->post_type !== 'yellow_page' && $post_type !== 'event') {
        return;
    }

    ?>
    <script>
    jQuery(document).ready(function($) {

        // Add "Rejected" and "Expired" to post status dropdowns (edit screen), quick edit and bulk edit
        var optRejected = '<option value="rejected">Rejected</option>';
        var optExpired  = '<option value="expired">Expired</option>';

        // Primary publish box and any select with name 'post_status'
        $("select#post_status, select[name='post_status']").append(optRejected).append(optExpired);

        // Quick edit and inline edit (some themes/plugins use _status)
        $("select[name='_status'], select[name='post_status']").append(optRejected).append(optExpired);

    });</script>
    <?php
}



/*
|--------------------------------------------------------------------------
| 10. ADD CUSTOM STATUSES TO QUICK EDIT + BULK EDIT
|--------------------------------------------------------------------------
*/
add_action('quick_edit_custom_box', 'wps247_quick_edit_status_dropdown', 10, 2);
add_action('bulk_edit_custom_box',  'wps247_quick_edit_status_dropdown', 10, 2);

function wps247_quick_edit_status_dropdown($column, $post_type) {

    // Only apply to classified & yellow pages
    if ($post_type !== 'free_ads' && $post_type !== 'yellow_page' && $post_type !== 'event') return;

    // Only run on title column
    if ($column !== 'title') return;

    ?>
    <script>
    jQuery(document).ready(function($) {
        // Add new statuses to quick edit dropdown
        $('select[name="_status"], select[name="post_status"]').append('<option value="rejected">Rejected</option>');
        $('select[name="_status"], select[name="post_status"]').append('<option value="expired">Expired</option>');
    });
    </script>
    <?php
}


/*
|--------------------------------------------------------------------------
| 11. Load UI Enhancements CSS
|--------------------------------------------------------------------------
*/

add_action('wp_enqueue_scripts', function() {
    wp_enqueue_style('wps247-ui', plugin_dir_url(__FILE__) . 'assets/dashboard-ui.css');
});

/*
|--------------------------------------------------------------------------
| 12. On status change or save, set ad_expiry (ACF field) for free_ads when approved/published
|--------------------------------------------------------------------------
*/

//add_action('save_post', 'wps247_set_ad_expiry_on_save', 20, 3);
function wps247_set_ad_expiry_on_save($post_ID, $post, $update) {
    if (!in_array($post->post_type, array('free_ads', 'yellow_page'))) return;
    $status = get_post_status($post_ID);
    if ($status === 'publish' || $status === 'approved') {
        $expiry = date_i18n('d/m/Y', strtotime('+30 days'));
        update_post_meta($post_ID, 'ad_expiry', $expiry);
    }
}
 
/*
|--------------------------------------------------------------------------
| 13. Handler: Banner
|--------------------------------------------------------------------------
*/

require_once plugin_dir_path(__FILE__) . 'banner/banner.php';



/*
|--------------------------------------------------------------------------
| 14. HANDLE "Events"
|--------------------------------------------------------------------------
*/
require_once plugin_dir_path(__FILE__) . 'events/events.php';


/*
|--------------------------------------------------------------------------
| 15. Bulk Update on all type
|--------------------------------------------------------------------------
*/

add_action('init', function () {

    if (
        isset($_POST['bulk_update_ads']) &&
        isset($_POST['bulk_ads_nonce']) &&
        wp_verify_nonce($_POST['bulk_ads_nonce'], 'bulk_ads_update')
    ) {

        if (empty($_POST['ad_ids'])) return;

        $ad_ids = array_map('intval', $_POST['ad_ids']);
        $status = sanitize_text_field($_POST['bulk_status']);
        $expiry = sanitize_text_field($_POST['bulk_expiry']);

        foreach ($ad_ids as $ad_id) {

            // Update status
            if ($status) {
                wp_update_post([
                    'ID' => $ad_id,
                    'post_status' => $status
                ]);
            }

            // Update expiry
            if ($expiry) {
				if($_POST['type'] == 'Classified'){
					update_post_meta($ad_id, 'ad_expiry', $expiry);
				}else{
					update_post_meta($ad_id, 'expiry_date', $expiry);
				}
               
            }
        }

        wp_redirect(add_query_arg('updated','1',wp_get_referer()));
        exit;
    }
});



/* redirect when logged in */
add_action('template_redirect', function () {

    if (!is_user_logged_in() || !is_page()) {
        return;
    }

    $blocked_pages = ['login', 'register', 'forgot-password'];

    if (is_page($blocked_pages)) {
        wp_safe_redirect($siteurl.'/dashboard/');
        exit;
    }
});

/* attached image to post*/
function wps247_attach_editor_images($post_id) {

    global $wpdb;

    $attachments = $wpdb->get_results(
        $wpdb->prepare(
            "SELECT ID FROM {$wpdb->posts}
             WHERE post_author = %d
             AND post_type = 'attachment'
             AND post_parent = 0",
            get_current_user_id()
        )
    );

    foreach ($attachments as $attachment) {
        wp_update_post([
            'ID'          => $attachment->ID,
            'post_parent' => $post_id
        ]);
    }
}



add_action('wp_ajax_wps247_get_banner_details', 'wps247_get_banner_details');
function wps247_get_banner_details(){

    $post_id = intval($_POST['post_id']);
    $post = get_post($post_id);

    if(!$post){
        wp_send_json_error();
    }

    // Get attachments
    $attachments = get_attached_media('', $post_id);
    $files = [];

    foreach ($attachments as $att) {

		$url  = wp_get_attachment_url($att->ID);
		$path = get_attached_file($att->ID);
		$mime = get_post_mime_type($att->ID);

		$files[] = [
			'url'   => $url,
			'thumb' => wp_get_attachment_image_url($att->ID, 'thumbnail'),
			'type'  => $mime,
			'name'  => basename($path)
		];
	}

	$terms = get_the_terms($post_id, 'banner_category');
	 $category = $terms[0]->name; 

    wp_send_json_success([
        'title'   => $post->post_title,
        'status'  => $post->post_status,
        'contact_name'  => get_post_meta($post_id,'contact_name',true),
        'phone'   => get_post_meta($post_id,'phone_number',true),
        'email'   => get_post_meta($post_id,'contact_email',true),
		'category'   => $category,
        'instructions' => get_post_meta($post_id,'instructions',true),
		 'expiry_date' => get_post_meta($post_id,'expiry_date',true),
        'files'   => $files
    ]);
}

add_action('template_redirect', function () {

    if (strpos($_SERVER['REQUEST_URI'], '/dashboard') !== 0) return;

    if (!is_user_logged_in()) {
        wp_redirect(
            site_url('/login/?redirect_to=' . urlencode($_SERVER['REQUEST_URI']))
        );
        exit;
    }
});


add_action('save_post', 'wps247_auto_set_expiry_admin', 10, 3);

function wps247_auto_set_expiry_admin($post_id, $post, $update) {

    // Only admin
    if (!current_user_can('administrator')) return;

    // Avoid autosave / revision
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (wp_is_post_revision($post_id)) return;

    // Only NEW posts (not edit)
    if ($update) return;

    // Target post types
    $allowed_types = ['free_ads', 'yellow_page'];
    if (!in_array($post->post_type, $allowed_types, true)) return;

    // Avoid duplicate setting
    if (get_post_meta($post_id, 'ad_expiry', true) || get_post_meta($post_id, 'expiry_date', true)) {
        return;
    }

    // Set expiry = today + 30 days
    $expiry_date = date_i18n('Y-m-d', strtotime('+30 days'));

    if ($post->post_type === 'free_ads') {
        update_post_meta($post_id, 'ad_expiry', $expiry_date);
    }

    if ($post->post_type === 'yellow_page') {
        update_post_meta($post_id, 'expiry_date', $expiry_date);
    }
}



/*  Grant upload permission */
add_action('wp_enqueue_scripts', function () {
    if (is_user_logged_in()) {
        wp_enqueue_media();
    }
});
add_action('init', function () {
    if (!defined('DOING_AJAX') && is_user_logged_in()) {
        wp_set_current_user(get_current_user_id());
    }
});
add_filter('user_has_cap', function ($caps, $cap) {

    if ($cap[0] === 'upload_files' && is_user_logged_in()) {
        $caps['upload_files'] = true;
    }

    return $caps;
}, 10, 2);

add_filter('ajax_query_attachments_args', function ($query) {
    if (!current_user_can('upload_files')) {
        $query['post__in'] = [0];
    }
    return $query;
});