<?php
if (!defined('ABSPATH')) exit;

/**
 * Variables:
 * $title
 * $description
 * $start_date
 * $end_date
 * $event_type
 * $event_link
 * $location
 * $categories
 * $featured
 * $is_admin
 */

// Optional: sanitize if coming directly from POST
$start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
$end_date   = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';
$event_type = isset($_POST['event_type']) ? sanitize_text_field($_POST['event_type']) : '';
$location   = isset($_POST['location']) ? sanitize_text_field($_POST['location']) : '';
$event_link = isset($_POST['booking_link']) ? esc_url($_POST['booking_link']) : '';
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
td{padding:8px;border-bottom:1px solid #eee;vertical-align:top}
.label{font-weight:bold;width:160px}
.footer{font-size:12px;color:#777;text-align:center;margin-top:20px}
</style>
</head>
<body>

<div class="box">

<h2>
<?php echo $is_admin
    ? 'Event Updated – Review Required'
    : 'Your Event Update Is Under Review'; ?>
</h2>

<?php if (!$is_admin): ?>
<p>You updated your event.  
Our team is reviewing your changes. The event will be visible again once approved.</p>
<?php else: ?>
<p>A user has updated an event. Please review the changes.</p>
<?php endif; ?>

<table>

<tr>
    <td class="label">Event Title</td>
    <td><?php echo esc_html($title); ?></td>
</tr>

<tr>
    <td class="label">Description</td>
    <td><?php echo wp_kses_post($description); ?></td>
</tr>

<tr>
    <td class="label">Category</td>
    <td><?php echo esc_html($categories); ?></td>
</tr>

<tr>
    <td class="label">Event Type</td>
    <td><?php echo esc_html($event_type ?: '—'); ?></td>
</tr>

<tr>
    <td class="label">Start Date</td>
    <td><?php echo esc_html($start_date ?: '—'); ?></td>
</tr>

<tr>
    <td class="label">End Date</td>
    <td><?php echo esc_html($end_date ?: '—'); ?></td>
</tr>

<tr>
    <td class="label">Location</td>
    <td><?php echo esc_html($location ?: '—'); ?></td>
</tr>
<?php if($event_type =='paid'): ?>
<tr>
    <td class="label">Event Link</td>
    <td>
        <?php if (!empty($event_link)) : ?>
            <a href="<?php echo esc_url($event_link); ?>" target="_blank">
                <?php echo esc_html($event_link); ?>
            </a>
        <?php else : ?>
            —
        <?php endif; ?>
    </td>
</tr>
 <?php endif; ?>
<tr>
    <td class="label">Featured</td>
    <td><?php echo $featured == 1 ? 'Yes' : 'No'; ?></td>
</tr>
<?php
		if (has_post_thumbnail($post_id)) {
			$image_url = get_the_post_thumbnail_url($post_id, 'large');
		?>
		<tr>
			<td>Featured Image</td>
			<td> <img src="<?php echo esc_url($image_url); ?>" width="600" style="max-width:100%; height:auto; border-radius:6px;"> </td>
		</tr>
		<?php
		}
		?>

</table>

<div class="footer">
© <?php echo date('Y'); ?> <?php echo get_bloginfo('name'); ?>
</div>

</div>

</body>
</html>
