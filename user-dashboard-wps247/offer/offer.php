<?php
if (!defined('ABSPATH')) exit;
/*
|--------------------------------------------------------------------------
| 1. HANDLE "ADD NEW Offer" FORM
|--------------------------------------------------------------------------
*/
function wps247_handle_add_offer_form() {

    if (!is_user_logged_in()) return;
    if (!isset($_POST['wps247_add_offer_nonce']) || !wp_verify_nonce($_POST['wps247_add_offer_nonce'], 'wps247_add_offer_action')) return;
	
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
	
    $title       = sanitize_text_field($_POST['offer_title']);
    $description = wp_kses_post($_POST['offer_description']);
    $categories  = isset($_POST['business_category']) ? array_map('intval', $_POST['business_category']) : [];

    /* ===========================
       EDIT MODE
    ============================ */
    if ($is_edit) {

        $post = get_post($edit_id);
        if (!$post || $post->post_type !== 'offer-deal') return;
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
		
		$post_id = wp_update_post([
            'ID'           => $edit_id,
            'post_title'   => $title,
            'post_content' => $description,
            'post_status'  => $new_status
        ]);

        if (!empty($categories)) {
            wp_set_post_terms($post_id, $categories, 'business-category', false);
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
            'post_type'    => 'offer-deal',
            'post_status'  => $st,
            'post_author'  => $user_id
        ]);

        if (!empty($categories)) {
            wp_set_post_terms($post_id, $categories, 'business-category', false);
        }

        wps247_attach_editor_images($post_id);
		if($st == 'publish'){
			$msg = 'added_admin';
		}else{
			$msg = 'added';
		}
        
    }

    if (!$post_id) return;

    /* ===========================
       META FIELDS
    ============================ */
    $meta_fields = [
        'location'  => sanitize_text_field($_POST['location']),
        'start_date'   => sanitize_text_field($_POST['start_date']),
        'end_date' => sanitize_text_field($_POST['end_date']),
        ];

    foreach ($meta_fields as $k => $v) {
        update_post_meta($post_id, $k, $v);
    }
	
	/* ===========================
       FEATURED IMAGE UPLOAD
    ============================ */
    if (!empty($_FILES['offer_featured_image']['name']) ) {

        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');

        $upload = wp_handle_upload($_FILES['offer_featured_image'], ['test_form' => false]);

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
		$terms = wp_get_post_terms($post_id, 'business-category', ['fields' => 'names']);
		$categories = !empty($terms) ? implode(', ', $terms) : '—';

		$template = plugin_dir_path(__FILE__) . 'emails/new-offer-email.php';

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
				'New Offer & Deals Submitted',
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
				'Your Offer Has Been Submitted',
				$user_body,
				$headers
			);

		} else {

			// Fallback (plain text)
			wp_mail(
				$admin_email,
				'New Offer & Deals Submitted',
				"A new Offer & Deals titled '{$title}' was submitted."
			);

			wp_mail(
				$user_email,
				'Your Offer & Deals Was Submitted',
				"Your Offer & Deals '{$title}' has been submitted and is under review."
			);
		}
	}

    /* ===========================
       EMAIL ON USER EDIT
    ============================ */
    if ($is_edit && !current_user_can('administrator')) {

        $admin_email = get_option('admin_email');
        $user_email = wp_get_current_user()->user_email;

        $terms = wp_get_post_terms($post_id, 'business-category', ['fields' => 'names']);
        $categories = !empty($terms) ? implode(', ', $terms) : '—';

        $template = plugin_dir_path(__FILE__) . 'emails/offer-update-review-email.php';

        if (file_exists($template)) {

            $headers = [
                'Content-Type: text/html; charset=UTF-8',
                'From: ' . get_bloginfo('name') . ' <no-reply@stage.charlotteindia.com>',
            ];

            // Admin email
            $is_admin = true;
            ob_start(); include $template; $admin_body = ob_get_clean();
            wp_mail($admin_email, 'Offer & Deals Updated – Review Required', $admin_body, $headers);

            // User email
            $is_admin = false;
            ob_start(); include $template; $user_body = ob_get_clean();
            wp_mail($user_email, 'Your Offer & Deals Update Is Under Review', $user_body, $headers);
        }
    }

    /* ===========================
       REDIRECT
    ============================ */
    wp_redirect($siteurl."/dashboard/offer-deals/?msg={$msg}");
    exit;
}

add_action('init', 'wps247_handle_add_offer_form');

/*
|--------------------------------------------------------------------------
| 2. SHORTCODE: Event List
|--------------------------------------------------------------------------
*/
function wps247_offer_list_shortcode() {
    if (!is_user_logged_in()) return '<p>Please login to view your offers.</p>';

    $plugin_path = plugin_dir_path(__FILE__);
    ob_start();
    include $plugin_path . 'list.php';
    return ob_get_clean();
}
add_shortcode('wps247_offer_list', 'wps247_offer_list_shortcode');


/*
|--------------------------------------------------------------------------
| 3. SHORTCODE: Add New Event
|--------------------------------------------------------------------------
*/
function wps247_offer_add_shortcode() {
    if (!is_user_logged_in()) return '<p>Please login to add a new Offer.</p>';

    $plugin_path = plugin_dir_path(__FILE__);
    ob_start();
    include $plugin_path . 'add.php';
    return ob_get_clean();
}
add_shortcode('wps247_offer_add', 'wps247_offer_add_shortcode');

/*
|--------------------------------------------------------------------------
| 4. Walker class to display hierarchical categories like WP Admin
|--------------------------------------------------------------------------
*/

class WPS247_Walker_Category_Checklist_offer extends Walker_Category {

    public $tree_type = 'business-category';
    public $db_fields = ['parent' => 'parent', 'id' => 'term_id'];

    public function start_el(&$output, $category, $depth = 0, $args = [], $id = 0) {

        $pad = str_repeat('&nbsp;&nbsp;&nbsp;', $depth);

        $checked = (is_array($args['selected_cats']) && in_array($category->term_id, $args['selected_cats']))
            ? 'checked'
            : '';

        $output .= '<label style="display:block;margin-bottom:5px;">' .
        $pad .
        '<input type="checkbox" name="business_category[]" value="' . $category->term_id . '" ' . $checked . '> ' .
        esc_html($category->name) .
        '</label>';
    }
}

/*add_action( 'elementor/query/featured_events', function( $query ) {

    $meta_query = [
        [
            'key'     => 'featured',
            'value'   => '1',
            'compare' => '='
        ]
    ];

    $query->set( 'meta_query', $meta_query );

});

add_action( 'elementor/query/featured_events_no', function( $query ) {

    $meta_query = [
        [
            'key'     => 'featured',
            'value'   => '0',
            'compare' => '='
        ]
    ];

    $query->set( 'meta_query', $meta_query );

}); */

add_action( 'elementor/query/active_offers', function( $query ) {

    $today = date( 'Ymd' );

    $meta_query = [
        [
            'key'     => 'end_date',
            'value'   => $today,
            'compare' => '>=',
            'type'    => 'NUMERIC',
        ]
    ];

    $query->set( 'meta_query', $meta_query );

    $query->set( 'meta_key', 'end_date' );
    $query->set( 'orderby', 'meta_value_num' );
    $query->set( 'order', 'ASC' );

});


/**
 * Shortcode: [offer_deals_archive]
 */
function offer_deals_archive_shortcode() {

    ob_start();

    $today = current_time('m/d/Y');

    /**
     * Render offer cards
     */
    function render_offer_cards($term_id, $taxonomy, $limit = 8, $show_view_all = true) {

        $today = current_time('m/d/Y');

        $query = new WP_Query([
            'post_type'      => 'offer-deal',
            'posts_per_page' => $limit,
            'orderby'        => 'date',
            'order'          => 'DESC',

            'tax_query' => [
                [
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $term_id,
                ]
            ],

            'meta_query' => [
                [
                    'key'     => 'end_date',
                    'value'   => $today,
                    'compare' => '>=',
                    'type'    => 'CHAR'
                ]
            ]
        ]);

        $total_query = new WP_Query([
            'post_type'      => 'offer-deal',
            'posts_per_page' => -1,
            'fields'         => 'ids',

            'tax_query' => [
                [
                    'taxonomy' => $taxonomy,
                    'field'    => 'term_id',
                    'terms'    => $term_id,
                ]
            ],

            'meta_query' => [
                [
                    'key'     => 'end_date',
                    'value'   => $today,
                    'compare' => '>=',
                    'type'    => 'CHAR'
                ]
            ]
        ]);

        if (!$query->have_posts()) {
            return;
        }

        echo '<div class="offer-grid">';

        while ($query->have_posts()) {

            $query->the_post();

            $start_date = get_field('start_date');
            $end_date   = get_field('end_date');

            ?>
            <div class="offer-card">

                <?php if (has_post_thumbnail()) : ?>
                    <a href="<?php the_permalink(); ?>" class="offer-image">
                        <?php the_post_thumbnail('large'); ?>
                    </a>
                <?php endif; ?>

                <div class="offer-content">

                    <h3 class="offer-title">
                        <a href="<?php the_permalink(); ?>">
                            <?php the_title(); ?>
                        </a>
                    </h3>

                    <div class="offer-excerpt">
                        <?php //echo wp_trim_words(get_the_excerpt(), 10); ?>
                    </div>

                    <?php if ($start_date || $end_date) : ?>
                        <div class="offer-date">
                            📅 <?php echo esc_html($start_date); ?>
                            <?php if ($end_date) : ?>
                                - <?php echo esc_html($end_date); ?>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>

                    <a class="offer-button" href="<?php the_permalink(); ?>">
                        View Details
                    </a>

                </div>

            </div>
            <?php
        }

        echo '</div>';

        wp_reset_postdata();

        if (
            $show_view_all &&
            $total_query->found_posts > $limit
        ) {

            $term_link = get_term_link($term_id);

            echo '<div class="offer-view-all">';
            echo '<a href="' . esc_url($term_link) . '">View All</a>';
            echo '</div>';
        }
    }

    /**
     * Main Offer Archive
     */
    if (is_post_type_archive('offer-deal')) {

        $parent_terms = get_terms([
            'taxonomy'   => 'business-category',
            'hide_empty' => true,
            'parent'     => 0
        ]);

        foreach ($parent_terms as $term) {

            echo '<div class="offer-section">';
            echo '<h2 class="offer-section-title">' . esc_html($term->name) . '</h2>';

            render_offer_cards(
                $term->term_id,
                'business-category',
                8,
                true
            );

            echo '</div>';
        }
    }

    /**
     * Category Archive
     */
    elseif (is_tax('business-category')) {

        $current_term = get_queried_object();

        $children = get_terms([
            'taxonomy'   => 'business-category',
            'parent'     => $current_term->term_id,
            'hide_empty' => true
        ]);

        if (!empty($children)) {

            foreach ($children as $child) {

                echo '<div class="offer-section">';
                echo '<h2 class="offer-section-title">' . esc_html($child->name) . '</h2>';

                render_offer_cards(
                    $child->term_id,
                    'business-category',
                    8,
                    true
                );

                echo '</div>';
            }

        } else {

            render_offer_cards(
                $current_term->term_id,
                'business-category',
                8,
                false
            );
        }
    }

    return ob_get_clean();
}
add_shortcode('offer_deals_archive', 'offer_deals_archive_shortcode');