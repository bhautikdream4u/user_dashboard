<?php
if (!is_user_logged_in()) return;

$user_id = get_current_user_id();
$has_plan = function_exists('wcs_user_has_subscription') && wcs_user_has_subscription($user_id, '', 'active');



/* =========================
   EDIT MODE DETECTION
========================= */
$edit_id = isset($_GET['edit']) ? intval($_GET['edit']) : 0;
$is_edit = $edit_id > 0;



$business_name = $contact_name = $contact_email = $phone = $instructions = '';
$selected_category = '';
$email_verified = 0;

if ($is_edit) {

    $post = get_post($edit_id);

    // Ownership check (allow administrators)
	if (
		!$post ||
		$post->post_type !== 'adv_banner' ||
		(
			(int) $post->post_author !== $user_id &&
			!current_user_can('manage_options') // allow admin
		)
	) {
		wp_die('Invalid banner access');
	}


    $business_name = $post->post_title;
    $contact_name  = get_post_meta($edit_id, 'contact_name', true);
    $contact_email = get_post_meta($edit_id, 'contact_email', true);
    $phone         = get_post_meta($edit_id, 'phone_number', true);
    $instructions  = get_post_meta($edit_id, 'instructions', true);

    $terms = wp_get_post_terms($edit_id, 'banner_category');
    $selected_category = $terms ? $terms[0]->term_id : '';

    // Email already verified in edit mode
    $email_verified = 1;
}else{
	$posts = get_posts(array(
    'post_type'      => 'adv_banner',
    'author'         => $user_id,
    'numberposts'    => 1,
    'post_status'    => 'publish'
));

if (!empty($posts)) {
    $have = 1;
} else {
    $have = 0;
}
}


$o_email = $is_edit ? $contact_email : wp_get_current_user()->user_email;
?>
<!-- message -->
<?php if(!$has_plan && !current_user_can('administrator')): ?>
<div id="featured-plan-message">
For advertise banner are available only with a membership plan. <br/>
Purchase our Membership plans to add your advertise banner.
<div class="btnbox">
<a href="/membership-plans" >Buy Membership Plans</a>
</div></div>
<style>
.bds{display:none !important;}</style>
<?php elseif($have == 1  && !current_user_can('administrator') && $has_plan):?>
<div id="featured-plan-message">
You can not add only one advertise banner with membership plan. you can only edit existing banner.</div>
<style>
.bds{display:none !important;}</style>
<?php endif; ?>

<div class="bds">
<h2><?php echo $is_edit ? "Edit Advertise Banner Details" : "Advertise with Banner Ads"; ?></h2>
	
<p style="padding-bottom:14px;"> Boost your visibility across CharlotteIndia with homepage and newsletter banner ads.
	
	</p>

<form method="post" enctype="multipart/form-data" id="wps247-add-banner-form">

<?php wp_nonce_field('wps247_add_banner_action', 'wps247_add_banner_nonce'); ?>
<input type="hidden" name="edit_id" value="<?php echo esc_attr($edit_id); ?>">

<!-- Business Name -->
<div class="form-group">
    <label>
        Business Name *
        <span class="wps247-tip" data-tip="Enter your business or brand name that will appear with the banner.">i</span>
    </label>
    <input type="text" placeholder="Enter Business Name" name="business_name" value="<?php echo esc_attr($business_name); ?>" required>
</div>

<!-- Contact Name -->
<div class="form-group">
    <label>
        Contact Name *
        <span class="wps247-tip" data-tip="Name of the person responsible for this advertisement.">i</span>
    </label>
    <input type="text" placeholder="Enter Your Contact Name" name="contact_name" value="<?php echo esc_attr($contact_name); ?>" required>
</div>

<!-- Contact Email -->
<div class="form-group">
    <label>
        Contact Email *
        <span class="wps247-tip" data-tip="Used for verification and banner-related communication. Not shown publicly.">i</span>
    </label>

    <input type="email" placeholder="Please Enter Email Address" name="contact_email" id="contact_email" value="<?php echo esc_attr($contact_email); ?>" style="width:100%;padding:10px;" required>
    <input type="hidden" id="original_email" value="<?php echo esc_attr($o_email); ?>">

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
</div>

<!-- Phone -->
<div class="form-group">
    <label>
        Phone Number *
        <span class="wps247-tip" data-tip="Enter a valid phone number for communication about this banner.">i</span>
    </label>
    <input type="tel"  id="phone" name="phone_number" placeholder="Enter Phone Number" maxlength="14" pattern="[0-9]{10}" value="<?php echo esc_attr($phone); ?>" required>
</div>

<!-- Banner Category -->
<div class="form-group">
    <label>
        Choose Your Ads Location *
        <span class="wps247-tip" data-tip="Select where your banner should appear on the website.">i</span>
    </label>

    <select name="banner_category" required>
        <option value="">Select Category</option>
        <?php
        $terms = get_terms([
            'taxonomy'   => 'banner_category',
            'hide_empty' => false
        ]);

        foreach ($terms as $term) {
            printf(
                '<option value="%d" %s>%s</option>',
                $term->term_id,
                selected($selected_category, $term->term_id, false),
                esc_html($term->name)
            );
        }
        ?>
    </select>

    <div class="notesin">
        You can find ad placements in this image:
        <a href="<?php echo $siteurl; ?>/wp-content/uploads/2025/12/home_layout.png" target="_blank">Home.png</a>
    </div>
</div>

<!-- Instructions -->
<div class="form-group">
    <label>
        Any Specific Instruction
        <span class="wps247-tip" data-tip="Optional. Add any special instructions related to banner placement or design.">i</span>
    </label>
    <textarea name="instructions" placeholder="Add any special instructions" ><?php echo esc_textarea($instructions); ?></textarea>
</div>

<!-- Banner Files -->
<div class="form-group">
    <label>
        <strong>Banner Image</strong>
        <span class="wps247-tip" data-tip="Upload banner image. For multiple files, upload a ZIP. Supported: image, zip, pdf.">i</span>
    </label>
    <br>
    <span>If you want to upload multiple images, please make a ZIP and upload it.</span>

    <input
        type="file"
        name="banner_images"
        accept=".zip,image/*,application/pdf"
    >

    <?php if ($is_edit): ?>
        <?php
        $banner_file = get_field('banner_images', $edit_id);
        if ($banner_file):
        ?>
        <div style="margin-top:8px;">
            <strong>Current File:</strong><br>

            <?php if (!empty($banner_file['mime_type']) && strpos($banner_file['mime_type'], 'image/') === 0): ?>
                <img src="<?php echo esc_url($banner_file['url']); ?>" style="max-width:300px;height:auto;">
            <?php else: ?>
                <a href="<?php echo esc_url($banner_file['url']); ?>" target="_blank">
                    <?php echo esc_html($banner_file['filename']); ?>
                </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<button type="submit" name="wps247_add_banner">
    <?php echo $is_edit ? 'Update Banner' : 'Submit Banner'; ?>
</button>

</form>
</div>

<script>
jQuery(document).ready(function($){

    function checkEmailChanged() {
        var original = $("#original_email").val();
        var current  = $("#contact_email").val();

        if (!original) {
            // add form, always verify
            $("#verify_box").show();
            return true;
        }

        if (original !== current) {
            $("#verify_box").show();
            $("#email_verified").val("0");
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
    $("#contact_email").on("input", function(){
        checkEmailChanged();
    });
	
	var ajaxurl = "/wp-admin/admin-ajax.php";

    // Send verification code
    $("#send_code_btn").on("click", function(){
        var email = $("#contact_email").val();

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
        var email = $("#contact_email").val();
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
                $("#verify_box").hide();
            } else {
                alert("Invalid code.");
            }
        });
    });
	
	 // BLOCK FORM SUBMISSION
    $("form").on("submit", function(e){
        if($("#email_verified").val() != "1"){
            e.preventDefault();
            $("#emailVerificationMsg").show().text("Please verify your email first.");
            $("html, body").animate({ scrollTop: $("#contact_email").offset().top - 100 }, 500);
			
        }
    });

});
</script>