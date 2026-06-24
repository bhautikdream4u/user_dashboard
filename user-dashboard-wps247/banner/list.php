<?php
if (!is_user_logged_in()) return;

$user_id = get_current_user_id();

/* SUCCESS MESSAGE */
$message = "";
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == "added") {
        $message = "<div style='padding:10px;background:#d4edda;border-left:4px solid #28a745;margin-bottom:15px;'>Banner added successfully!</div>";
    }
    if ($_GET['msg'] == "updated") {
        $message = "<div style='padding:10px;background:#cce5ff;border-left:4px solid #004085;margin-bottom:15px;'>Banner updated successfully!</div>";
    }
}

/* =========================
   FILTERS
========================= */
$paged  = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$search = isset($_GET['s']) ? sanitize_text_field($_GET['s']) : '';
$status = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$author = isset($_GET['author']) ? intval($_GET['author']) : '';
$cat    = isset($_GET['banner_category']) ? intval($_GET['banner_category']) : '';

$args = [
    'post_type'      => 'adv_banner',
    'posts_per_page' => 10,
    'paged'          => $paged,
    'post_status'    => $status ? $status : ['pending','publish','draft','expired'],
    's'              => $search,
];

if ($author) {
    $args['author'] = $author;
}

if ($cat) {
    $args['tax_query'] = [[
        'taxonomy' => 'banner_category',
        'field'    => 'term_id',
        'terms'    => $cat
    ]];
}

$query = new WP_Query($args);
?>
<div class="bads">
<?= $message; ?>
<!-- ================= FILTER FORM ================= -->
<form method="get" class="wps247-filter-form adv">
	<div class="filters-row" style="display:flex;gap:10px;align-items:center;">
    <input type="text" name="s" placeholder="Search title..." value="<?php echo esc_attr($search); ?>" style="padding:8px;width:200px;">

    <!-- Type of Ads -->
	<div>
    <select name="banner_category">
        <option value="">All Types</option>
        <?php
        $terms = get_terms(['taxonomy'=>'banner_category','hide_empty'=>false]);
        foreach ($terms as $term) {
            echo '<option value="'.$term->term_id.'" '.selected($cat,$term->term_id,false).'>'.$term->name.'</option>';
        }
        ?>
    </select>
	</div>

    <!-- Status -->
	<div>
    <select name="status">
        <option value="">All Status</option>
        <?php
        foreach (['pending','publish','draft','expired'] as $st) {
            echo '<option value="'.$st.'" '.selected($status,$st,false).'>'.ucfirst($st).'</option>';
        }
        ?>
    </select>
	</div>

    <!-- Author -->
	<div>
    <select name="author">
        <option value="">All Authors</option>
        <?php
        foreach (get_users(['role__in'=>['administrator','subscriber']]) as $user) {
            echo '<option value="'.$user->ID.'" '.selected($author,$user->ID,false).'>'.$user->display_name.'</option>';
        }
        ?>
    </select>
	</div>

    <button type="submit">Filter</button>
	<a href="<?php echo $siteurl; ?>/dashboard/advertise-banners/" class="btn" >Reset All</a>
	</div>
</form>

<!-- ================= TABLE ================= -->
<table class="wps247-table">
    <thead>
        <tr>
            <th>Business Title</th>
            <th>Type of Ads</th>
            <th>Status</th>
            <th>Expiry Date</th>
            <th>Phone</th>
            <th>Email</th>
            <th>Payment Status</th>
            <th>Action</th>
        </tr>
    </thead>
    <tbody>

<?php if ($query->have_posts()): ?>
<?php while ($query->have_posts()): $query->the_post();

    $post_id = get_the_ID();

    $phone  = get_post_meta($post_id, 'phone_number', true);
    $email  = get_post_meta($post_id, 'contact_email', true);
    $expiry = get_post_meta($post_id, 'expiry_date', true);
    $payment = get_post_meta($post_id, 'payment_status', true);

    $terms = wp_get_post_terms($post_id, 'banner_category');
    $type  = $terms ? $terms[0]->name : '-';
?>

<tr>
    <td><?php the_title(); ?></td>
    <td><?php echo esc_html($type); ?></td>
    <td><?php echo ucfirst(get_post_status()); ?></td>
    <td><?php echo $expiry ? esc_html($expiry) : '-'; ?></td>
    <td><?php echo esc_html($phone); ?></td>
    <td><?php echo esc_html($email); ?></td>
    <td><?php echo $payment ? esc_html($payment) : 'Unpaid'; ?></td>

    <td>
        <a href="add/?edit=<?php echo $post_id; ?>">Edit</a>
		<a href="javascript:void(0);" class="view-banner-btn" data-id="<?php echo $post_id; ?>"> View </a>
        
    </td>
</tr>

<?php endwhile; wp_reset_postdata(); ?>
<?php else: ?>
<tr><td colspan="8">No banners found.</td></tr>
<?php endif; ?>

    </tbody>
</table>
<!-- VIEW BANNER POPUP -->
<div id="viewBannerModal" style="display:none;position:fixed;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,.6);z-index:9999;">
    <div style="background:#fff;width:700px;max-width:95%;margin:40px auto;padding:20px;border-radius:8px;position:relative;">
        
        <h3>Banner Details</h3>

        <div id="bannerLoader">Loading...</div>

        <div id="bannerDetails" style="display:none;">
            <table style="width:100%;" class="widefat">
                <tbody id="bannerDetailsBody"></tbody>
            </table>

            <h4 style="margin-top:15px;">Uploaded Files</h4>
            <div id="bannerFiles" style="display:flex;gap:10px;flex-wrap:wrap;"></div>
        </div>

        <button id="closeBannerModal" style="margin-top:15px;">Close</button>
    </div>
</div>

<!-- ================= PAGINATION ================= -->
<?php
echo paginate_links([
    'total'   => $query->max_num_pages,
    'current' => $paged,
]);
?>
</div>

<script>
jQuery(document).ready(function($){
	var ajaxurl = "/wp-admin/admin-ajax.php";
	
    $(".view-banner-btn").on("click", function(){
        let postID = $(this).data("id");

        $("#viewBannerModal").fadeIn();
        $("#bannerLoader").show();
        $("#bannerDetails").hide();
        $("#bannerDetailsBody").html("");
        $("#bannerFiles").html("");

        $.post(ajaxurl, {
            action: "wps247_get_banner_details",
            post_id: postID
        }, function(res){

            if(!res.success){
                $("#bannerLoader").text("Failed to load data");
                return;
            }

            let d = res.data;

            // BASIC FIELDS
            $("#bannerDetailsBody").append(`
                <tr><td><strong>Business Name:</strong></td><td>${d.title}</td></tr>
                <tr><td><strong>Contact Name:</strong></td><td>${d.contact_name}</td></tr>
                <tr><td><strong>Contact Email:</strong></td><td>${d.email}</td></tr>
                <tr><td><strong>Phone Number:</strong></td><td>${d.phone}</td></tr>
                <tr><td><strong>Choose Your Ads Location:</strong></td><td>${d.category}</td></tr>
                <tr><td><strong>Any Specific Instruction:</strong></td><td>${d.instructions}</td></tr>
                <tr><td><strong>Status</strong></td><td>${d.status}</td></tr>
            `);

            // FILES
            if(d.files.length){
                d.files.forEach(file => {

				// IMAGE FILE
				if (file.type.startsWith("image/")) {

					$("#bannerFiles").append(`
						<a href="${file.url}" target="_blank">
							<img src="${file.thumb}" 
								 style="width:100px;height:auto;border:1px solid #ccc;">
						</a>
					`);

				} else {
					// ZIP / PDF / DOC → Download link
					$("#bannerFiles").append(`
						<div style="margin-bottom:10px;">
							📎 <a href="${file.url}" download target="_blank">
								${file.name}
							</a>
						</div>
					`);
				}

			});
            } else {
                $("#bannerFiles").html("<p>No files uploaded.</p>");
            }

            $("#bannerLoader").hide();
            $("#bannerDetails").show();
        });
    });

    $("#closeBannerModal").on("click", function(){
        $("#viewBannerModal").fadeOut();
    });

});
</script>
