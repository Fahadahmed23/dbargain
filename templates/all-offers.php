<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
// require the All Offers class which extends WP_LIST_TABLE class to render WP default list view
//require_once WP_PLUGIN_DIR . '/d-bargain/include/class-alloffers.php';
require_once DBARGAIN_PLUGIN_PATH . 'include/class-alloffers.php';


$all_offers = new All_Offers();
?>
<div class="wrap">
	<h1 class="wp-heading-inline">All Offers</h1>
	<div id="poststuff">
		<div id="post-body" class="metabox-holder columns-1">
			<div id="post-body-content">
				<div class="meta-box-sortables ui-sortable">
					<form method="post">
						<?php
						$all_offers->prepare_items();
						$all_offers->display();
						?>
					</form>
				</div>
			</div>
		</div>
		<br class="clear">
	</div>
</div>

