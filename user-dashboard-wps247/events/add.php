<?php
if (!defined('ABSPATH')) exit;

// Only logged-in users
if (!is_user_logged_in()) {
    echo "<p>Please login to submit an Event.</p>";
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
    if ($post && $post->post_type === 'event' && (current_user_can('administrator') or $post->post_author == get_current_user_id())) {
        $is_edit = true;
    }
    if ($post && $post->post_type === 'event' && $post->post_author == get_current_user_id()) {
        $is_edit_c = true;
    }
}

/* Prefill Fields */
$title       = $is_edit ? $post->post_title : '';
$description = $is_edit ? $post->post_content : '';

$selected_cats = $is_edit ? wp_get_post_terms($edit_id, 'event-category', ['fields' => 'ids']) : [];

$featured  = $is_edit ? get_post_meta($edit_id, 'featured', true) : '0';

$start_date   = $is_edit ? get_post_meta($edit_id, 'start_date', true) : '';
if ($start_date) {

    // If format is 20260220 (8 digits, no dash)
    if (preg_match('/^\d{8}$/', $start_date)) {
        $start_date = DateTime::createFromFormat('Ymd', $start_date)->format('Y-m-d');
    }
  
}
$end_date       = $is_edit ? get_post_meta($edit_id, 'end_date', true) : '';
if ($end_date) {

    // If format is 20260220 (8 digits, no dash)
    if (preg_match('/^\d{8}$/', $end_date)) {
        $end_date = DateTime::createFromFormat('Ymd', $end_date)->format('Y-m-d');
    }
  
}
$location     = $is_edit ? get_post_meta($edit_id, 'location', true) : '';
$event_type  = $is_edit ? get_post_meta($edit_id, 'event_type', true) : 'free';
$booking_link   = $is_edit ? get_post_meta($edit_id, 'booking_link', true) : '';

$expiry    = $is_edit ? get_post_meta($edit_id, 'expiry', true) : '';

$o_email = $is_edit ? $email : wp_get_current_user()->user_email;
?>

<div class="bds">

<h2><?php echo $is_edit ? "Edit Event" : "Post an Event"; ?></h2>
	

<form method="post" enctype="multipart/form-data">

<?php wp_nonce_field('wps247_add_event_action', 'wps247_add_event_nonce'); ?>
<input type="hidden" name="edit_id" value="<?php echo esc_attr($edit_id); ?>">

<!-- TITLE -->
<p>
<label><strong>Title*</strong>
<span class="wps247-tip" data-tip="Maximum 60 characters. Keep the title short and clear so users can easily find your ad.">i</span>
</label><br>
<input type="text" name="event_title" placeholder="Enter Event Title" value="<?php echo esc_attr($title); ?>" required maxlength="60" style="width:100%;padding:10px;">
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
Featured Event are available only with a membership plan. <br/>
Purchase our Membership plans to promote your Event and get higher visibility.
<div class="btnbox">
<a href="/membership-plans" >Buy Membership Plans</a>
</div></div>
<br/>
<?php endif; ?>

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
        'textarea_name' => 'event_description',
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
    'taxonomy'   => 'event-category',
    'hide_empty' => false,
    'orderby'    => 'name',
]);

if (!empty($terms) && !is_wp_error($terms)) {
    $walker = new WPS247_Walker_Category_Checklist_event();
    echo $walker->walk($terms, 0, ['selected_cats' => $selected_cats]);
} else {
    echo "<p>No categories found.</p>";
}
?>
</div>
</p>



<!-- Location -->
<p>
<label><strong>Location*</strong>
<span class="wps247-tip" data-tip="Enter the city or area where your event organize.">i</span>
</label><br>
<input type="text" name="location" placeholder="Please Enter Event Location" value="<?php echo esc_attr($location); ?>" style="width:100%;padding:10px;" required>
</p>



<!-- Start Date -->
<p>
<label><strong>Start Date*</strong>
<span class="wps247-tip" data-tip="Add your event Start Date.">i</span>
</label><br>
<input type="date" name="start_date" placeholder="Event Start Date" value="<?php echo $start_date; ?>" style="width:100%;padding:10px;" required>
</p>

<!-- End Date -->
<p>
<label><strong>End Date*</strong>
<span class="wps247-tip" data-tip="Add your event End Date.">i</span>
</label><br>
<input type="date" name="end_date" placeholder="Event End Date" value="<?php echo $end_date; ?>" style="width:100%;padding:10px;" required>
</p>

<!-- Event Type -->
<p>
<label><strong>Event Type*</strong>
<span class="wps247-tip" data-tip="Event type - free or paid">i</span>
</label><br>
<select name="event_type" id="event_type">
    <option value="free"  <?php selected($event_type, 'free');  ?>>Free</option>
    <option value="paid"  <?php selected($event_type, 'paid');  ?>>Paid</option>
</select>
</p>

<!-- Booking URL -->
<p id="booking_url_wrapper">
<label><strong>Booking URL *</strong>
<span class="wps247-tip" data-tip="Enter the Booking URL start with https://">i</span>
</label><br>
<input type="text" name="booking_link" placeholder="Event Booking URL" value="<?php echo esc_attr($booking_link); ?>" style="width:100%;padding:10px;" id="booking_link" >
</p>




<!-- FEATURED IMAGE -->
<p id="featured-image-wrapper">
<label><strong>Featured Image</strong>
<span class="wps247-tip" data-tip="Required only if Featured is set to Yes. Upload a clear image related to your ad.">i</span>
</label><br>
<input type="file" name="event_featured_image" accept="image/*">
</p>

<?php if ($is_edit && has_post_thumbnail($edit_id)) : ?>
<p>Current Image:<br><?php echo get_the_post_thumbnail($edit_id, 'medium'); ?></p>
<?php endif; ?>

<?php if ( current_user_can('administrator') && $is_edit ) :
		
			$expiry_db = get_post_meta($edit_id, 'expiry', true); // 2026-02-18
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
            <input type="date" name="expiry" value="<?php echo $expiry_input; ?>" style="padding:10px;width:100%;">
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
<select name="event_status">
    <option value="pending"  <?php selected($st, 'pending');  ?>>In Review</option>
    <option value="publish"  <?php selected($st, 'publish');  ?>>Approved</option>
    <option value="draft"    <?php selected($st, 'draft');    ?>>Draft</option>
    <option value="rejected" <?php selected($st, 'rejected'); ?>>Rejected</option>
    <option value="expired"  <?php selected($st, 'expired');  ?>>Expired</option>
</select>
</p>
<?php endif; ?>

<p>
<button class="button button-primary" type="submit" id="submit-ad-btn">
<?php echo $is_edit ? "Update Event" : "Submit Event"; ?>
</button>
</p>

</form>
</div>

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
    const textarea = document.getElementById('event_description');
    if (textarea) {
        textarea.addEventListener('input', function () {
            if (this.value.length > MAX_CHARS) {
                this.value = this.value.substring(0, MAX_CHARS);
            }
            document.getElementById('desc-count').innerText = this.value.length;
        });
    }
});

document.addEventListener("DOMContentLoaded", function() {

    var eventType = document.getElementById("event_type");
    var bookingWrapper = document.getElementById("booking_url_wrapper");

    function toggleBookingField() {
        if (eventType.value === "paid") {
            bookingWrapper.style.display = "block";
			bookingWrapper.setAttribute("required", "required");
        } else {
            bookingWrapper.style.display = "none";
			bookingWrapper.removeAttribute("required");
        }
    }

    // Run on page load (important for edit mode)
    toggleBookingField();

    // Run when changed
    eventType.addEventListener("change", toggleBookingField);

});



jQuery(document).ready(function($){
	
	var hasPlan = <?php echo $has_plan ? 'true' : 'false'; ?>;
	
	 function checkFeaturedAccess(){

        var featured = $('#featured-select').val();

        if(featured == '1' && !hasPlan){

            $('#submit-ad-btn').prop('disabled', true);

            if($('#plan-warning').length === 0){
                $('#featured-select').after(
                    '<div id="plan-warning" style="color:#b91c1c;margin-top:6px;">Featured Events require a membership plan. <a href="/membership-plans">View Plans</a></div>'
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
