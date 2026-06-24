<?php
if (!defined('ABSPATH')) exit;

/**
 * Variables available:
 * $title
 * $description
 * $location
 * $business
 * $email
 * $contact_no
 * $website_url
 * $categories
 * $is_admin
 */
 
 $contact = $_POST['business'];
$email = $_POST['email'];
$tel = $_POST['contact_no'];
$location = $_POST['location'];
$website = $_POST['website_url'];
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body{font-family:Arial;background:#f6f7f9;padding:20px}
.box{max-width:600px;background:#fff;margin:auto;padding:20px;border-radius:6px}
h2{color:#1e3a8a}
table{width:100%;border-collapse:collapse}
td{padding:8px;border-bottom:1px solid #eee}
.label{font-weight:bold;width:160px}
.footer{font-size:12px;color:#777;text-align:center;margin-top:20px}
</style>
</head>
<body>

<div class="box">
<h2>
<?php echo $is_admin ? 'New Business Listing Submitted' : 'Your Business Listing Was Submitted'; ?>
</h2>

<?php if (!$is_admin): ?>
<p>Your business listing has been submitted successfully and is under review.</p>
<?php endif; ?>

<table>
<tr><td class="label">Title</td><td><?php echo esc_html($title); ?></td></tr>
<tr><td class="label">Business</td><td><?php echo esc_html($business); ?></td></tr>
<tr><td class="label">Description</td><td><?php echo wp_kses_post($description); ?></td></tr>
<tr><td class="label">Category</td><td><?php echo esc_html($categories); ?></td></tr>
<tr><td class="label">Location</td><td><?php echo esc_html($location); ?></td></tr>
<tr><td class="label">Email</td><td><?php echo esc_html($email); ?></td></tr>
<tr><td class="label">Phone</td><td><?php echo esc_html($contact_no ?: '—'); ?></td></tr>
<tr><td class="label">Website</td><td><?php echo esc_url($website_url); ?></td></tr>
</table>

<div class="footer">
© <?php echo date('Y'); ?> <?php echo get_bloginfo('name'); ?>
</div>
</div>

</body>
</html>
