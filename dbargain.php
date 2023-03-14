<?php
/**
 * @package select2
 */
/**
 * Plugin Name: D-Bargain
 * Plugin URI: http://cloudprimero.com/
 * Description: This Plugin is used for product price bargaining
 * Version: 3.0.0
 * Author: Cloud Primero B.V.
 * Author URI: http://cloudprimero.com/
 * Developer: Waqar Muhammad Irfan/Cloud Primero B.V
 * Developer URI: http://cloudprimero.com/
 * Text Domain: DBargain.
 *
 * Woo: 7551266:16f2e53d68da13623deffd02e00cdab2
 * WC requires at least: 3.4.2
 * WC tested up to: 5.0.0
 *
 * License: GPLv3 or later
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// constants
define( 'DBARGAIN_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );
global $isMerchantIdValid;

// require the main file for the plugin logics
require_once DBARGAIN_PLUGIN_PATH . 'include/class-dbargain.php';

// Test to see if WooCommerce is active (including network activated).
$plugin_path = trailingslashit( WP_PLUGIN_DIR ) . 'woocommerce/woocommerce.php';

// if ( in_array( $plugin_path, wp_get_active_and_valid_plugins() )
// 	|| in_array( $plugin_path, wp_get_active_network_plugins() )
// ) {

if ( in_array( $plugin_path, wp_get_active_and_valid_plugins() )) {


	// activation
	register_activation_hook( __FILE__, array( 'DBargain', 'activate' ) );

	// deactivation
	register_deactivation_hook( __FILE__, array( 'DBargain', 'deactivate' ) );

	$dbargain = new DBargain();
	//start custom session
	add_action( 'init', array( $dbargain, 'start_session' ), 1 );
	//add_action( 'init', array( $dbargain, 'dbargain_send_emails' ), 2 );

	

	/*
	 * Add delete post ability
	   add_action('plugins_loaded', 'wporg_add_delete_post_ability');
	*/
}


/*
function wporg_add_delete_post_ability() {
	if ( current_user_can( 'edit_others_posts' ) ) {

		add_filter( 'the_content', 'wporg_generate_delete_link' );


		add_action( 'init', 'wporg_delete_post' );
	}
}


function wporg_delete_post() {
	if ( isset( $_GET['action'] )
		&& isset( $_GET['nonce'] )
		&& 'wporg_frontend_delete' === $_GET['action']
		&& wp_verify_nonce( $_GET['nonce'], 'wporg_frontend_delete' ) ) {

		// Verify we have a post id.
		$post_id = ( isset( $_GET['post'] ) ) ? ( $_GET['post'] ) : ( null );

		// Verify there is a post with such a number.
		$post = get_post( (int) $post_id );
		if ( empty( $post ) ) {
			return;
		}

		// Delete the post.
		wp_trash_post( $post_id );

		// Redirect to admin page.
		$redirect = admin_url( 'edit.php' );
		wp_safe_redirect( $redirect );

		// We are done.
		die;
	}
}


/**
 * Generate a Delete link based on the homepage url.
 *
 * @param string $content   Existing content.
 *
 * @return string|null
 *//*
function wporg_generate_delete_link( $content ) {
	// Run only for single post page.
	if ( is_single() && in_the_loop() && is_main_query() ) {
		// Add query arguments: action, post, nonce
		$url = add_query_arg(
			[
				'action' => 'wporg_frontend_delete',
				'post'   => get_the_ID(),
				'nonce'  => wp_create_nonce( 'wporg_frontend_delete' ),
			], home_url()
		);

		return $content . ' <a href="' . esc_url( $url ) . '">' . esc_html__( 'Delete Post', 'wporg' ) . '</a>';
	}

	return null;
}*/
