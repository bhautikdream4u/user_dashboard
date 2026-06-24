<?php 
/*
|--------------------------------------------------------------------------
| 1. HANDLE "ADD NEW Yellow Pages" FORM
|--------------------------------------------------------------------------
*/
add_action('init', 'wps247_handle_yellow_form');
function wps247_handle_yellow_form() {

    if (!is_user_logged_in()) return;
    if (!isset($_POST['wps247_yellow_nonce'])) return;
    if (!wp_verify_nonce($_POST['wps247_yellow_nonce'], 'wps247_yellow_action')) return;

    $user_id = get_current_user_id();
    $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
    $is_edit = ($edit_id > 0);
	
	
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

    /* ======================
       BASIC FIELDS
    ====================== */
    $title       = sanitize_text_field($_POST['title']);
    $description = wp_kses_post($_POST['description']);
    $categories  = isset($_POST['categories']) ? array_map('intval', $_POST['categories']) : [];

    /* ======================
       EDIT MODE
    ====================== */
    if ($is_edit) {

        $post = get_post($edit_id);
        if (!$post || $post->post_type !== 'yellow_page') return;
        if ($post->post_author != $user_id && !current_user_can('administrator')) return;

        if (current_user_can('administrator')) {
            $new_status = get_post_status($edit_id);
            if (isset($_POST['ad_status'])) {
                $new_status = sanitize_text_field($_POST['ad_status']);
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
				$expiry = $_POST['expiry_date'];
			}
			
            update_post_meta($edit_id, 'expiry_date', $expiry);
        }

        $post_id = wp_update_post([
            'ID'           => $edit_id,
            'post_title'   => $title,
            'post_content' => $description,
            'post_status'  => $new_status
        ]);

        wps247_attach_editor_images($post_id);

        if (!empty($categories)) {
            wp_set_post_terms($post_id, $categories, 'yellow-category', false);
        }

        $msg = 'updated';

    } else {
		
		if (current_user_can('administrator')) {
			$st = "publish";
		}else{
			$st = "pending";
		}

        /* ======================
           ADD NEW POST
        ====================== */
        $post_id = wp_insert_post([
            'post_title'   => $title,
            'post_content' => $description,
            'post_type'    => 'yellow_page',
            'post_status'  => $st,
            'post_author'  => $user_id
        ]);

        wps247_attach_editor_images($post_id);

        if (!empty($categories)) {
            wp_set_post_terms($post_id, $categories, 'yellow-category', false);
        }

        update_post_meta($post_id, 'featured', 0);
        $msg = 'added';
    }

    if (!$post_id) return;

    /* ======================
       META FIELDS
    ====================== */
    $meta_fields = [
		'featured'    => isset($_POST['featured']) ? intval($_POST['featured']) : 0,
		'location'    => isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '',
		'contact_no'  => isset($_POST['contact_no']) ? sanitize_text_field($_POST['contact_no']) : '',
		'email'       => isset($_POST['email']) ? sanitize_email($_POST['email']) : '',
		'business'    => isset($_POST['business']) ? sanitize_text_field($_POST['business']) : '',
		'website_url' => isset($_POST['website_url']) ? esc_url_raw($_POST['website_url']) : '',
		'map_link'    => isset($_POST['map_link']) ? sanitize_text_field($_POST['map_link']) : '',
	];

    foreach ($meta_fields as $k => $v) {
        update_post_meta($post_id, $k, $v);
    }

    /* ======================
       FEATURED IMAGE
    ====================== */
    if (!empty($_FILES['featured_image']['name']) && $_POST['featured'] == 1) {

        require_once ABSPATH . 'wp-admin/includes/file.php';
        require_once ABSPATH . 'wp-admin/includes/image.php';

        $upload = wp_handle_upload($_FILES['featured_image'], ['test_form' => false]);

        if (!isset($upload['error'])) {

            $attachment_id = wp_insert_attachment([
                'post_mime_type' => $upload['type'],
                'post_title'     => sanitize_file_name($title),
                'post_status'    => 'inherit'
            ], $upload['file'], $post_id);

            $attach_data = wp_generate_attachment_metadata($attachment_id, $upload['file']);
            wp_update_attachment_metadata($attachment_id, $attach_data);

            set_post_thumbnail($post_id, $attachment_id);
        }
    }

	
	 /* ======================
       EMAIL → NEW POST ONLY
    ====================== */
    if (!$is_edit) {

        $admin_email = get_option('admin_email');
        $user_email  = sanitize_email($_POST['email']);

        $terms = wp_get_post_terms($post_id, 'yellow-category', ['fields' => 'names']);
        $categories_text = !empty($terms) ? implode(', ', $terms) : '—';

        $template = plugin_dir_path(__FILE__) . 'emails/new-yellow-email.php';

        if (file_exists($template)) {

            $headers = [
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_bloginfo('name') . ' <no-reply@stage.charlotteindia.com>',
            ];

            // Admin email
            $is_admin = true;
            ob_start();
            include $template;
            $admin_body = ob_get_clean();
            wp_mail($admin_email, 'New Business Listing Submitted', $admin_body, $headers);

            // User email
            $is_admin = false;
            ob_start();
            include $template;
            $user_body = ob_get_clean();
            wp_mail($user_email, 'Your Business Listing Was Submitted', $user_body, $headers);
        }
    }
	
    /* ======================
       EMAIL LOGIC
    ====================== */
    $admin_email = get_option('admin_email');
    $user_email  = sanitize_email($_POST['email']);

    $terms = wp_get_post_terms($post_id, 'yellow-category', ['fields' => 'names']);
    $categories_text = !empty($terms) ? implode(', ', $terms) : '—';

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . get_bloginfo('name') . ' <no-reply@stage.charlotteindia.com>',
    ];

    // USER EDIT → SEND REVIEW EMAIL
    if ($is_edit && !current_user_can('administrator')) {

        $template = plugin_dir_path(__FILE__) . 'emails/yellow-update-review-email.php';

        if (file_exists($template)) {

            // Admin email
            $is_admin = true;
            ob_start(); include $template; $admin_body = ob_get_clean();
            wp_mail($admin_email, 'Listing Updated – Review Required', $admin_body, $headers);

            // User email
            $is_admin = false;
            ob_start(); include $template; $user_body = ob_get_clean();
            wp_mail($user_email, 'Your Listing Update Is Under Review', $user_body, $headers);
        }
    }

    /* ======================
       REDIRECT
    ====================== */
    wp_redirect($siteurl."/dashboard/local-business/?msg={$msg}");
    exit;
}

/*
|--------------------------------------------------------------------------
| 2. SHORTCODE: Yellow Pages Ads List
|--------------------------------------------------------------------------
*/
function wps247_Yellow_list_shortcode() {
    if (!is_user_logged_in()) return '<p>Please login to view your ads.</p>';

    $plugin_path = plugin_dir_path(__FILE__);
    ob_start();
    include $plugin_path . 'list.php';
    return ob_get_clean();
}
add_shortcode('wps247_Yellow_list', 'wps247_Yellow_list_shortcode');


/*
|--------------------------------------------------------------------------
| 3. SHORTCODE: Add New Yellow page
|--------------------------------------------------------------------------
*/
function wps247_yellow_add_shortcode() {
    if (!is_user_logged_in()) return '<p>Please login to add a new ad.</p>';

    $plugin_path = plugin_dir_path(__FILE__);
    ob_start();
    include $plugin_path . 'add.php';
    return ob_get_clean();
}
add_shortcode('wps247_yellow_add', 'wps247_yellow_add_shortcode');