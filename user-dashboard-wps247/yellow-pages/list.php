<?php
if (!defined('ABSPATH')) exit;

if (!is_user_logged_in()) {
    echo "<p>You must be logged in to view this page.</p>";
    return;
}

$current_user = get_current_user_id();

/* SUCCESS MESSAGE */
$message = "";
if (isset($_GET['msg'])) {
    if ($_GET['msg'] == "added") {
        $message = "<div style='padding:10px;background:#d4edda;border-left:4px solid #28a745;margin-bottom:15px;'>Yellow Page added successfully!</div>";
    }
    if ($_GET['msg'] == "updated") {
        $message = "<div style='padding:10px;background:#cce5ff;border-left:4px solid #004085;margin-bottom:15px;'>Yellow Page updated successfully!</div>";
    }
}

/* GET FILTER VALUES */
$filter_title     = isset($_GET['title']) ? sanitize_text_field($_GET['title']) : '';
$filter_status    = isset($_GET['status']) ? sanitize_text_field($_GET['status']) : '';
$filter_cat       = isset($_GET['category']) ? array_map('intval', $_GET['category']) : [];
$filter_author    = isset($_GET['author']) ? intval($_GET['author']) : 0;

/* PAGINATION */
$paged = max( 1, get_query_var('paged'), get_query_var('page') );

/* QUERY ARGUMENTS */
$args = [
    'post_type'      => 'yellow_page',
    'posts_per_page' => 10,
    'paged'          => $paged,
    'post_status' => ['publish','pending','draft','rejected','expired'],
];

/* USER RESTRICTION */
if (!current_user_can('administrator')) {
    $args['author'] = $current_user;
}

/* FILTER: TITLE SEARCH */
if ($filter_title) {
    $args['s'] = $filter_title;
}

/* FILTER: STATUS */
if ($filter_status && $filter_status != 'all') {
    $args['post_status'] = $filter_status;
}

/* FILTER: CATEGORY */
if (!empty($_GET['filter_cat'])) {
    $args['tax_query'] = [
        [
            'taxonomy' => 'yellow-category',
            'field' => 'term_id',
            'terms' => array_map('intval', $_GET['filter_cat']),
        ]
    ];
}


/* FILTER: AUTHOR (ADMIN ONLY) */
if (current_user_can('administrator') && $filter_author) {
    $args['author'] = $filter_author;
}

$query = new WP_Query($args);
?>
<style>
.multi-select-dropdown {
    position: relative;
    width: 250px;
}

.dropdown-btn {
    padding: 10px;
    border: 1px solid #ccc;
    background: #fff;
    cursor: pointer;
    border-radius: 4px;
}

.dropdown-content {
    display: none;
    position: absolute;
    top: 42px;
    left: 0;
    width: 100%;
    max-height: 250px;
    overflow-y: auto;
    background: #fff;
    border: 1px solid #ccc;
    z-index: 10;
    border-radius: 4px;
    padding: 10px;
}

.dropdown-content .dropdown-item {
    display: block;
    padding: 5px 0;
    cursor: pointer;
}

</style>
<div class="bads">

<?= $message; ?>

<!-- ================= FILTER BAR =================== -->
<form method="get" style="margin-bottom:20px; display:flex; gap:15px; align-items:center; flex-wrap:wrap;">
<div class='filters-row' style='display:flex;gap:10px;align-items:center;'>

    <!-- Title Search -->
    <div>
	
        <input type="text" name="title"
               placeholder="Search Title"
               value="<?= esc_attr($filter_title); ?>"
               style="padding:7px;width:180px;">
    </div>

    <!-- Category Multi Select -->
    <div class="filter-field">
		
		<div class="multi-select-dropdown">
			<div class="dropdown-btn">Select Categories</div>

			<div class="dropdown-content">
				<?php
				$terms = get_terms([
					'taxonomy' => 'yellow-category',
					'hide_empty' => false
				]);

				$selected_cats = isset($_GET['filter_cat']) ? (array) $_GET['filter_cat'] : [];

				foreach ($terms as $term) {
					?>
					<label class="dropdown-item">
						<input type="checkbox" 
							   name="filter_cat[]" 
							   value="<?php echo $term->term_id; ?>" 
							   <?php checked(in_array($term->term_id, $selected_cats)); ?>>
						<?php echo $term->name; ?>
					</label>
					<?php
				}
				?>
			</div>
		</div>
	</div>


    <!-- Status Filter -->
    <div>
        <select name="status" style="padding:7px;">
            <option value="all">All Status</option>
            <option value="publish" <?= selected($filter_status, 'publish', false); ?>>Approved</option>
            <option value="pending" <?= selected($filter_status, 'pending', false); ?>>In Review</option>
            <option value="draft" <?= selected($filter_status, 'draft', false); ?>>Draft</option>
			<option value="rejected" <?= selected($filter_status, 'publish', false); ?>>Rejected</option>
        </select>
    </div>

    <!-- Author Filter (Admin only) -->
    <?php if (current_user_can('administrator')): ?>
        <div>
            <select name="author" style="padding:7px;">
                <option value="0">All Authors</option>
                <?php
                $users = get_users(['role__in' => ['administrator', 'editor', 'author', 'subscriber']]);
                foreach ($users as $user) {
                    $sel = selected($filter_author, $user->ID, false);
                    echo "<option value='{$user->ID}' $sel>{$user->display_name}</option>";
                }
                ?>
            </select>
        </div>
    <?php endif; ?>

    <!-- Submit -->
    <div>
        <button type="submit" style="background:#0073aa;color:#fff;border:none;cursor:pointer;">
            Filter
        </button>
		<a class="btn reset" href="<?php echo $siteurl; ?>/dashboard/local-business/">Reset</a>
    </div>

</div>
</form>
<!-- ================= END FILTER BAR ================= -->
<?php if (isset($_GET['updated'])): ?>
		<div class="notice success">Ads updated successfully.</div>
	<?php endif; ?>
<form method="post" id="bulk-update-form">
<?php wp_nonce_field('bulk_ads_update','bulk_ads_nonce'); ?>
<table style="width:100%;border-collapse:collapse;background:#fff;">
    <thead>
    <tr style="background:#f7f7f7;">
		<?php if (current_user_can('administrator')){ ?>
			<th><input type="checkbox" id="select-all"></th>
		<?php } ?>
        <th style="border:1px solid #ddd;padding:10px;">Title</th>
        <th style="border:1px solid #ddd;padding:10px;">Categories</th>
        <th style="border:1px solid #ddd;padding:10px;">Phone</th>
        <th style="border:1px solid #ddd;padding:10px;">Email</th>
        <th style="border:1px solid #ddd;padding:10px;">Expiry</th>
        <th style="border:1px solid #ddd;padding:10px;">Status</th>
        <th style="border:1px solid #ddd;padding:10px;">Action</th>
    </tr>
    </thead>

    <tbody>

    <?php if ($query->have_posts()) : while ($query->have_posts()) : $query->the_post();

        $post_id  = get_the_ID();
        $phone    = get_post_meta($post_id, 'contact_no', true);
        $email    = get_post_meta($post_id, 'email', true);
        $business = get_post_meta($post_id, 'business', true);
        $expiry   = get_post_meta($post_id, 'expiry_date', true);

        $status = get_post_status($post_id);
			if ($status === 'publish') {
				$status_label = '<span style="color:green;font-weight:bold;">Approved</span>';
			} elseif ($status === 'pending') {
				$status_label = '<span style="color:#d69e2e;font-weight:bold;">In Review</span>';
			} elseif ($status === 'draft') {
				$status_label = '<span style="color:#777;font-weight:bold;">Draft</span>';
			} elseif ($status === 'rejected') {
				$status_label = '<span class="status-label status-rejected">Rejected</span>'; 
			} elseif ($status === 'expired') {
				$status_label = '<span class="status-label status-expired">Expired</span> <a class="button-renew" href="#">Renew Now</a>';
			} else {
				$status_label = ucfirst($status);
			}

        $terms = get_the_terms($post_id, 'yellow-category');
        $cats = (!empty($terms)) ? implode(', ', wp_list_pluck($terms, 'name')) : "-";

        $edit_link = 'add?edit=' . $post_id;
        ?>

        <tr>
			<?php if (current_user_can('administrator')){ ?>
				<td  style="padding:10px;border:1px solid #ddd;">
				   <input type="checkbox" name="ad_ids[]" value="<?php echo esc_attr($post_id); ?>">
				</td>
			<?php } ?>
            <td style="border:1px solid #ddd;padding:10px;"><b><?= get_the_title(); ?></b></td>
            <td style="border:1px solid #ddd;padding:10px;"><?= $cats; ?></td>
            <td style="border:1px solid #ddd;padding:10px;"><?= esc_html($phone); ?></td>
            <td style="border:1px solid #ddd;padding:10px;"><?= esc_html($email); ?></td>
            <td style="border:1px solid #ddd;padding:10px;"><?= esc_html($expiry ?: '-'); ?></td>

            <td style="padding:10px;border:1px solid #ddd;"><?= $status_label; ?></td>

            <td style="border:1px solid #ddd;padding:10px;">
                <a href="<?= $edit_link; ?>" style="color:#0073aa;font-weight:bold;">Edit</a>
            </td>
        </tr>

    <?php endwhile; wp_reset_postdata(); else: ?>

        <tr><td colspan="8" style="text-align:center;padding:20px;">No results found.</td></tr>

    <?php endif; ?>

    </tbody>
</table>

<!-- PAGINATION -->
<div style="margin-top:20px;text-align:center;">
    <?php
    // Preserve filters
    $query_args = $_GET;
    unset($query_args['paged']);

    echo paginate_links([
        'total'      => $query->max_num_pages,
        'current'    => $paged,

        // FORCE query-string pagination only
        'base'       => add_query_arg( 'paged', '%#%' ),
        'format'     => '',  // <-- IMPORTANT! prevents /page/2/ structure

        'add_args'   => $query_args,
        'prev_text'  => '&laquo; Prev',
        'next_text'  => 'Next &raquo;',
        'type'       => 'plain',
    ]);
    ?>
</div>

<?php if (current_user_can('administrator')){ ?>
	
	<div class="bulk-controls" style="display: flex;gap: 20px;">
		<select name="bulk_status" >
			<option value="">Change Status</option>
			<option value="publish">Approved</option>
			<option value="pending">In Review</option>
			<option value="draft">Draft</option>
			<option value="expired">Expired</option>
		</select>

		<input type="date" name="bulk_expiry">
		
		<input type="hidden" name="type" value="yellow">

		<button type="submit" name="bulk_update_ads">
			Update Selected Business
		</button>
	</div>
	<script>
		document.getElementById('select-all').addEventListener('change', function () {
			document.querySelectorAll('input[name="ad_ids[]"]').forEach(cb => {
				cb.checked = this.checked;
			});
		});
	</script>
	<?php } ?>


</div>
<script>
document.addEventListener("DOMContentLoaded", function() {
    const buttons = document.querySelectorAll(".multi-select-dropdown .dropdown-btn");

    buttons.forEach(btn => {
        btn.addEventListener("click", function(e) {
            e.stopPropagation();
            const dropdown = btn.nextElementSibling;
            dropdown.style.display = 
                dropdown.style.display === "block" ? "none" : "block";
        });
    });

    // Close dropdown when clicking outside
    document.addEventListener("click", function() {
        document.querySelectorAll(".dropdown-content").forEach(el => {
            el.style.display = "none";
        });
    });
});
</script>
