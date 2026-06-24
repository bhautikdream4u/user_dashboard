<?php
if (!defined('ABSPATH')) exit;

// Only logged-in users
if (!is_user_logged_in()) {
    echo "<p>Please login to submit an Offer & Deals.</p>";
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
    if ($post && $post->post_type === 'offer-deal' && (current_user_can('administrator') or $post->post_author == get_current_user_id())) {
        $is_edit = true;
    }
    if ($post && $post->post_type === 'offer-deal' && $post->post_author == get_current_user_id()) {
        $is_edit_c = true;
    }
}

/* Prefill Fields */
$title       = $is_edit ? $post->post_title : '';
$description = $is_edit ? $post->post_content : '';

$selected_cats = $is_edit ? wp_get_post_terms($edit_id, 'business-category', ['fields' => 'ids']) : [];

//$featured  = $is_edit ? get_post_meta($edit_id, 'featured', true) : '0';

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
//$event_type  = $is_edit ? get_post_meta($edit_id, 'event_type', true) : 'free';
//$booking_link   = $is_edit ? get_post_meta($edit_id, 'booking_link', true) : '';

//$expiry    = $is_edit ? get_post_meta($edit_id, 'expiry', true) : '';

//$o_email = $is_edit ? $email : wp_get_current_user()->user_email;
?>

<div class="bds">

<h2><?php echo $is_edit ? "Edit Offer & Deals" : "Post an Offer & Deals"; ?></h2>
	

<form method="post" enctype="multipart/form-data">

<?php wp_nonce_field('wps247_add_offer_action', 'wps247_add_offer_nonce'); ?>
<input type="hidden" name="edit_id" value="<?php echo esc_attr($edit_id); ?>">

<!-- TITLE -->
<p>
<label><strong>Title*</strong>
<span class="wps247-tip" data-tip="Maximum 60 characters. Keep the title short and clear so users can easily find your offer.">i</span>
</label><br>
<input type="text" name="offer_title" placeholder="Enter Title" value="<?php echo esc_attr($title); ?>" required maxlength="60" style="width:100%;padding:10px;">
</p>


<!-- DESCRIPTION -->
<p>
<label><strong>Description*</strong>
<span class="wps247-tip" data-tip="Maximum 600 characters. Describe your offer & Deals clearly. ">i</span>
</label>
</p>

<?php
wp_editor(
    $description,
    'ad_description',
    [
        'textarea_name' => 'offer_description',
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
    'taxonomy'   => 'business-category',
    'hide_empty' => false,
    'orderby'    => 'name',
]);

if (!empty($terms) && !is_wp_error($terms)) {
    $walker = new WPS247_Walker_Category_Checklist_offer();
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
<input type="text" name="location" placeholder="Please Enter Business Location" value="<?php echo esc_attr($location); ?>" style="width:100%;padding:10px;" required>
</p>



<!-- Start Date -->
<p>
<label><strong>Start Date*</strong>
<span class="wps247-tip" data-tip="Add your Offer & Deal Start Date.">i</span>
</label><br>
<input type="date" name="start_date" placeholder="Offer & Deal Start Date" value="<?php echo $start_date; ?>" style="width:100%;padding:10px;" required>
</p>

<!-- End Date -->
<p>
<label><strong>End Date*</strong>
<span class="wps247-tip" data-tip="Add your Offer & Deal End Date.">i</span>
</label><br>
<input type="date" name="end_date" placeholder="Offer & Deal End Date" value="<?php echo $end_date; ?>" style="width:100%;padding:10px;" required>
</p>



<!-- FEATURED IMAGE -->
<p id="featured-image-wrapper">
<label><strong>Featured Image</strong>
<span class="wps247-tip" data-tip="Required only if Featured is set to Yes. Upload a clear image related to your Offer & Deals.">i</span>
</label><br>
<input type="file" name="offer_featured_image" accept="image/*">
</p>

<?php if ($is_edit && has_post_thumbnail($edit_id)) : ?>
<p>Current Image:<br><?php echo get_the_post_thumbnail($edit_id, 'medium'); ?></p>
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
<?php echo $is_edit ? "Update Offer & Deals" : "Submit Offer & Deals"; ?>
</button>
</p>

</form>
</div>

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
    const textarea = document.getElementById('offer_description');
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
