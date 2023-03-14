<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
global $wpdb;
if ( isset( $_GET['sid'] ) && ! empty( $_GET['sid'] ) ) {
	$data    = $wpdb->get_row( $wpdb->prepare( "select u.display_name, d.product_id from {$wpdb->prefix}dbargain_reports d join {$wpdb->prefix}users u on d.user_id = u.ID where d.session_id=%s", intval( $_GET['sid'] ) ), ARRAY_A );
	$product = wc_get_product( $data['product_id'] );

	$image = wp_get_attachment_image_src( get_post_thumbnail_id( $data['product_id'] ), 'product' );
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">Session Chat History of <?php echo esc_attr( $data['display_name'] ); ?></h1>

		<div id="poststuff">
			<div id="post-body" class="metabox-holder columns-1">
				<div id="post-body-content">
					<table class='wp-list-table widefat fixed striped table-view-list offers' border='0' width='100%'>
						<tr>
							<td colspan="3">
								<table class='wp-list-table widefat fixed table-view-list offers' border='0'
									   width='100%'>
									<tr>
										<td width="10%"><img src="<?php echo esc_attr( $image[0] ); ?>" style="max-width: 100px"
															 class="img-responsive"></td>
										<td width="5%">#<?php echo esc_attr( $data['product_id'] ); ?></td>
										<td width="12%"><b><?php echo esc_attr( $product->get_title() ); ?></b></td>
										<td width="70%"><?php echo esc_attr( wc_price( $product->get_regular_price() ) ); ?></td>
									</tr>
								</table>
							</td>
						</tr>
						<?php
						if ( isset( $_GET['sid'] ) && ! empty( $_GET['sid'] ) ) {
							$messages = $wpdb->get_results( $wpdb->prepare( "select * from {$wpdb->prefix}dbargain_session where session_id = %d", intval( $_GET['sid'] ) ), ARRAY_A );
							foreach ( $messages as $msg ) {
								?>
								<tr>
									<td><?php echo esc_attr( ( isset( $msg['message'] ) && ! empty( $msg['message'] ) ) ? 'System: ' : esc_attr( $data['display_name'] ) ); ?></td>
									<td><?php echo esc_attr( ( ! empty( $msg['offer'] ) && $msg['offer'] > 0 ) ? esc_attr( wc_price( $msg['offer'] ) ) : esc_attr( $msg['message'] ) ); ?></td>
									<td><?php echo esc_attr( $msg['date_created'] ); ?></td>
								</tr>
								<?php
							}
						}
						?>
					</table>
				</div>
				<button type="button" class="button button-primary" onclick="window.location.href='?page=dbargain'">
					Back
				</button>
			</div>
		</div>
	</div>
	<?php
} else {
	?>
	<div class="wrap">
		<h1 class="wp-heading-inline">Chat Data Not Found</h1>
	</div>
	<?php
}
?>
