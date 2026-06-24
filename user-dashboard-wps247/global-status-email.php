<?php
if (!defined('ABSPATH')) exit;

/*
|--------------------------------------------------------------------------
| GLOBAL STATUS HANDLER FOR CLASSIFIED ADS + YELLOW PAGES
|--------------------------------------------------------------------------
*/

add_action('transition_post_status', 'wps247_status_change_handler', 10, 3);
function wps247_status_change_handler($new_status, $old_status, $post) {
	$headers = array('Content-Type: text/html; charset=UTF-8');
    $cpts = ['free_ads', 'yellow_page'];
    if (!in_array($post->post_type, $cpts)) return;

    if (wp_is_post_autosave($post->ID) || wp_is_post_revision($post->ID)) return;

    $user_email  = get_the_author_meta('user_email', $post->post_author);
    $user_name   = get_the_author_meta('display_name', $post->post_author);
    $title       = $post->post_title;
    $post_id     = $post->ID;

    $base_path = plugin_dir_path(__FILE__);

    /* -------------------------------
       APPROVED EMAIL (pending → publish)
    ---------------------------------*/
    if ($old_status === 'pending' && $new_status === 'publish') {

        // Set expiry date = today + 30 days
        $expiry = date('Y-m-d', strtotime('+30 days'));
        update_post_meta($post_id, 'ad_expiry', $expiry);
		
		

        $template = wps247_get_email_template($post->post_type, 'approved', [
            'name'   => $user_name,
            'title'  => $title,
            'expiry' => $expiry,
            'link'   => "#"
        ]);

        wp_mail($user_email, "Your Listing Has Been Approved!", $template, $headers);
    }

    /* -------------------------------
       REJECTED EMAIL (pending → rejected)
    ---------------------------------*/
    if ($old_status === 'pending' && $new_status === 'rejected') {

        $template = wps247_get_email_template($post->post_type, 'rejected', [
            'name'   => $user_name,
            'title'  => $title,
        ]);

        wp_mail($user_email, "Your Listing Has Been Rejected", $template, $headers);
    }
}


/*
|--------------------------------------------------------------------------
| HELPER — LOAD EMAIL TEMPLATE
|--------------------------------------------------------------------------
*/
function wps247_get_email_template($post_type, $template_name, $vars = []) {

    $folder = ($post_type === 'free_ads') ? 'classified-ads' : 'yellow-pages';

    $path = plugin_dir_path(__FILE__) . "$folder/emails/$template_name.php";

    if (!file_exists($path)) return "Template missing: $template_name";

    ob_start();
    extract($vars);
    include $path;
    return ob_get_clean();
}


/*
|--------------------------------------------------------------------------
| CRON JOB — SEND EXPIRY REMINDERS
|--------------------------------------------------------------------------
*/

add_action('wps247_daily_event', 'wps247_check_expiry_dates');

function wps247_check_expiry_dates() {

    $posts = get_posts([
        'post_type'   => ['free_ads', 'yellow_page'],
        'post_status' => ['publish'],
        'numberposts' => -1,
    ]);

    foreach ($posts as $post) {

        $expiry = get_post_meta($post->ID, 'ad_expiry', true);
        if (!$expiry) continue;

        $today  = date('Y-m-d');
        $minus1 = date('Y-m-d', strtotime('+1 day'));

        $email = get_the_author_meta('user_email', $post->post_author);
        $name  = get_the_author_meta('display_name', $post->post_author);
        $title = $post->post_title;
		$headers = array('Content-Type: text/html; charset=UTF-8');
		
        /* -------------------------------
           1 DAY BEFORE EXPIRY
        ---------------------------------*/
        if ($expiry === $minus1) {

            $template = wps247_get_email_template($post->post_type, 'expiry-warning', [
                'name'   => $name,
                'title'  => $title,
                'expiry' => $expiry,
                'link'   => "#",
            ]);

            wp_mail($email, "Your Listing Will Expire Soon", $template, $headers);
        }

        /* -------------------------------
           ON EXPIRY DAY
        ---------------------------------*/
        if ($expiry === $today) {

            $template = wps247_get_email_template($post->post_type, 'expired', [
                'name'   => $name,
                'title'  => $title,
                'expiry' => $expiry,
                'link'   => "#",
            ]);

            // Change status to expired
            wp_update_post([
                'ID'          => $post->ID,
                'post_status' => 'expired'
            ]);

            wp_mail($email, "Your Listing Has Expired", $template, $headers);
        }
    }
}


/*
|--------------------------------------------------------------------------
| ACTIVATE CRON
|--------------------------------------------------------------------------
*/
register_activation_hook(__FILE__, function () {
    if (!wp_next_scheduled('wps247_daily_event')) {
        wp_schedule_event(time(), 'daily', 'wps247_daily_event');
    }
});

/*
|--------------------------------------------------------------------------
| CLEANUP CRON ON DEACTIVATION
|--------------------------------------------------------------------------
*/
register_deactivation_hook(__FILE__, function () {
    wp_clear_scheduled_hook('wps247_daily_event');
});


// Transition handler to set ad_expiry when post is approved/published
add_action('transition_post_status', 'wps247_set_expiry_on_approval', 15, 3);
function wps247_set_expiry_on_approval($new_status, $old_status, $post) {
    if ($old_status === 'pending' && ($new_status === 'publish' || $new_status === 'approved')) {
        $expiry = date_i18n('d/m/Y', strtotime('+30 days'));
        if ($post->post_type === 'free_ads' || $post->post_type === 'yellow_page') {
            update_post_meta($post->ID, 'ad_expiry', $expiry);
        }
    }
}
