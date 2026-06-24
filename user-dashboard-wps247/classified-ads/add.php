<?php
if (!defined('ABSPATH')) exit;

// Only logged-in users
if (!is_user_logged_in()) {
    echo "<p>Please login to submit an ad.</p>";
    return;
}

$user_id = get_current_user_id();
$has_plan = function_exists('wcs_user_has_subscription') && wcs_user_has_subscription($user_id, '', 'active');

/* Detect EDIT MODE */
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$is_edit = false;
$is_edit_c = false;

if ($edit_id > 0) {
    $post = get_post($edit_id);
    if ($post && $post->post_type === 'free_ads' && (current_user_can('administrator') or $post->post_author == get_current_user_id())) {
        $is_edit = true;
    }
    if ($post && $post->post_type === 'free_ads' && $post->post_author == get_current_user_id()) {
        $is_edit_c = true;
    }
}

/* Prefill Fields */
$title       = $is_edit ? $post->post_title : '';
$description = $is_edit ? $post->post_content : '';

$selected_cats = $is_edit ? wp_get_post_terms($edit_id, 'freeads-category', ['fields' => 'ids']) : [];

$featured  = $is_edit ? get_post_meta($edit_id, 'featured', true) : '0';
$contact   = $is_edit ? get_post_meta($edit_id, 'contact', true) : '';
$tel       = $is_edit ? get_post_meta($edit_id, 'ad_tel', true) : '';
$email     = $is_edit ? get_post_meta($edit_id, 'ad_email', true) : '';
$location  = $is_edit ? get_post_meta($edit_id, 'location', true) : '';
$website   = $is_edit ? get_post_meta($edit_id, 'website', true) : '';
$expiry    = $is_edit ? get_post_meta($edit_id, 'ad_expiry', true) : '';
$email_verified = $is_edit ? 1 : 0;
$verified = $is_edit ? get_post_meta($edit_id, 'verified', true) : 'no';

$o_email = $is_edit ? $email : wp_get_current_user()->user_email;
?>

<div class="bds">

<h2><?php echo $is_edit ? "Edit Classified Ad" : "Post a Free Classified"; ?></h2>
	
<p style="padding-bottom:14px;"> Share jobs, services, announcements, or offers with the community — completely free.
	
	</p>

<form method="post" enctype="multipart/form-data">

<?php wp_nonce_field('wps247_add_ad_action', 'wps247_add_ad_nonce'); ?>
<input type="hidden" name="edit_id" value="<?php echo esc_attr($edit_id); ?>">

<!-- TITLE -->
<p>
<label><strong>Title*</strong>
<span class="wps247-tip" data-tip="Maximum 60 characters. Keep the title short and clear so users can easily find your ad.">i</span>
</label><br>
<input type="text" name="ad_title" value="<?php echo esc_attr($title); ?>" required maxlength="60" style="width:100%;padding:10px;" placeholder="Please Enter Title">
</p>


<!-- FEATURED -->
<p>
<label><strong>Featured*</strong>
<span class="wps247-tip" data-tip="Featured ads get higher visibility and appear at the top. Admin approval is required.">i</span>
</label>
<select name="featured" id="featured-select" style="padding:8px;width:100%;">
<option value="0" <?php selected($featured, '0'); ?>>No</option>
<option value="1" <?php selected($featured, '1'); ?>>Yes</option>
</select>
</p>

<!-- message -->
<?php if(!$has_plan): ?>
<div id="featured-plan-message" style="display:none;">
Featured ads are available only with a membership plan. <br/>
Purchase our Membership plans to promote your ad and get higher visibility.
<div class="btnbox">
<a href="/membership-plans" >Buy Membership Plans</a>
</div></div>
<br/>
<?php endif; ?>

<!-- FEATURED IMAGE -->
<p id="featured-image-wrapper">
<label><strong>Featured Image</strong>
<span class="wps247-tip" data-tip="Required only if Featured is set to Yes. Upload a clear image related to your ad.">i</span>
</label><br>
<input type="file" name="ad_featured_image" accept="image/*">
</p>





<!-- DESCRIPTION -->
<p>
<label><strong>Description*</strong>
<span class="wps247-tip" data-tip="Maximum 600 characters. Describe your product or service clearly. Do not add phone numbers or emails here.">i</span>
</label>
</p>

<?php
wp_editor(
    $description,
    'ad_description',
    [
        'textarea_name' => 'ad_description',
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

<!-- CATEGORY -->
<p>
<label><strong>Categories*</strong>
<span class="wps247-tip" data-tip="Choose the most relevant category. This helps your ad appear in the correct section.">i</span>
</label>
<div style="border:1px solid #ccc; padding:10px; max-height:250px; overflow-y:scroll;">
<?php
$terms = get_terms([
    'taxonomy'   => 'freeads-category',
    'hide_empty' => false,
    'orderby'    => 'name',
]);

if (!empty($terms) && !is_wp_error($terms)) {
    $walker = new WPS247_Walker_Category_Checklist();
    echo $walker->walk($terms, 0, ['selected_cats' => $selected_cats]);
} else {
    echo "<p>No categories found.</p>";
}
?>
</div>
</p>

<!-- CONTACT NAME -->
<p>
<label><strong>Contact Name*</strong>
<span class="wps247-tip" data-tip="This name will be shown publicly so users know who to contact.">i</span>
</label><br>
<input type="text" name="contact" placeholder="Please Enter Contact Name" value="<?php echo esc_attr($contact); ?>" style="width:100%;padding:10px;" required>
</p>

<!-- EMAIL -->
<p>
<label><strong>Email*</strong>
<span class="wps247-tip" data-tip="Used for verification and notifications. This email is not shown publicly.">i</span>
</label><br>
<input type="email" name="ad_email" placeholder="Please Enter Your Email" id="ad_email" value="<?php echo esc_attr($email); ?>" style="width:100%;padding:10px;" required>
<input type="hidden" id="original_email" value="<?php echo esc_attr($o_email); ?>">
</p>

<div id="verify_box" style="margin-top:10px; display:none;">
			<button type="button" id="send_code_btn" class="button">Send Verification Code</button>
		</div>

		<div id="code_box" style="margin-top:10px; display:none;">
			<input type="text" id="verify_code" placeholder="Enter verification code" style="padding:10px;width:100%;">
			<button type="button" id="verify_btn" class="button button-primary">Verify</button>
		</div>
		<p id="emailVerificationMsg" style="color:red; margin-top:5px; display:none;">
			Please verify your email first.
		</p>
		<p id="verify_message" style="color:green; display:none;">Email verified successfully ✔</p>

		<input type="hidden" name="email_verified" id="email_verified" value="<?php echo esc_attr($email_verified); ?>">

<!-- PHONE -->
<p>
<label><strong>Phone Number</strong>
<span class="wps247-tip" data-tip="Optional. Add only if you want users to contact you by phone.">i</span>
</label><br>
<input type="text" id="phone" name="ad_tel" maxlength="14" placeholder="Please Enter Phone Number" value="<?php echo esc_attr($tel); ?>" style="width:100%;padding:10px;">
</p>

<!-- LOCATION -->
<p>
<label><strong>Location*</strong>
<span class="wps247-tip" data-tip="Enter the city or area where your product or service is available.">i</span>
</label><br>
<input type="text" name="location" placeholder="Please Enter Your Location" value="<?php echo esc_attr($location); ?>" style="width:100%;padding:10px;" required>
</p>

<!-- WEBSITE -->
<p>
<label><strong>Website</strong>
<span class="wps247-tip" data-tip="Enter the full website URL starting with https://">i</span>
</label><br>
<input type="text" name="website" placeholder="Please Enter Website URL" value="<?php echo esc_attr($website); ?>" style="width:100%;padding:10px;" >
</p>

<?php if ( current_user_can('administrator') && $is_edit ) :
		
			$expiry_db = get_post_meta($edit_id, 'ad_expiry', true); // 2026-02-18
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
            <input type="date" name="ad_expiry" value="<?php echo $expiry_input; ?>" style="padding:10px;width:100%;">
        </p>
		<?php elseif($is_edit_c): ?>
		<p class="exdate"> 
            <label><strong>Expiry Date:</strong></label> : 
            <b><?php echo esc_attr($expiry); ?></b>
        </p>
		<?php endif; ?>

<?php if (current_user_can('administrator') && $is_edit) : 

$st = get_post_status($edit_id);
?>

<input type="hidden" name="old_status" value="<?php echo get_post_status($edit_id); ?>">
<p>
<label><strong>Change Status (Admin Only)</strong>
<span class="wps247-tip" data-tip="Admin can approve, reject, expire, or move the ad back to review.">i</span>
</label>
<select name="ad_status">
    <option value="pending"  <?php selected($st, 'pending');  ?>>In Review</option>
    <option value="publish"  <?php selected($st, 'publish');  ?>>Approved</option>
    <option value="draft"    <?php selected($st, 'draft');    ?>>Draft</option>
    <option value="rejected" <?php selected($st, 'rejected'); ?>>Rejected</option>
    <option value="expired"  <?php selected($st, 'expired');  ?>>Expired</option>
</select>
</p>
<?php endif; ?>


<?php if (current_user_can('administrator')) :


  ?>
<!-- Verified -->
<p>

<label><strong>Verified*</strong>
<span class="wps247-tip" data-tip="Select yes if your business is verified.">i</span>
</label><br>
<input type="radio" name="verified" id="yes-verified" value="yes" <?php if($verified == 'yes'){echo 'checked="checked"';}?> > <label for="yes-verified">Yes </label> <br/>
<input type="radio" name="verified" id="no-verified" value="no" <?php if($verified == 'no'){echo 'checked="checked"';}?> > <label for="no-verified">No </label>  

</p>
<?php endif; ?>

<?php if ($is_edit && has_post_thumbnail($edit_id)) : ?>
<p>Current Image:<br><?php echo get_the_post_thumbnail($edit_id, 'medium'); ?></p>
<?php endif; ?>


<p>
<button class="button button-primary" type="submit" >
<?php echo $is_edit ? "Update Ad" : "Submit Ad"; ?>
</button>
</p>

</form>
</div>

<script>
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
		<?php if ( !current_user_can('administrator')) { ?>
        checkEmailChanged();
		<?php } ?>
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
	
	 // BLOCK FORM SUBMISSION
    $("form").on("submit", function(e){
		<?php if ( !current_user_can('administrator')) { ?>
        if($("#email_verified").val() != "1"){
            e.preventDefault();
            $("#emailVerificationMsg").show().text("Please verify your email first.");
            $("html, body").animate({ scrollTop: $("#contact_email").offset().top - 100 }, 500);
			
        }
		<?php } ?>
    });

});
 jQuery(document).on('click', '.insert-media', function () {
            const interval = setInterval(function () {
                const libraryTab = jQuery('.media-menu-item:contains("Media Library")');
                if (libraryTab.length) {
                    libraryTab.remove(); // remove tab
                    clearInterval(interval);
                }
            }, 100);
        });
		
</script>
<script>
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
</script>

<script>
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
    const textarea = document.getElementById('ad_description');
    if (textarea) {
        textarea.addEventListener('input', function () {
            if (this.value.length > MAX_CHARS) {
                this.value = this.value.substring(0, MAX_CHARS);
            }
            document.getElementById('desc-count').innerText = this.value.length;
        });
    }
});
</script>