<?php
echo "Hi Admin,\n\n";
echo "A new Classified Ad was submitted and is pending your approval.\n";
echo "Title: " . esc_html($title ?? '') . "\n\n";
echo "Please review it in WordPress admin.\n";
?>
