<?php
if (!defined('ABSPATH')) exit;

/**
 * Available variables:
 * $title
 * $description
 * $contact
 * $email
 * $tel
 * $location
 * $website
 * $featured
 * $categories
 * $is_admin (true/false)
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
        body {
            font-family: Arial, sans-serif;
            background: #f6f7f9;
            padding: 20px;
        }
        .email-box {
            max-width: 600px;
            background: #ffffff;
            margin: auto;
            padding: 20px;
            border-radius: 6px;
        }
        h2 {
            color: #1e3a8a;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        td {
            padding: 8px;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }
        .label {
            font-weight: bold;
            width: 150px;
        }
        .footer {
            font-size: 12px;
            color: #777;
            margin-top: 20px;
            text-align: center;
        }
    </style>
</head>
<body>

<div class="email-box">

    <h2>
        <?php echo $is_admin ? 'New Classified Ad Submitted' : 'Your Classified Ad Was Submitted'; ?>
    </h2>

    <?php if (!$is_admin): ?>
        <p>Thank you for submitting your classified ad. Our team will review it shortly.</p>
    <?php endif; ?>

    <table>
        <tr>
            <td class="label">Title</td>
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
            <td class="label">Contact Name</td>
            <td><?php echo esc_html($contact); ?></td>
        </tr>

        <tr>
            <td class="label">Email</td>
            <td><?php echo esc_html($email); ?></td>
        </tr>

        <tr>
            <td class="label">Phone</td>
            <td><?php echo esc_html($tel ?: '—'); ?></td>
        </tr>

        <tr>
            <td class="label">Location</td>
            <td><?php echo esc_html($location); ?></td>
        </tr>

        <tr>
            <td class="label">Website</td>
            <td><?php echo esc_url($website); ?></td>
        </tr>

        <tr>
            <td class="label">Featured</td>
            <td><?php echo $featured == '1' ? 'Yes' : 'No'; ?></td>
        </tr>
    </table>

    <div class="footer">
        © <?php echo date('Y'); ?> <?php echo get_bloginfo('name'); ?>
    </div>

</div>

</body>
</html>
