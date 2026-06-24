<?php
if (!defined('ABSPATH')) exit;

/**
 * Variables:
 * $title
 * $description
 * $contact
 * $email
 * $ad_tel
 * $location
 * $website
 * $categories
 * $featured
 * $is_admin
 */
 
$contact = $_POST['contact'];
$email = $_POST['ad_email'];
$tel = $_POST['ad_tel'];
$location = $_POST['location'];
$website = $_POST['website'];
 
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<style>
body{font-family:Arial;background:#f6f7f9;padding:20px}
.box{max-width:600px;background:#fff;margin:auto;padding:20px;border-radius:6px}
h2{color:#b91c1c}
table{width:100%;border-collapse:collapse}
td{padding:8px;border-bottom:1px solid #eee}
.label{font-weight:bold;width:160px}
.footer{font-size:12px;color:#777;text-align:center;margin-top:20px}
</style>
</head>
<body>

<div class="box">
<h2>
<?php echo $is_admin
    ? 'Classified Ad Updated – Review Required'
    : 'Your Ad Update Is Under Review'; ?>
</h2>

<?php if (!$is_admin): ?>
<p>You updated your classified ad.  
Our team is reviewing your changes. The ad will be visible again once approved.</p>
<?php else: ?>
<p>A user has updated a classified ad. Please review the changes.</p>
<?php endif; ?>

<table>
<tr><td class="label">Title</td><td><?php echo esc_html($title); ?></td></tr>
<tr><td class="label">Description</td><td><?php echo wp_kses_post($description); ?></td></tr>
<tr><td class="label">Category</td><td><?php echo esc_html($categories); ?></td></tr>
<tr><td class="label">Contact</td><td><?php echo esc_html($contact); ?></td></tr>
<tr><td class="label">Email</td><td><?php echo esc_html($email); ?></td></tr>
<tr><td class="label">Phone</td><td><?php echo esc_html($ad_tel ?: '—'); ?></td></tr>
<tr><td class="label">Location</td><td><?php echo esc_html($location); ?></td></tr>
<tr><td class="label">Website</td><td><?php echo esc_url($website); ?></td></tr>
<tr><td class="label">Featured</td><td><?php echo $featured == 1 ? 'Yes' : 'No'; ?></td></tr>
</table>

<div class="footer">
© <?php echo date('Y'); ?> <?php echo get_bloginfo('name'); ?>
</div>
</div>

</body>
</html>
