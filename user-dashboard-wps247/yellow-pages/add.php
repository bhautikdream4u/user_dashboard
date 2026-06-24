
<?php
if (!defined('ABSPATH')) exit;

// Only logged-in users
if (!is_user_logged_in()) {
    echo "<p>Please login to submit an ad.</p>";
    return;
}

$user_id = get_current_user_id();
$has_plan = function_exists('wcs_user_has_subscription') && wcs_user_has_subscription($user_id, '', 'active');

/* If editing, get post data */
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$is_edit = false;
$is_edit_c = false;

if ($edit_id > 0) {
    $post = get_post($edit_id);
    if ($post && $post->post_type === 'yellow_page' && (current_user_can('administrator') or $post->post_author == get_current_user_id())) {
        $is_edit = true;
    }
    if ($post && $post->post_type === 'yellow_page' && $post->post_author == get_current_user_id()) {
        $is_edit_c = true;
    }
}

/* Pre-fill values */
$title       = $is_edit ? $post->post_title : '';
$description = $is_edit ? $post->post_content : '';

$categories = $is_edit
    ? wp_get_post_terms($edit_id, 'yellow-category', ['fields' => 'ids'])
    : [];

$featured       = $is_edit ? get_post_meta($edit_id, 'featured', true) : '0';
$location       = $is_edit ? get_post_meta($edit_id, 'location', true) : '';
$contact_no     = $is_edit ? get_post_meta($edit_id, 'contact_no', true) : '';
$email          = $is_edit ? get_post_meta($edit_id, 'email', true) : '';
$business       = $is_edit ? get_post_meta($edit_id, 'business', true) : '';
$website_url    = $is_edit ? get_post_meta($edit_id, 'website_url', true) : '';
$map_link       = $is_edit ? get_post_meta($edit_id, 'map_link', true) : '';
$expiry_date    = $is_edit ? get_post_meta($edit_id, 'expiry_date', true) : '';
$email_verified = $is_edit ? 1 : '0';

$o_email = $is_edit ? $email : wp_get_current_user()->user_email;
?>

<div class="bds">

<h2><?php echo $is_edit ? "Edit Business" : "List Your Business"; ?></h2>
	
<p style="padding-bottom:14px;">
	Get discovered by the Charlotte Indian community. Add your business details to appear in our local directory and reach customers actively searching for services like yours.
	</p>

<form action="" method="post" enctype="multipart/form-data">

<?php wp_nonce_field('wps247_yellow_action', 'wps247_yellow_nonce'); ?>
<input type="hidden" name="edit_id" value="<?php echo esc_attr($edit_id); ?>">

<!-- TITLE -->
<p>
<label><strong>Business Title*</strong>
<span class="wps247-tip" data-tip="Maximum 60 characters. Use your business name or a clear service title.">i</span>
</label>
<input type="text" name="title" placeholder="Enter Your Business Title" required value="<?php echo esc_attr($title); ?>" style="width:100%;padding:8px;" maxlength="60">
</p>

<!-- FEATURED -->
<p>
<label><strong>Featured*</strong>
<span class="wps247-tip" data-tip="Featured businesses appear at the top and get more visibility. Admin approval required.">i</span>
</label>
<select name="featured" id="featured-select" style="padding:8px;width:100%;">
<option value="0" <?php selected($featured, 'false'); ?>>No</option>
<option value="1" <?php selected($featured, 'true'); ?>>Yes</option>
</select>
</p>

<!-- message -->
<?php if(!$has_plan): ?>
<div id="featured-plan-message" style="display:none;">
Featured Business are available only with a membership plan. <br/>
Purchase our Membership plans to promote your business and get higher visibility.
<div class="btnbox">
<a href="/membership-plans" >Buy Membership Plans</a>
</div></div>
<br/>
<?php endif; ?>

<!-- DESCRIPTION -->
<p>
<label><strong>Description*</strong>
<span class="wps247-tip" data-tip="Maximum 600 characters. Describe your business clearly. Do not include phone numbers or emails here.">i</span>
</label>
</p>

<?php
wp_nonce_field('media-form', '_wpnonce_media_form');
wp_editor(
    $description,
    'yellow_description_editor',
    [
        'textarea_name' => 'description',
        'media_buttons' => true,
        'teeny'         => false,
        'quicktags'     => true,
        'tinymce'       => [
            'toolbar1' => 'bold italic underline | alignleft aligncenter alignright | bullist numlist | link unlink | image',
            'toolbar2' => '',
        ],
    ]
);
?>

<p style="margin-top:6px;font-size:13px;color:#555;">
Characters: <strong><span id="desc-count">0</span>/600</strong>
</p>

<!-- CATEGORIES -->
<p>
<label><strong>Select Categories*</strong>
<span class="wps247-tip" data-tip="Choose the most relevant categories so users can easily find your business.">i</span>
</label>
</p>

<div style="border:1px solid #ccc; padding:10px; max-height:250px; overflow-y:scroll;">
<?php
$terms = get_terms([
    'taxonomy' => 'yellow-category',
    'hide_empty' => false,
    'orderby' => 'name',
]);

if (!empty($terms) && !is_wp_error($terms)) {

    class WPS247_Walker_Category_Checkbox extends Walker_Category {
        function start_el(&$output, $category, $depth = 0, $args = [], $id = 0) {
            $selected = isset($args['selected_cats']) && in_array($category->term_id, $args['selected_cats'])
                ? 'checked'
                : '';

            $output .= '<label style="display:block; margin-left:' . ($depth * 15) . 'px;">
                <input type="checkbox" name="categories[]" value="' . $category->term_id . '" ' . $selected . '>
                ' . $category->name . '
            </label>';
        }
    }

    $walker = new WPS247_Walker_Category_Checkbox();
    echo $walker->walk($terms, 0, ['selected_cats' => $categories]);
} else {
    echo "<p>No categories found.</p>";
}
?>
</div>

<!-- LOCATION -->
<p>
<label><strong>Business Location*</strong>
<span class="wps247-tip" data-tip="Enter the city or area where your business is located.">i</span>
</label>
<input type="text" name="location" placeholder="Enter Your Location" value="<?php echo esc_attr($location); ?>" style="width:100%;padding:8px;" required>
</p>

<!-- EMAIL -->
<p>
<label><strong>Email*</strong>
<span class="wps247-tip" data-tip="Used for verification and notifications. This email is not shown publicly.">i</span>
</label><br>
<input type="email" name="email" placeholder="Enter Your Email" id="ad_email" value="<?php echo esc_attr($email); ?>" style="width:100%;padding:10px;" required>
<input type="hidden" id="original_email" value="<?php echo esc_attr($o_email); ?>">
</p>

<div id="verify_box" style="margin-top:10px; display:none;">
			<button type="button" id="send_code_btn" class="button">Send Verification Code</button>
		</div>

		<div id="code_box" style="margin-top:10px; display:none;">
			<input type="text" id="verify_code" placeholder="Enter verification code" style="padding:10px;width:100%;">
			<button type="button" id="verify_btn" class="button button-primary">Verify</button>
		</div>

		<p id="verify_message" style="color:green; display:none;">Email verified successfully ✔</p>

		<input type="hidden" name="email_verified" id="email_verified" value="<?php echo esc_attr($email_verified); ?>">

<!-- PHONE -->
<p>
<label><strong>Phone Number</strong>
<span class="wps247-tip" data-tip="Optional. Add only if you want customers to contact you by phone.">i</span>
</label>
<input type="text" id="phone" maxlength="14" placeholder="Enter Your Phone Number" name="contact_no" value="<?php echo esc_attr($contact_no); ?>" style="width:100%;padding:8px;" Required >
</p>

<!-- BUSINESS -->
<p>
<label><strong>Business Name*</strong>
<span class="wps247-tip" data-tip="Your registered or commonly known business name.">i</span>
</label>
<input type="text" name="business" placeholder="Enter Your Business Name" value="<?php echo esc_attr($business); ?>" style="width:100%;padding:8px;" required>
</p>

<!-- MAP LINK -->
<p>
<label><strong>Map Link (Business Address)*</strong>
<span class="wps247-tip" data-tip="Paste Google Maps link of your business location.">i</span>
</label>
<input type="text" name="map_link" placeholder="Paste Your Google Maps link Here" value="<?php echo esc_attr($map_link); ?>" style="width:100%;padding:8px;" required>
</p>

<!-- WEBSITE -->
<p>
<label><strong>Website URL*</strong>
<span class="wps247-tip" data-tip="Enter the full website URL starting with https://">i</span>
</label>
<input type="text" name="website_url" placeholder="Enter Your Website URL" value="<?php echo esc_attr($website_url); ?>" style="width:100%;padding:8px;" required>
</p>



<!-- FEATURED IMAGE -->
<p id="featured-image-wrapper">
<label><strong>Featured Image</strong>
<span class="wps247-tip" data-tip="Required only if Featured is Yes. Upload a clear business-related image.">i</span>
</label>
<input type="file" name="featured_image">
</p>

<?php if ($is_edit && has_post_thumbnail($edit_id)): ?>
<p><strong>Current Image:</strong></p>
<?php echo get_the_post_thumbnail($edit_id, 'medium'); ?>
<?php endif; ?>


 <?php if ( current_user_can('administrator') && $is_edit ) : 
   
	
			
			$expiry_db = get_post_meta($edit_id, 'expiry_date', true); // 2026-02-18
			$expiry_input = '';

			if (!empty($expiry_db)) {
				$date = DateTime::createFromFormat('Y-m-d', $expiry_db);
				if ($date) {
					$expiry_input = $date->format('Y-m-d');
				}
			}
   ?>
		
		<!-- EXPIRY DATE -->
        <p> 
            <label><strong>Expiry Date:</strong></label><br>
            <input type="date" name="expiry_date" value="<?php echo $expiry_input; ?>" style="padding:10px;width:100%;">
        </p>
		<?php elseif($is_edit_c): ?>
		<p class="exdate"> 
            <label><strong>Expiry Date:</strong></label> : 
            <b><?php echo esc_attr($expiry); ?></b>
        </p>
		<?php endif; ?>
	

<?php if (current_user_can('administrator') && $is_edit): ?>
<input type="hidden" value="<?php echo get_post_status($edit_id); ?>" name="old_status">
<p>
<label><strong>Change Status (Admin Only)</strong>
<span class="wps247-tip" data-tip="Admin can approve, reject, expire, or move the business back to review.">i</span>
</label>
<select name="ad_status">
	<option value="pending" <?php selected(get_post_status($edit_id), 'pending'); ?>>In Review</option>
			<option value="publish" <?php selected(get_post_status($edit_id), 'publish'); ?>>Approved</option>
					<option value="draft" <?php selected(get_post_status($edit_id), 'draft'); ?>>Draft</option>
					<option value="rejected" <?php selected(get_post_status($edit_id), 'rejected'); ?>>Rejected</option>
					<option value="expired" <?php selected(get_post_status($edit_id), 'expired'); ?>>Expired</option>
</select>
</p>
<?php endif; ?>

<p>
<button type="submit" style="padding:10px 20px;" id="submit-ad-btn">
<?php echo $is_edit ? 'Update Business' : 'Add Business'; ?>
</button>
</p>
<?php wp_nonce_field('media-form', '_wpnonce'); ?>
</form>
</div>

<script>
jQuery(document).ready(function($){

    function checkEmailChanged() {
        var original = $("#original_email").val();
        var current  = $("#ad_email").val();

        if (!original) {
            // add form, always verify
            $("#verify_box").show();
            return true;
        }

        if (original !== current) {
			<?php if ( !current_user_can('administrator')) { ?>
            $("#verify_box").show();
			
            $("#email_verified").val("0");
			<?php } ?>
            return true;
        } else {
            $("#verify_box").hide();
            $("#code_box").hide();
            //$("#verify_message").show();
            $("#email_verified").val("1");
            return false;
        }
    }

    // When typing email
    $("#ad_email").on("input", function(){
        checkEmailChanged();
    });

    // Send verification code
    $("#send_code_btn").on("click", function(){
        var email = $("#ad_email").val();

        $.post(ajaxurl, {
            action: "send_verification_code",
            email: email
        }, function(res){
            if(res == "sent"){
                $("#code_box").show();
                alert("Verification code sent to your email!");
            }
        });
    });

    // Verify code
    $("#verify_btn").on("click", function(){
        var email = $("#ad_email").val();
        var code = $("#verify_code").val();

        $.post(ajaxurl, {
            action: "verify_code",
            email: email,
            code: code
        }, function(res){
            if(res == "verified"){
                $("#email_verified").val("1");
                $("#verify_message").show();
                $("#code_box").hide();
                $("#verify_box,#code_box,#emailVerificationMsg").hide();
            } else {
                alert("Invalid code.");
            }
        });
    });

});

document.addEventListener('DOMContentLoaded', function () {

    const featuredSelect = document.getElementById('featured-select');
    const imageWrapper  = document.getElementById('featured-image-wrapper');
    const note          = document.getElementById('featured-note');

    function toggleFeaturedImage() {
		
        if (featuredSelect.value === '1') {
			imageWrapper.style.display = 'block';
        } else {
            imageWrapper.style.display = 'none';
        }
    }

    featuredSelect.addEventListener('change', toggleFeaturedImage);
    toggleFeaturedImage(); // run on page load (important for edit)
});
 

document.addEventListener('DOMContentLoaded', function () {

    const MAX_CHARS = 600;

    // TinyMCE (Visual editor)
    if (typeof tinymce !== 'undefined') {
        tinymce.on('AddEditor', function (e) {
            const editor = e.editor;

            editor.on('keydown', function (event) {
                const text = editor.getContent({ format: 'text' });

                if (text.length >= MAX_CHARS &&
                    ![8, 46, 37, 38, 39, 40].includes(event.keyCode)) {
                    event.preventDefault();
                }
            });

            editor.on('keyup', function () {
                const text = editor.getContent({ format: 'text' });
                document.getElementById('desc-count').innerText = text.length;
            });
        });
    }

    // Text editor (Quicktags)
    const textarea = document.getElementById('description');
    if (textarea) {
        textarea.addEventListener('input', function () {
            if (this.value.length > MAX_CHARS) {
                this.value = this.value.substring(0, MAX_CHARS);
            }
            document.getElementById('desc-count').innerText = this.value.length;
        });
    }
});


document.addEventListener('click', function (e) {

    // Catch submit buttons & clickable submit elements
    const submitBtn = e.target.closest(
        'button[type="submit"], input[type="submit"], .submit, .publish'
    );

    if (!submitBtn) return;

    /* =========================
       VALIDATE DESCRIPTION
    ========================= */

    let content = '';

    if (typeof tinymce !== 'undefined' && tinymce.get('yellow_description_editor')) {
        content = tinymce
            .get('yellow_description_editor')
            .getContent({ format: 'text' })
            .trim();
    } else {
        const textarea = document.getElementById('yellow_description_editor');
        if (textarea) content = textarea.value.trim();
    }

    if (!content) {
        e.preventDefault();
        alert('⚠️ Description is required.');
        return false;
    }

    /* =========================
       VALIDATE CATEGORY
    ========================= */

    const checkedCats = document.querySelectorAll(
        'input[name="categories[]"]:checked'
    );

    if (!checkedCats.length) {
        e.preventDefault();
        alert('⚠️ Please select at least one category.');
        return false;
    }

});

jQuery(document).ready(function($){
	
	var hasPlan = <?php echo $has_plan ? 'true' : 'false'; ?>;
	
	 function checkFeaturedAccess(){

        var featured = $('#featured-select').val();

        if(featured == '1' && !hasPlan){

            $('#submit-ad-btn').prop('disabled', true);

            if($('#plan-warning').length === 0){
                $('#featured-select').after(
                    '<div id="plan-warning" style="color:#b91c1c;margin-top:6px;">Featured ads require a membership plan. <a href="/membership-plans">View Plans</a></div>'
                );
            }

        } else {

            $('#submit-ad-btn').prop('disabled', false);
            $('#plan-warning').remove();

        }

    }

    $('#featured-select').on('change', checkFeaturedAccess);

    checkFeaturedAccess();
	

    $('#featured-select').on('change', function(){

        if($(this).val() == '1'){
            $('#featured-plan-message').show();
        } else {
            $('#featured-plan-message').hide();
        }

    });

});
</script>
