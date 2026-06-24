<?php
if (!defined('ABSPATH')) exit;

// Only logged-in users
if (!is_user_logged_in()) {
    echo "<p>Please login to view your Offer & Deals.</p>";
    return;
}

$user_id = get_current_user_id();

// FIXED PAGINATION — WORKS 100%
$paged = max( 1, get_query_var('paged'), get_query_var('page') );

$posts_per_page = 10;

// === FILTERS ====================================
$search_title = isset($_GET['search_title']) ? sanitize_text_field($_GET['search_title']) : '';
$filter_status = isset($_GET['filter_status']) ? sanitize_text_field($_GET['filter_status']) : '';
$filter_cat = isset($_GET['filter_cat']) ? (array) $_GET['filter_cat'] : [];
$filter_author = isset($_GET['filter_author']) ? intval($_GET['filter_author']) : 0;

// === QUERY ARGS =================================

// Default user filter
$args = [
    'post_type'      => 'offer-deal',
    'post_status' => ['publish','pending','draft','rejected','expired'],
    'posts_per_page' => $posts_per_page,
    'paged'          => $paged,
    'orderby'        => 'date',
    'order'          => 'DESC'
];

if (!current_user_can('administrator')) {
    $args['author'] = $user_id; // normal users see only their event
}

// Search by title
if ($search_title !== '') {
    $args['s'] = $search_title;
}

// Filter by status
if ($filter_status !== '') {
    $args['post_status'] = [$filter_status];
}

// Category filter
if (!empty($filter_cat)) {
    $args['tax_query'] = [
        [
            'taxonomy' => 'business-category',
            'field'    => 'term_id',
            'terms'    => array_map('intval', $filter_cat)
        ]
    ];
}

// Admin filter by author
if (current_user_can('administrator') && $filter_author > 0) {
    $args['author'] = $filter_author;
}

$ads = new WP_Query($args);
?>

<style>
.multi-select-dropdown { position:relative; width:200px; }
.dropdown-btn { padding:8px; border:1px solid #ccc; cursor:pointer; background:white; }
.dropdown-content {
    display:none; position:absolute; top:40px; left:0; width:100%;
    max-height:200px; overflow-y:auto;
    border:1px solid #ccc; background:white; z-index:20; padding:10px;
}
.dropdown-item { display:block; margin-bottom:6px; }
</style>

<script>
document.addEventListener("DOMContentLoaded", function(){
    document.querySelectorAll(".dropdown-btn").forEach(btn=>{
        btn.addEventListener("click", function(e){
            e.stopPropagation();
            let dd = this.nextElementSibling;
            dd.style.display = (dd.style.display==="block") ? "none" : "block";
        });
    });

    document.addEventListener("click", function(){
        document.querySelectorAll(".dropdown-content").forEach(el=>el.style.display="none");
    });
});
</script>


<div class="bads">

    <!-- FILTER FORM -->
    <form method="get" style="margin-bottom:20px; display:flex; gap:20px; flex-wrap:wrap;">
<div class='filters-row' style='display:flex;gap:10px;align-items:center;'>

        <!-- TEXT SEARCH -->
        <input type="text" name="search_title" placeholder="Search Title..." 
               value="<?php echo esc_attr($search_title); ?>"
               style="padding:8px;width:200px;">

        <!-- CATEGORY FILTER -->
        <div class="multi-select-dropdown">
            <div class="dropdown-btn">Select Categories</div>
            <div class="dropdown-content">
                <?php
                $terms = get_terms(['taxonomy'=>'business-category','hide_empty'=>false]);

                foreach ($terms as $t): ?>
                    <label class="dropdown-item">
                        <input type="checkbox" 
                               name="filter_cat[]" 
                               value="<?php echo $t->term_id; ?>"
                               <?php checked(in_array($t->term_id, $filter_cat)); ?>>
                        <?php echo $t->name; ?>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- STATUS FILTER -->
        <div>
            <select name="filter_status" style="padding:8px;height:40px;">
				<option value="">All Status</option>
                <option value="publish" <?= selected($filter_status, 'publish', false); ?>>Approved</option>
				<option value="pending" <?= selected($filter_status, 'pending', false); ?>>In Review</option>
				<option value="draft" <?= selected($filter_status, 'draft', false); ?>>Draft</option>

				<option value="rejected" <?= selected($filter_status, 'rejected', false); ?>>Rejected</option>
				<option value="expired" <?= selected($filter_status, 'expired', false); ?>>Expired</option>

            </select>
        </div>

        <!-- ADMIN ONLY AUTHOR FILTER -->
        <div>
        <?php if (current_user_can('administrator')): ?>
            <select name="filter_author" style="padding:8px;height:40px;">
                <option value="0">All Authors</option>
                <?php
                $authors = get_users(['role__in'=>['administrator','author','subscriber']]);
                foreach ($authors as $a):
                ?>
                    <option value="<?php echo $a->ID; ?>" 
                        <?php selected($filter_author, $a->ID); ?>>
                        <?php echo $a->display_name; ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>

        <button type="submit" style="background:#0073aa;color:#fff;border:none;cursor:pointer;">Filter</button>
        <a href="<?php echo $siteurl; ?>/dashboard/offer-deals/" class="btn reset">Reset</a>
    </div>
</form>

    <!-- SUCCESS MSG -->
    <?php 
    if (isset($_GET['msg'])) {
        echo '<div style="clear:both;"></div><div style="padding:10px;background:#d4edda;color:#155724;margin-bottom:20px;">';
        echo ($_GET['msg']=='added') ? 'Offer & Deals Added Successfully (Pending Review)' : '';
		echo ($_GET['msg']=='added_admin') ? 'Offer & Deals Added Successfully.' : '';
        echo '</div>';
    }
	
	
    ?>
	<?php if (isset($_GET['updated'])): ?>
		<div class="notice success">Offer & Deals updated successfully.</div>
	<?php endif; ?>
	<form method="post" id="bulk-update-form" style="width:100%;">
	<?php wp_nonce_field('bulk_ads_update','bulk_ads_nonce'); ?>
    <!-- TABLE -->
    <table style="width:100%;border-collapse:collapse;margin-top:20px;">
        <thead>
			
            <tr style="background:#f4f4f4;border-bottom:2px solid #ccc;">
				<?php if (current_user_can('administrator')){ ?>
				<th><input type="checkbox" id="select-all"></th>
				<?php } ?>
                <th style="padding:10px;border:1px solid #ddd;">Title</th>
                <th style="padding:10px;border:1px solid #ddd;">Categories</th>
                <th style="padding:10px;border:1px solid #ddd;">Start Date</th>
                <th style="padding:10px;border:1px solid #ddd;">End Date</th>
                <th style="padding:10px;border:1px solid #ddd;">Status</th>
                <th style="padding:10px;border:1px solid #ddd;">Action</th>
            </tr>
        </thead>

        <tbody>

        <?php if ($ads->have_posts()) : while ($ads->have_posts()) : $ads->the_post(); 
            $post_id = get_the_ID();

            $cats = wp_get_post_terms($post_id, 'business-category', ['fields'=>'names']);
			
            $location = get_post_meta($post_id, 'location', true);
			$start_date = get_post_meta($post_id, 'start_date', true);
			if ($start_date) {

				// If format is 20260220 (8 digits, no dash)
				if (preg_match('/^\d{8}$/', $start_date)) {
					$start_date = DateTime::createFromFormat('Ymd', $start_date)->format('Y-m-d');
				}
			  
			}
			$end_date = get_post_meta($post_id, 'end_date', true);
			if ($end_date) {

				// If format is 20260220 (8 digits, no dash)
				if (preg_match('/^\d{8}$/', $end_date)) {
					$end_date = DateTime::createFromFormat('Ymd', $end_date)->format('Y-m-d');
				}
			  
			}
			//$event_type = get_post_meta($post_id, 'event_type', true);
			$featured = get_post_meta($post_id, 'featured', true);
           
			
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
				$status_label = '<span class="status-label status-expired">Expired</span><br/> <br/><a class="button-renew" href="#">Renew Now</a>';
			} else {
				$status_label = ucfirst($status);
			}
			
        ?>

            <tr>
				<?php if (current_user_can('administrator')){ ?>
				<td  style="padding:10px;border:1px solid #ddd;">
                <input type="checkbox" name="ad_ids[]" value="<?php echo esc_attr($post_id); ?>">
				</td>
				<?php } ?>
                <td style="padding:10px;border:1px solid #ddd;"><strong><?php the_title(); ?></strong></td>
                <td style="padding:10px;border:1px solid #ddd;"><?php echo implode(', ', $cats); ?></td>
                <td style="padding:10px;border:1px solid #ddd;"><?php echo $start_date; ?></td>
                <td style="padding:10px;border:1px solid #ddd;"><?php echo $end_date; ?></td>
                
                <td style="padding:10px;border:1px solid #ddd;width:120px;"><?= $status_label; ?></td>
                <td style="padding:10px;border:1px solid #ddd;">
                    <a class="button" href="add/?edit=<?php echo $post_id; ?>">Edit</a>
                </td>
            </tr>

        <?php endwhile; else: ?>

            <tr>
                <td colspan="7" style="padding:20px;text-align:center;border:1px solid #ddd;">
                    No Offer & Deals found.
                </td>
            </tr>

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
            'total'      => $ads->max_num_pages,
            'current'    => $paged,

            // FORCE `?paged=2` ALWAYS
            'base'       => add_query_arg('paged', '%#%'),
            'format'     => '',
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

		<input type="hidden" name="bulk_expiry" >
		<input type="hidden" name="type" value="offer-deal">

		<button type="submit" name="bulk_update_ads">
			Update Selected Offer & Deals.
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
</form>

</div>

<?php wp_reset_postdata(); ?>