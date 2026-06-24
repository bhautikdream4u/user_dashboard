<?php
if (!defined('ABSPATH')) exit;
/*
|--------------------------------------------------------------------------
| 1. HANDLE "ADD NEW Event" FORM
|--------------------------------------------------------------------------
*/
function wps247_handle_add_event_form() {

    if (!is_user_logged_in()) return;
    if (!isset($_POST['wps247_add_event_nonce']) || !wp_verify_nonce($_POST['wps247_add_event_nonce'], 'wps247_add_event_action')) return;
	
    $user_id = get_current_user_id();
    $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    $is_edit = ($edit_id > 0);
	
	
	/* check subscription  */
	
	
	if($is_edit){
	$author_id = get_post_field('post_author', $_POST['edit_id']);
	
	$uid = $author_id;

	

	$has_active_subscription = function_exists('wcs_user_has_subscription') 
		&& wcs_user_has_subscription($uid, '', 'active');

	$had_subscription_before = function_exists('wcs_user_has_subscription') 
		&& (
			wcs_user_has_subscription($uid, '', 'expired') ||
			wcs_user_has_subscription($uid, '', 'cancelled')
		);

	$plan = 0;

	if ($has_active_subscription) {

		$plan = 'active';

	} elseif ($had_subscription_before) {

		$plan = '1';

	} else {

		

	}
	}

    /* ===========================
       FIELDS
    ============================ */
	
    $title       = sanitize_text_field($_POST['event_title']);
    $description = wp_kses_post($_POST['event_description']);
    $categories  = isset($_POST['event_category']) ? array_map('intval', $_POST['event_category']) : [];

    /* ===========================
       EDIT MODE
    ============================ */
    if ($is_edit) {

        $post = get_post($edit_id);
        if (!$post || $post->post_type !== 'event') return;
        if ($post->post_author != $user_id && !current_user_can('administrator')) return;

        if (current_user_can('administrator')) {
            $new_status = get_post_status($edit_id);
            if (isset($_POST['event_status'])) {
                $new_status = sanitize_text_field($_POST['event_status']);
            }
        } else {
            // USER EDIT → FORCE REVIEW
            $new_status = 'pending';
        }
		
		
		/* APPROVAL HANDLES EXPIRY DATE */
        if (current_user_can('administrator') && $new_status === 'publish') {
			
			
			if($_POST['old_status'] !="publish" && $plan == 0){
				$expiry = date_i18n('Y-m-d', strtotime('+30 days'));
			}else if($plan == 'active'){
				$expiry = "";
			}else{
				$expiry = $_POST['expiry'];
			}
			
            update_post_meta($edit_id, 'expiry', $expiry);
        }
		

        $post_id = wp_update_post([
            'ID'           => $edit_id,
            'post_title'   => $title,
            'post_content' => $description,
            'post_status'  => $new_status
        ]);

        if (!empty($categories)) {
            wp_set_post_terms($post_id, $categories, 'event-category', false);
        }

       
        $msg = 'updated';

    } else {
		
		if (current_user_can('administrator')) {
			$st = "publish";
		}else{
			$st = "pending";
		}

        /* ===========================
           ADD NEW POST
        ============================ */
        $post_id = wp_insert_post([
            'post_title'   => $title,
            'post_content' => $description,
            'post_type'    => 'event',
            'post_status'  => $st,
            'post_author'  => $user_id
        ]);

        if (!empty($categories)) {
            wp_set_post_terms($post_id, $categories, 'event-category', false);
        }

        wps247_attach_editor_images($post_id);
		if($st == 'publish'){
			$msg = 'added_admin';
		}else{
			$msg = 'added';
		}
        
    }

    if (!$post_id) return;
	
	//echo sanitize_text_field($_POST['start_date']);exit;

    /* ===========================
       META FIELDS
    ============================ */
    $meta_fields = [
        'featured' => sanitize_text_field($_POST['featured']),
        'location'  => sanitize_text_field($_POST['location']),
        'start_date'   => sanitize_text_field($_POST['start_date']),
        'end_date' => sanitize_text_field($_POST['end_date']),
        'event_type' => sanitize_text_field($_POST['event_type']),
        'booking_link'  => sanitize_text_field($_POST['booking_link']),
    ];

    foreach ($meta_fields as $k => $v) {
        update_post_meta($post_id, $k, $v);
    }
	
	/* ===========================
       FEATURED IMAGE UPLOAD
    ============================ */
    if (!empty($_FILES['event_featured_image']['name']) ) {

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $upload = wp_handle_upload($_FILES['event_featured_image'], ['test_form' => false]);

        if (!isset($upload['error']) && isset($upload['file'])) {

            $file = $upload['file'];

            $attachment_id = wp_insert_attachment([
                'post_mime_type' => $upload['type'],
                'post_title'     => sanitize_file_name($title),
                'post_content'   => '',
                'post_status'    => 'inherit'
            ], $file, $post_id);

            // Generate attachment metadata
            $attach_data = wp_generate_attachment_metadata($attachment_id, $file);
            wp_update_attachment_metadata($attachment_id, $attach_data);

            // Set featured image
            set_post_thumbnail($post_id, $attachment_id);
        }
    }
	
	/* ===================
		Email On add new
	====================== */
	if (!$is_edit) {

		$admin_email = get_option('admin_email');
		$user_email = wp_get_current_user()->user_email;

		// Categories text
		$terms = wp_get_post_terms($post_id, 'event-category', ['fields' => 'names']);
		$categories = !empty($terms) ? implode(', ', $terms) : '—';

		$template = plugin_dir_path(__FILE__) . 'emails/new-event-email.php';

		if (file_exists($template)) {

			// Email headers
			$headers = [
				'Content-Type: text/html; charset=UTF-8',
				'From: ' . get_bloginfo('name') . ' <no-reply@stage.charlotteindia.com>',
			];

			/* -------- ADMIN EMAIL -------- */
			$is_admin = true;
			ob_start();
			include $template;
			$admin_body = ob_get_clean();

			wp_mail(
				$admin_email,
				'New Event Submitted',
				$admin_body,
				$headers
			);

			/* -------- USER EMAIL -------- */
			$is_admin = false;
			ob_start();
			include $template;
			$user_body = ob_get_clean();

			wp_mail(
				$user_email,
				'Your Event Has Been Submitted',
				$user_body,
				$headers
			);

		} else {

			// Fallback (plain text)
			wp_mail(
				$admin_email,
				'New Event Submitted',
				"A new Event titled '{$title}' was submitted."
			);

			wp_mail(
				$user_email,
				'Your Event Was Submitted',
				"Your ad '{$title}' has been submitted and is under review."
			);
		}
	}

    /* ===========================
       EMAIL ON USER EDIT
    ============================ */
    if ($is_edit && !current_user_can('administrator')) {

        $admin_email = get_option('admin_email');
        $user_email = wp_get_current_user()->user_email;

        $terms = wp_get_post_terms($post_id, 'event-category', ['fields' => 'names']);
        $categories = !empty($terms) ? implode(', ', $terms) : '—';

        $template = plugin_dir_path(__FILE__) . 'emails/event-update-review-email.php';

        if (file_exists($template)) {

            $headers = [
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_bloginfo('name') . ' <no-reply@stage.charlotteindia.com>',
            ];

            // Admin email
            $is_admin = true;
            ob_start(); include $template; $admin_body = ob_get_clean();
            wp_mail($admin_email, 'Event Updated – Review Required', $admin_body, $headers);

            // User email
            $is_admin = false;
            ob_start(); include $template; $user_body = ob_get_clean();
            wp_mail($user_email, 'Your Event Update Is Under Review', $user_body, $headers);
        }
    }

    /* ===========================
       REDIRECT
    ============================ */
    wp_redirect($siteurl."/dashboard/events/?msg={$msg}");
    exit;
}

add_action('init', 'wps247_handle_add_event_form');

/*
|--------------------------------------------------------------------------
| 2. SHORTCODE: Event List
|--------------------------------------------------------------------------
*/
function wps247_event_list_shortcode() {
    if (!is_user_logged_in()) return '<p>Please login to view your events.</p>';

    $plugin_path = plugin_dir_path(__FILE__);
    ob_start();
    include $plugin_path . 'list.php';
    return ob_get_clean();
}
add_shortcode('wps247_event_list', 'wps247_event_list_shortcode');


/*
|--------------------------------------------------------------------------
| 3. SHORTCODE: Add New Event
|--------------------------------------------------------------------------
*/
function wps247_event_add_shortcode() {
    if (!is_user_logged_in()) return '<p>Please login to add a new Event.</p>';

    $plugin_path = plugin_dir_path(__FILE__);
    ob_start();
    include $plugin_path . 'add.php';
    return ob_get_clean();
}
add_shortcode('wps247_event_add', 'wps247_event_add_shortcode');

/*
|--------------------------------------------------------------------------
| 4. Walker class to display hierarchical categories like WP Admin
|--------------------------------------------------------------------------
*/

class WPS247_Walker_Category_Checklist_event extends Walker_Category {

    public $tree_type = 'event-category';
    public $db_fields = ['parent' => 'parent', 'id' => 'term_id'];

    public function start_el(&$output, $category, $depth = 0, $args = [], $id = 0) {

        $pad = str_repeat('&nbsp;&nbsp;&nbsp;', $depth);

        $checked = (is_array($args['selected_cats']) && in_array($category->term_id, $args['selected_cats']))
            ? 'checked'
            : '';

        $output .= '<label style="display:block;margin-bottom:5px;">' .
        $pad .
        '<input type="checkbox" name="event_category[]" value="' . $category->term_id . '" ' . $checked . '> ' .
        esc_html($category->name) .
        '</label>';
    }
}

add_action( 'elementor/query/featured_events', function( $query ) {

    $today = current_time( 'Y-m-d' );

    $query->set( 'meta_query', [
        'relation' => 'AND',

        [
            'key'     => 'featured',
            'value'   => '1',
            'compare' => '='
        ],

        [
            'key'     => 'end_date',
            'value'   => $today,
            'compare' => '>=',
            'type'    => 'DATE'
        ]
    ] );

    $query->set( 'meta_key', 'start_date' );
    $query->set( 'orderby', 'meta_value' );
    $query->set( 'order', 'ASC' );

});

add_action( 'elementor/query/featured_events_no', function( $query ) {

    $today = current_time( 'Y-m-d' );

    $query->set( 'meta_query', [
        'relation' => 'AND',

        [
            'key'     => 'featured',
            'value'   => '0',
            'compare' => '='
        ],

        [
            'key'     => 'end_date',
            'value'   => $today,
            'compare' => '>=',
            'type'    => 'DATE'
        ]
    ] );

    $query->set( 'meta_key', 'start_date' );
    $query->set( 'orderby', 'meta_value' );
    $query->set( 'order', 'ASC' );

});


add_shortcode( 'raw_start_date', function() {
    return 'Raw Date: ' . get_post_meta( get_the_ID(), 'start_date', true );
});

function wps247_expire_events() {

    $posts = get_posts(array(
        'post_type'      => 'event',
        'post_status'    => 'publish',
        'posts_per_page' => -1,
        'fields'         => 'ids',
    ));

    foreach ($posts as $post_id) {

        $end_date = get_field('end_date', $post_id);

        if (empty($end_date)) {
            continue;
        }

        $date = false;

        // Format: 2026-07-31
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
            $date = DateTime::createFromFormat('Y-m-d', $end_date);
        }

        // Format: 31/07/2026
        elseif (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $end_date)) {
            $date = DateTime::createFromFormat('d/m/Y', $end_date);
        }

        if (!$date) {
            continue;
        }

        // Expire at end of day
        $date->setTime(23, 59, 59);

        if ($date->getTimestamp() < current_time('timestamp')) {

            wp_update_post(array(
                'ID'          => $post_id,
                'post_status' => 'draft',
            ));

        }
    }
}


if ( ! wp_next_scheduled( 'wps247_daily_expire_events' ) ) {
    wp_schedule_event( time(), 'daily', 'wps247_daily_expire_events' );
}
//add_action('init', 'wps247_expire_events');
add_action( 'wps247_daily_expire_events', 'wps247_expire_events' );

