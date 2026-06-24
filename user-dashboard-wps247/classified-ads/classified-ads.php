<?php
/*
|--------------------------------------------------------------------------
| 1. HANDLE "ADD NEW CLASSIFIED AD" FORM
|--------------------------------------------------------------------------
*/
function wps247_handle_add_ad_form() {

    if (!is_user_logged_in()) return;
    if (!isset($_POST['wps247_add_ad_nonce']) || !wp_verify_nonce($_POST['wps247_add_ad_nonce'], 'wps247_add_ad_action')) return;

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
	
    $title       = sanitize_text_field($_POST['ad_title']);
    $description = wp_kses_post($_POST['ad_description']);
    $categories  = isset($_POST['ad_category']) ? array_map('intval', $_POST['ad_category']) : [];

    /* ===========================
       EDIT MODE
    ============================ */
    if ($is_edit) {

        $post = get_post($edit_id);
        if (!$post || $post->post_type !== 'free_ads') return;
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
				$expiry = $_POST['ad_expiry'];
			}
			
            update_post_meta($edit_id, 'ad_expiry', $expiry);
        }

        $post_id = wp_update_post([
            'ID'           => $edit_id,
            'post_title'   => $title,
            'post_content' => $description,
            'post_status'  => $new_status
        ]);

        if (!empty($categories)) {
            wp_set_post_terms($post_id, $categories, 'freeads-category', false);
        }

        wps247_attach_editor_images($post_id);
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
            'post_type'    => 'free_ads',
            'post_status'  => $st,
            'post_author'  => $user_id
        ]);

        if (!empty($categories)) {
            wp_set_post_terms($post_id, $categories, 'freeads-category', false);
        }

        wps247_attach_editor_images($post_id);
        $msg = 'added';
    }

    if (!$post_id) return;

	if(isset($_POST['verified'])){
		$v = $_POST['verified'];
	}else{
		$v = 'no';
	}

    /* ===========================
       META FIELDS
    ============================ */
    $meta_fields = [
        'featured' => sanitize_text_field($_POST['featured']),
        'contact'  => sanitize_text_field($_POST['contact']),
        'ad_tel'   => sanitize_text_field($_POST['ad_tel']),
        'ad_email' => sanitize_email($_POST['ad_email']),
        'location' => sanitize_text_field($_POST['location']),
        'website'  => sanitize_text_field($_POST['website']),
		'verified'  => $v,
		
    ];

    foreach ($meta_fields as $k => $v) {
        update_post_meta($post_id, $k, $v);
    }
	
	/* ===========================
       FEATURED IMAGE UPLOAD
    ============================ */
    if (!empty($_FILES['ad_featured_image']['name']) && $_POST['featured']== 1) {

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $upload = wp_handle_upload($_FILES['ad_featured_image'], ['test_form' => false]);

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
		
		$user_email   = sanitize_email($_POST['ad_email']);

		// Categories text
		$terms = wp_get_post_terms($post_id, 'freeads-category', ['fields' => 'names']);
		$categories = !empty($terms) ? implode(', ', $terms) : '—';

		$template = plugin_dir_path(__FILE__) . 'emails/new-ad-email.php';

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

			$result = wp_mail(
				$admin_email,
				'New Classified Ad Submitted',
				$admin_body,
				$headers
			);
			

			/* -------- USER EMAIL -------- */
			$is_admin = false;
			ob_start();
			include $template;
			$user_body = ob_get_clean();

			$result1 = wp_mail(
				$user_email,
				'Your Classified Ad Has Been Submitted',
				$user_body,
				$headers
			);
			
			
		} else {

			// Fallback (plain text)
			wp_mail(
				$admin_email,
				'New Classified Ad Submitted',
				"A new ad titled '{$title}' was submitted."
			);

			wp_mail(
				$user_email,
				'Your Classified Ad Was Submitted',
				"Your ad '{$title}' has been submitted and is under review."
			);
		}
	}

    /* ===========================
       EMAIL ON USER EDIT
    ============================ */
    if ($is_edit && !current_user_can('administrator')) {

        $admin_email = get_option('admin_email');
        $user_email  = sanitize_email($_POST['ad_email']);

        $terms = wp_get_post_terms($post_id, 'freeads-category', ['fields' => 'names']);
        $categories = !empty($terms) ? implode(', ', $terms) : '—';

        $template = plugin_dir_path(__FILE__) . 'emails/ad-update-review-email.php';

        if (file_exists($template)) {

            $headers = [
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_bloginfo('name') . ' <no-reply@stage.charlotteindia.com>',
            ];

            // Admin email
            $is_admin = true;
            ob_start(); include $template; $admin_body = ob_get_clean();
            wp_mail($admin_email, 'Classified Ad Updated – Review Required', $admin_body, $headers);

            // User email
            $is_admin = false;
            ob_start(); include $template; $user_body = ob_get_clean();
            wp_mail($user_email, 'Your Ad Update Is Under Review', $user_body, $headers);
        }
    }

    /* ===========================
       REDIRECT
    ============================ */
    wp_redirect($siteurl."/dashboard/classified-ads/?msg={$msg}");
    exit;
}

add_action('init', 'wps247_handle_add_ad_form');

/*
|--------------------------------------------------------------------------
| 2. SHORTCODE: Classified Ads List
|--------------------------------------------------------------------------
*/
function wps247_classified_list_shortcode() {
    if (!is_user_logged_in()) return '<p>Please login to view your ads.</p>';

    $plugin_path = plugin_dir_path(__FILE__);
    ob_start();
    include $plugin_path . 'list.php';
    return ob_get_clean();
}
add_shortcode('wps247_classified_list', 'wps247_classified_list_shortcode');


/*
|--------------------------------------------------------------------------
| 3. SHORTCODE: Add New Classified Ad
|--------------------------------------------------------------------------
*/
function wps247_classified_add_shortcode() {
    if (!is_user_logged_in()) return '<p>Please login to add a new ad.</p>';

    $plugin_path = plugin_dir_path(__FILE__);
    ob_start();
    include $plugin_path . 'add.php';
    return ob_get_clean();
}
add_shortcode('wps247_classified_add', 'wps247_classified_add_shortcode');

/*
|--------------------------------------------------------------------------
| 4. Walker class to display hierarchical categories like WP Admin
|--------------------------------------------------------------------------
*/

class WPS247_Walker_Category_Checklist extends Walker_Category {

    public $tree_type = 'freeads-category';
    public $db_fields = ['parent' => 'parent', 'id' => 'term_id'];

    public function start_el(&$output, $category, $depth = 0, $args = [], $id = 0) {

        $pad = str_repeat('&nbsp;&nbsp;&nbsp;', $depth);

        $checked = (is_array($args['selected_cats']) && in_array($category->term_id, $args['selected_cats']))
            ? 'checked'
            : '';

        $output .= '<label style="display:block;margin-bottom:5px;">' .
        $pad .
        '<input type="checkbox" name="ad_category[]" value="' . $category->term_id . '" ' . $checked . '> ' .
        esc_html($category->name) .
        '</label>';
    }
}