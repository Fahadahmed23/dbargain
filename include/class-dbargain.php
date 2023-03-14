<?php

/**
 * Summary.
 *
 * Description: Main class for handling all functionalities and working of D-Bargain plugin
 *
 **/

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class DBargain {

	/*
	function __construct() {
				return $this;
	}
	*/

	
	public static function activate() {
		flush_rewrite_rules();

		global $pt_db_version, $wpdb;
		$pt_db_version = '1.0';

		$dbargain_table_name = $wpdb->prefix . 'dbargain_reports';
		$session_table_name  = $wpdb->prefix . 'dbargain_session';
		$response_table_name = $wpdb->prefix . 'dbargain_responses';

		$charset_collate = $wpdb->get_charset_collate();

		$sql_dbargain = "CREATE TABLE IF NOT EXISTS $dbargain_table_name (
                                ID mediumint(9) NOT NULL AUTO_INCREMENT,
                                session_id varchar(128) NULL,
                                user_id mediumint(9) NULL,
                                product_id mediumint(9) NOT NULL,
                                quantity mediumint(9) NOT NULL,
                                order_price float(5,2) NULL,
                                date_created datetime NOT NULL,
                                PRIMARY KEY (ID)
                            ) $charset_collate;";

		$sql_session = "CREATE TABLE IF NOT EXISTS $session_table_name (
                                ID mediumint(9) NOT NULL AUTO_INCREMENT,
                                session_id VARCHAR(128) NOT NULL,
                                product_id mediumint(9) NOT NULL,
                                offer float(6,2) NULL,
                                message text NULL,
                                status tinyint(4) NOT NULL,
                                date_created datetime NOT NULL,
                                PRIMARY KEY (ID)
                            ) $charset_collate;";

		$sql_responses = "CREATE TABLE IF NOT EXISTS $response_table_name (
                                ID mediumint(9) NOT NULL AUTO_INCREMENT,
                                percentage_difference mediumint(4) NULL,
                                `condition` VARCHAR(128) NULL,
                                message text NOT NULL,
                                PRIMARY KEY (ID)
                            ) $charset_collate;";

		include_once ABSPATH . 'wp-admin/includes/upgrade.php';
		//echo $sql_project;die;
		dbDelta( $sql_dbargain );
		dbDelta( $sql_session );
		dbDelta( $sql_responses );

		$wpdb->query( "delete from {$wpdb->prefix}dbargain_responses" );
		$wpdb->query(
			"insert  into {$wpdb->prefix}dbargain_responses(`ID`,`percentage_difference`,`condition`,`message`) values
						(1,10,'less','Your offer is very good but you are just slightly under our accepted limit. Please try again'),
						(2,20,'less','You are getting closer but still you need to give a better offer'),(3,30,'less','We don\'t wish to be rude but this product is worth a bit more than what you are offering'),
						(4,40,'less','The offer doesn\'t match with the worth of product. Please improve'),
						(5,50,'less','You need to give a better offer than this'),
						(6,60,'less','Please make a serious offer considering the worth of the product.'),
						(7,NULL,'more','Thankyou for your enthusiasm but the offer you are making, is above the product price itself. You can buy the product on original price'),
						(8,NULL,'success','Success!! Thankyou for the great offer. You can buy the product on your offered price'),
						(9,NULL,'warning','You have exhausted all your attempts. Considering your interest and effort, we are going to grant you one last chance. Please give us your best offer this time.'),
						(10,NULL,'welcome','Welcome, Thankyou for taking interest in this product. Please give us your best offer.'),
						(11,NULL,'failure','We are sorry but you have exhausted all your chances. Considering your interest and effort, we can let you buy this product on a discounted price still. Our final price for you is: ');"
		);

		add_option( 'pt_db_version', $pt_db_version );

		$current_user       = wp_get_current_user();
		$current_user_email = $current_user->user_email;
		$current_user_name  = $current_user->first_name;
		$domain             = home_url( '/' );
		$xml                = http_build_query(
			array(
				'user_name' => $current_user_name,
				'domain'    => $domain,
				'email'     => $current_user_email,
			)
		);

		$add = 'api/auth/register';

		$url = 'https://kz3r4ehy21.execute-api.us-east-1.amazonaws.com/dev/' . $add;

		$ch = curl_init( $url );

		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $xml );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

		curl_exec( $ch );
		curl_close( $ch );
	}

	public static function deactivate() {
		flush_rewrite_rules();
	}

	public static function uninstall() {
		global $wpdb;

		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'dbargain_reports' );
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'dbargain_session' );
		$wpdb->query( 'DROP TABLE IF EXISTS ' . $wpdb->prefix . 'dbargain_responses' );

		delete_option( 'pt_db_version' );
	}

	public static function register() {

		add_action( 'admin_menu', array( self::class, 'add_admin_pages' ) );
		// Register javascript
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueue_admin_js' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_frontend_js' ) );

		add_action( 'wp_ajax_nopriv_fetch_details', array( self::class, 'fetch_details' ) );
		add_action( 'wp_ajax_fetch_details', array( self::class, 'fetch_details' ) );
		
	
		if(get_option('dbargain_merchant_id')!=false || !empty(get_option('dbargain_merchant_id'))){ 
		
			// The code for displaying WooCommerce Product Custom Fields
			add_action( 'woocommerce_product_options_general_product_data', array( self::class, 'dbargain_fields' ) );
			// Following code Saves  WooCommerce Product Custom Fields
			add_action( 'woocommerce_process_product_meta', array( self::class, 'dbargain_fields_save' ) );

			//Code for adding DBargain button on frontend
			add_action( 'woocommerce_after_add_to_cart_button', array( self::class, 'render_dbargain_window' ) );

			//Code for handling chat
			add_action( 'wp_ajax_make_offer', array( self::class, 'make_offer' ) );
			add_action( 'wp_ajax_nopriv_make_offer', array( self::class, 'make_offer' ) );

			//Code for overriding cart prices
			add_action( 'woocommerce_before_calculate_totals', array( self::class, 'update_cart_prices' ), 10, 1 );

			add_action( 'woocommerce_payment_complete', 'custom_process_order', 10, 1 );

			//Code to unset session data after checkout
			add_action( 'woocommerce_thankyou', array( self::class, 'unset_session' ), 10, 1 );
		}
	
	
	}

	

	public function custom_process_order( $order_id ) {
		if ( isset( $_SESSION['token'] ) ) {
			$order = new WC_Order( $order_id );
			$items = $order->get_items();
			foreach ( $items as $item ) {
				if ( isset( $_SESSION['dbargain'][ $item['product_id'] ]['dbargain_price'] ) ) {
					$data = http_build_query(
						array(
							'order_data' => array(
								'session_id'  => $_SESSION['dbargain'][ $item['product_id'] ]['session_id'],
								'order_id'    => $order_id,
								'product_id'  => $item['product_id'],
								'final_price' => $_SESSION['dbargain'][ $item['product_id'] ]['dbargain_price'],
								'status'      => 1,
							),
						)
					);
					$add  = 'api/order/store';
					$url  = 'https://kz3r4ehy21.execute-api.us-east-1.amazonaws.com/dev/' . $add;
					$curl = curl_init();
					curl_setopt( $curl, CURLOPT_POST, 1 );
					curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
					curl_setopt( $curl, CURLOPT_URL, $url );
					curl_setopt(
						$curl,
						CURLOPT_HTTPHEADER,
						array(
							'Authorization: Bearer ' . $_SESSION['token'],
							'Content-Type: application/x-www-form-urlencoded',
						)
					);
					curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
					curl_setopt( $curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
					$result = curl_exec( $curl );

					curl_close( $curl );
				}
			}
		}
	}

	public static function unset_session( $order_id ) {
		if ( isset( $_SESSION['dbargain'] ) ) {
			unset( $_SESSION['dbargain'] );
			session_regenerate_id();
		}
		if ( isset( $_SESSION['offerdata'] ) ) {
			unset( $_SESSION['offerdata'] );
		}
	}

	public static function update_cart_prices( $cart ) {
		// This is necessary for WC 3.0+
		if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
			return;
		}

		// Avoiding hook repetition (when using price calculations for example | optional)
		if ( did_action( 'woocommerce_before_calculate_totals' ) >= 2 ) {
			return;
		}

		// Loop through cart items
		foreach ( WC()->cart->get_cart() as $cart_item ) {
			$id = $cart_item['data']->get_id();

			if ( isset( $_SESSION['dbargain'][ $id ]['dbargain_price'] ) && ! empty( $_SESSION['dbargain'][ $id ]['dbargain_price'] ) ) {
				$cart_item['data']->set_price( $_SESSION['dbargain'][ $id ]['dbargain_price'] );
			}
		}
	}

	public static function update_price_mini_cart( $price_html, $cart_item, $cart_item_key ) {
		$id = $cart_item['data']->get_id();

		if ( isset( $_SESSION['dbargain'][ $id ]['dbargain_price'] ) && ! empty( $_SESSION['dbargain'][ $id ]['dbargain_price'] ) ) {
			$price = array( 'price' => $_SESSION['dbargain'][ $id ]['dbargain_price'] );
			if ( WC()->cart->display_prices_including_tax() ) {
				$product_price = wc_get_price_including_tax( $cart_item['data'], $price );
			} else {
				$product_price = wc_get_price_excluding_tax( $cart_item['data'], $price );
			}
			return wc_price( $product_price );
		}

		return $price_html;
	}

	//start session
	public static function start_session() {
		
		session_start();
		
		// Set the cURL timeout limit to 20 seconds
		add_filter( 'http_request_timeout', function( $timeout ) {
			return 20;
		} );

		global $isMerchantIdValid,$isMerchantTimeValid,$dBargainExpiryDate;
		$isMerchantIdValid = false;
		$isMerchantTimeValid = true;
		$dBargainExpiryDate = '';


		
		if(get_option('dbargain_merchant_id')!=false || !empty(get_option('dbargain_merchant_id'))){

			$dbargainMerchantId =  self::isMerchantIdValid(get_option('dbargain_merchant_id'));
			
			$isMerchantIdValid  = isset($dbargainMerchantId->status) &&  !is_null($dbargainMerchantId->status) && $dbargainMerchantId->status==200 ? true : false;
			
			if($isMerchantIdValid){

				if(isset($dbargainMerchantId->time) &&  !is_null($dbargainMerchantId->time)){

					
					$current_time = time(); // current Unix timestamp
					$other_time = $dbargainMerchantId->time; // Unix timestamp to compare with

					$one_week_after = strtotime("+1 week",$current_time); // Unix timestamp of one week ago from current time
					$one_day_before = strtotime("-1 day",$other_time); 

					if (($other_time - $current_time) <= 86400) {
				
						$isMerchantTimeValid =false;
						$expiry_date = date('Y-m-d',$other_time);
						$dBargainExpiryDate = $expiry_date;
						self::dBargainTimeDuration($expiry_date,'dbargain_email_oneday');
					}
					else if ($other_time <= $one_week_after) {
						$isMerchantTimeValid =false;

						$expiry_date = date('Y-m-d',$other_time);
						$dBargainExpiryDate = $expiry_date;

						self::dBargainTimeDuration($expiry_date,'dbargain_email_sent');

						// if(get_option('dbargain_email_sent')!=false || !empty(get_option('dbargain_email_sent'))){
						// 	$dbargain_email_sent = get_option('dbargain_email_sent');
						// 	if($dbargain_email_sent==0){
						// 		update_option( 'dbargain_email_sent',1, 'yes');
						// 		self::dbargain_send_emails($expiry_date);
						// 	}
						// }
						// else {
						// 	update_option( 'dbargain_email_sent',1,'yes');
						// 	self::dbargain_send_emails($expiry_date);

						// }	

					}
					
				
				}
			}
		}
		

		if ( ! isset( $_SESSION['dbargain'] ) ) {
			$_SESSION['dbargain'] = array();
		}
		if ( ! isset( $_SESSION['offerdata'] ) ) {
			$_SESSION['offerdata'] = array();
		}
		if ( ! isset( $_SESSION['token'] ) ) {
			$_SESSION['token'] = array();
		}
	}


	public static function dBargainTimeDuration($expiry_date,$get_option_email_val) {


		if(get_option($get_option_email_val)!=false || !empty(get_option($get_option_email_val))){
			$dbargain_email_sent = get_option($get_option_email_val);
			if($dbargain_email_sent==0){
				update_option( $get_option_email_val,1, 'yes');
				self::dbargain_send_emails($expiry_date);
			}
		}
		else {
			update_option( $get_option_email_val,1,'yes');
			self::dbargain_send_emails($expiry_date);

		}

	}


	public static function isMerchantIdValid($dbargainMerchantId) {
		// Set the API endpoint URL
		//$api_url = 'https://dbargain.fahsoft.com/wp-json/dbargain/post/membership';
		
		$url = 'wp-json/dbargain/post/membership';

		if (get_option('dbargain_api_base_url')!=false || !empty(get_option('dbargain_api_base_url'))){
			$api_url = trim(get_option('dbargain_api_base_url'));
			$api_url = $api_url.$url;
		}	
		else {
			$api_url = 'https://d-bargain.com/';
			$api_url = $api_url.$url;
		}



		// Set the request arguments
		$args = array(
			'method' => 'POST',
			'body' => array(
				'dbargainMerchantId' => $dbargainMerchantId
			),
		);
	
		// Make the request using the HTTP API
		$response = wp_remote_post($api_url,$args);

		$response_body = wp_remote_retrieve_body( $response );
	
		$data_object = json_decode($response_body);
		return $data_object;
			
	}
	
	
	public static function dbargain_send_emails($other_time) {


		global $isMerchantIdValid;
		
		
		// Check if WooCommerce is active
		if ( ! class_exists( 'WooCommerce' ) ) {
			return;
		}
	
		// Load the WC_Email class
		if ( ! class_exists( 'WC_Email' ) ) {
			include_once( WC()->plugin_path() . '/includes/emails/class-wc-email.php' );
		}
	
	

		// Get the user's email address and name
		$admin_email = get_option('admin_email');

		$admin_user = get_user_by('email', $admin_email);
		$admin_user_name = $admin_user->display_name;
		$user_name = isset($admin_user_name) && !empty($admin_user_name) ? $admin_user_name : 'Admin';

		//$date_format = 'Y-m-d'; // desired date format
		//$expiry_date = date($date_format,$other_time);
		$expiry_date = $other_time;
	
		// Load the email class of woocommerce
		$email_class = WC()->mailer()->emails['WC_Email_Customer_Completed_Order'];
	
		// Set the email subject and heading
		$subject = 'DBargain Subscription Renewal';
		$heading = 'Your Subscription is Expiring Soon';

		//Base color  
		$woocommerce_email_base_color = get_option('woocommerce_email_base_color')!=false || !empty(get_option('woocommerce_email_base_color'))
										? get_option('woocommerce_email_base_color') : '#0071c5'; 

		// Background color  
		$woocommerce_email_background_color = get_option('woocommerce_email_background_color')!=false || !empty(get_option('woocommerce_email_background_color')) 
										? get_option('woocommerce_email_background_color') : '#f7f7f7'; 

		// Body background color		
		$woocommerce_email_body_background_color = get_option('woocommerce_email_body_background_color')!=false || !empty(get_option('woocommerce_email_body_background_color')) 
										? get_option('woocommerce_email_body_background_color') : '#ffffff'; 

		// Body text color  
		$woocommerce_email_text_color = get_option('woocommerce_email_text_color')!=false || !empty(get_option('woocommerce_email_text_color')) 
		? get_option('woocommerce_email_text_color') : '#3c3c3c'; 


		// Set the email body using the custom template
		$body = '<table style="border-collapse: collapse; width: 100%;"><tbody><tr><td style="text-align: center; background-color: {woocommerce_email_base_color}; padding: 20px;"><h2 style="color: {woocommerce_email_text_color};">' . $heading . '</h2></td></tr><tr style="background-color: {woocommerce_email_background_color}; color: {woocommerce_email_text_color};"><td style="padding: 20px;"><p>Dear {user_name},</p><p>Your subscription will be expiring on {expiry_date}. Kindly renew your subscription in order to continue availing the services of DBargain.</p><p>Thank you.</p></td></tr><tr><td style="background-color: {woocommerce_email_background_color}; padding: 20px; text-align: center;"><p style="color: {woocommerce_email_text_color};">DBargain Subscription Renewal</p></td></tr></tbody></table>';
		
		// Replace placeholders in email body
		$body = str_replace('{woocommerce_email_base_color}', $woocommerce_email_base_color, $body);
		$body = str_replace('{woocommerce_email_text_color}', $woocommerce_email_text_color, $body);
		$body = str_replace('{woocommerce_email_background_color}', $woocommerce_email_background_color, $body);
		$body = str_replace('{user_name}', $user_name, $body);
		$body = str_replace('{expiry_date}', $expiry_date, $body);

		// Set the email headers
		$headers = array(
			'Content-Type: text/html; charset=UTF-8',
		);

		// Send the email
		$email_class->send($admin_email,$subject,$body,$headers,array());
	}
	
	//Ajax function to get offer details
	public static function fetch_details() {
		global $wpdb;

		if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), 'offer-details' ) && isset( $_post['id'] ) && ! empty( $_POST['id'] ) ) {
			$id      = sanitize_text_field( $_POST['id'] );
			$product = wc_get_product( $id );

			$data = $wpdb->get_results( $wpdb->prepare( "select distinct user_id from {$wpdb->prefix}dbargain_reports where product_id = %d;", intval( $id ) ), 'ARRAY_A' );

			$image = wp_get_attachment_image_src( get_post_thumbnail_id( intval( $id ) ), 'product' );

			$html = "
                    <table class='wp-list-table widefat fixed table-view-list offers' border='0' width='100%'>
                        <tr>
                            <td width='10%'>
                                <img src='" . $image[0] . "' width='100px'>
                            </td>
                            <td width='11%'>
                                <p>#" . intval( $id ) . '</p>
                                <p><h2>' . $product->get_title() . '</h2></p>
                                <p>' . $product->get_short_description() . '</p>
                                <p>' . wc_price( $product->get_regular_price() ) . "</p>
                            </td>
                            <td width='78%'>
                                <table class='wp-list-table widefat fixed table-view-list offers' border='0' width='100%'>
                                    <tr>
                                        <td style='background-color:#efefef'><b>Customer Name</b></td>
                                        <td style='background-color:#efefef'><b>Bargain Price</b></td>
                                        <td style='background-color:#efefef'><b>Past Orders</b></td>
                                        <td style='background-color:#efefef'><b>Email</b></td>
                                        <td style='background-color:#efefef'><b>Phone</b></td>
                                        <td style='background-color:#efefef'><b>Status</b></td>
                                        <td style='background-color:#efefef'><b>Chat History</b></td>
                                    </tr>";
			foreach ( $data as $d ) {
				$user    = get_user_by( 'id', $d['user_id'] );
				$session = $wpdb->get_row( $wpdb->prepare( "select session_id, order_price from {$wpdb->prefix}dbargain_reports where user_id = %d and product_id = %d order by ID DESC  limit 0,1", intval( $d['user_id'] ), intval( $id ) ), ARRAY_A );
				$html   .= '
                                    <tr>
                                        <td>' . $user->display_name . '</td>
                                        <td>' . ( ( isset( $session['order_price'] ) && ! empty( $session['order_price'] ) ) ? $session['order_price'] : '' ) . '</td>
                                        <td>' . $wpdb->get_var( $wpdb->prepare( "select count(ID) from {$wpdb->prefix}dbargain_reports where user_id = %d and product_id = %d ", intval( $d['user_id'] ), intval( $id ) ) ) . '</td>
                                        <td>' . $user->user_email . '</td>
                                        <td>' . get_user_meta( $d['user_id'], 'user_phone', true ) . '</td>
                                        <td>' . ( ( isset( $session['order_price'] ) && ! empty( $session['order_price'] ) ) ? 'Sold' : 'Failed' ) . "</td>
                                        <td><a href='?page=chat&sid=" . $session['session_id'] . "'>Show Chat</a></td>
                                    </tr>";
			}

			$html .= "
                                </table>
                            </td>
                        </tr>
                        <tr>
                            <td colspan='3' style='text-align:right'><a href='javascript:;' onclick='hide_detail(" . intval( $id ) . ")' class='hide_detail' rel='" . intval( $id ) . "'>Hide Details</a></td>
                        </tr>
                    </table>
                    ";
			wp_send_json_success(
				array(
					'id'   => intval( $id ),
					'data' => $html,
				)
			);
		} else {
			wp_send_json_error(
				array(
					'id'   => '',
					'data' => '',
				)
			);
		}
	}

	/**
	 * Function that will add javascript file for Color Piker.
	 */
	public static function enqueue_admin_js() {
		// Css rules for Color Picker
		wp_enqueue_style( 'wp-color-picker' );
		// Make sure to add the wp-color-picker dependecy to js file
		wp_enqueue_script( 'dbargain_admin_js', plugins_url( 'assets/javascript.js', __FILE__ ), array( 'jquery', 'wp-color-picker' ), '1.0', true );
		wp_enqueue_style( 'jquery-ui' );
		wp_enqueue_script( 'jquery-ui-datepicker', '', array(), '1.0' );
	}

	/**
	 * Function that will add javascript file for DBargain popup.
	 */
	public static function enqueue_frontend_js() {
		wp_enqueue_script( 'dbargain_frontend_js', plugins_url( 'assets/javascript_frontend.js', __FILE__ ), array( 'jquery' ), '1.0', true );
	}

	//Function to create custom fields on product edit page
	public static function dbargain_fields() {
		echo '<div class="product_custom_field">';
		// DBargain Price Threshold Field
		woocommerce_wp_text_input(
			array(
				'id'          => '_dbargain_price_threshold',
				'placeholder' => 'Bargain Price Threshold',
				'label'       => __( 'Bargain Price Threshold', 'woocommerce' ),
				'desc_tip'    => 'true',
			)
		);

		// DBargain Date Start Field
		woocommerce_wp_text_input(
			array(
				'id'          => '_dbargain_date_start',
				'placeholder' => 'Bargain Start Date',
				'class'       => 'datepicker',
				'label'       => __( 'Bargain Start Date', 'woocommerce' ),
				'desc_tip'    => 'true',
			)
		);

		// DBargain Date End Field
		woocommerce_wp_text_input(
			array(
				'id'          => '_dbargain_date_end',
				'placeholder' => 'Bargain End Date',
				'class'       => 'datepicker',
				'label'       => __( 'Bargain End Date', 'woocommerce' ),
				'desc_tip'    => 'true',
			)
		);

		echo '</div>';
	}

	//Function to save custom fields from product edit page
	public static function dbargain_fields_save() {
		global $post_id;

		if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), 'add-post' ) ) {

			// DBargain Price Threshold Field
			if ( isset( $_POST['_dbargain_price_threshold'] ) && ! empty( $_POST['_dbargain_price_threshold'] ) ) {
				$price_threshold = sanitize_text_field( $_POST['_dbargain_price_threshold'] );
				update_post_meta( $post_id, '_dbargain_price_threshold', esc_attr( $price_threshold ) );
			}

			// DBargain Price Threshold Field
			if ( isset( $_POST['_dbargain_date_start'] ) && ! empty( $_POST['_dbargain_date_start'] ) ) {
				$start_date = sanitize_text_field( $_POST['_dbargain_date_start'] );
				update_post_meta( $post_id, '_dbargain_date_start', esc_attr( $start_date ) );
			}

			// DBargain Price Threshold Field
			if ( isset( $_POST['_dbargain_date_end'] ) && ! empty( $_POST['_dbargain_date_end'] ) ) {
				$end_date = sanitize_text_field( $_POST['_dbargain_date_end'] );
				update_post_meta( $post_id, '_dbargain_date_end', esc_attr( $end_date ) );
			}
		}
	}

	//Function to create admin menu
	public static function add_admin_pages() {

		global $isMerchantIdValid;

		//add_menu_page( 'D-Bargain', 'D-Bargain', 'manage_options', 'dbargain', array( self::class, 'all_offers' ), 'dashicons-admin-settings', 100 );
		add_menu_page( 'D-Bargain', 'D-Bargain', 'manage_options', 'dbargain','', 'dashicons-admin-settings', 100 );
		add_submenu_page( 'dbargain', 'Settings', 'Settings', 'manage_options', 'dbargain_settings', array( self::class, 'settings' ) );

		//if(get_option('dbargain_merchant_id')!=false || !empty(get_option('dbargain_merchant_id'))){
		if($isMerchantIdValid){  

			add_submenu_page( 'dbargain', 'All Offers', 'All Offers', 'manage_options', 'dbargain', array( self::class, 'all_offers' ) );
			//add_submenu_page('dbargain', 'Reports', 'Reports', 'manage_options', 'reports', [self::class, 'reports']);
			add_submenu_page( '', 'Session Chat History', 'Session Chat History', 'manage_options', 'chat', array( self::class, 'chat' ) );
	
		}
	}

	//Function to render all offers admin menu page template
	public static function all_offers() {
		include_once DBARGAIN_PLUGIN_PATH . 'templates/all-offers.php';
	}

	//Function to render settings admin menu page template
	public static function settings() {
		global $isMerchantIdValid,$isMerchantTimeValid,$dBargainExpiryDate;
		include_once DBARGAIN_PLUGIN_PATH . 'templates/settings.php';
	}

	//Function to render reports admin menu page template
	public static function reports() {
		include_once DBARGAIN_PLUGIN_PATH . 'templates/reports.php';
	}

	//Function to render chat history admin menu page template
	public static function chat() {
		include_once DBARGAIN_PLUGIN_PATH . 'templates/chat.php';
	}

	//Function to render DBargain Window
	public static function render_dbargain_window() {
		global $wpdb, $product;
		$id = $product->get_id();

		$session_upper_limit = get_option( 'dbargain_session_upper_limit' );
		$session_lower_limit = get_option( 'dbargain_session_lower_limit' );
		$bg_color            = get_option( 'dbargain_bg_color' );
		$txt_color           = get_option( 'dbargain_txt_color' );
		$btn_color           = get_option( 'dbargain_btn_color' );
		$heading_font        = get_option( 'dbargain_heading_font' );
		$text_font           = get_option( 'dbargain_text_font' );
		$button_font         = get_option( 'dbargain_button_font' );
		$label_font          = get_option( 'dbargain_label_font' );
		$window_layout       = get_option( 'dbargain_window_layout' );
		$display_criteria    = get_option( 'dbargain_display_criteria' );
		$window_delay        = get_option( 'dbargain_window_delay' );
		$window_chat_delay   = get_option( 'dbargain_window_chat_delay' );
		$global_threshold    = get_option( 'dbargain_threshold' );
		$global_start        = get_option( 'dbargain_start_date' );
		$global_end          = get_option( 'dbargain_end_date' );
		$agent_name          = get_option( 'dbargain_agent_name', 'Jone D' );

		$threshold  = get_post_meta( $id, '_dbargain_price_threshold', true );
		$start_date = get_post_meta( $id, '_dbargain_date_start', true );
		$end_date   = get_post_meta( $id, '_dbargain_date_end', true );

		if ( '' == $session_upper_limit ) {
			$session_upper_limit = '5';
		}
		if ( '' == $session_lower_limit ) {
			$session_lower_limit = '3';
		}

		if ( ! isset( $_SESSION['dbargain'][ $id ] ) || ! is_array( $_SESSION['dbargain'][ $id ] ) ) {
			$_SESSION['dbargain'][ $id ] = array(
				'attempts'   => rand( $session_lower_limit, $session_upper_limit ),
				'session_id' => session_id(),
			);

			$message = $wpdb->get_var( "select message from {$wpdb->prefix}dbargain_responses where `condition` = 'welcome' order by rand() limit 1" );

			if ( empty( $message ) ) {
				$message = 'Welcome, Thankyou for taking interest in this product. Please give us your best offer.';
			}

			$wpdb->insert(
				$wpdb->prefix . 'dbargain_reports',
				array(
					'session_id'   => $_SESSION['dbargain'][ $id ]['session_id'],
					'product_id'   => $id,
					'user_id'      => get_current_user_id(),
					'date_created' => 'now()',
				)
			);
			$wpdb->insert(
				$wpdb->prefix . 'dbargain_session',
				array(
					'session_id'   => $_SESSION['dbargain'][ $id ]['session_id'],
					'product_id'   => $id,
					'message'      => $message,
					'status'       => '0',
					'date_created' => 'NOW()',
				)
			);
		}
		$data = $wpdb->get_results( $wpdb->prepare( "select * from {$wpdb->prefix}dbargain_session where session_id = %s and product_id = %d", $_SESSION['dbargain'][ $id ]['session_id'], intval( $id ) ), ARRAY_A );

		$html = '<style>
                    /* The Modal (background) */
                    .modal {
                    display: none; /* Hidden by default */
                    position: fixed; /* Stay in place */
                    z-index: 1; /* Sit on top */
                    left: 0;
                    top: 0;
                    width: 100%; /* Full width */
                    height: 100%; /* Full height */
                    /* overflow: auto; Enable scroll if needed */
                    background-color: rgb(0,0,0); /* Fallback color */
                    background-color: rgba(0,0,0,0.4); /* Black w/ opacity */
                    }

                    /* Modal Content/Box */
                    .modal-content {
                    background-color: #fcfcfc;
                    margin: 10% auto; /* 15% from the top and centered */
                    border: 1px solid #888;
                    width: 50%; /* Could be more or less, depending on screen size */
                    }

                    /* The Close Button */
                    .close {
                    color: #aaa;
                    float: right;
                    font-size: 28px;
                    font-weight: bold;
                    }

                    .close:hover,
                    .close:focus {
                    color: black;
                    text-decoration: none;
                    cursor: pointer;
                    }
                    @charset "utf-8";
                    /* CSS Document */

                    #chat_window{
                        height: 300px;
                        background-color: #fcfcfc;
                        color: #85be54;
                        padding: 2%;
                        margin: 2%;
                        border: #85be54 solid;
                    }
                    #chat_window img{
                        width: 100px;
                        height: 100px;
                        margin: 0 auto;
                        margin-top: 23px;
                        border: #85be54 solid;
                        padding: 9px;
                        border-radius: 60px;
                    }
                    #chat_window h1{
                        font-size: 40px;
                        text-align: center;
                        font-weight: bold;
                    }
                    #chat_window p{
                        font-size: 20px;
                        text-align: center;
                        font-weight: bold;
                        color: #9c9c9c;
                    }
                    
                
                    @media only screen and (max-width: 600px) {
                        .modal-content {
                            width: 100%;
                        }
                    }
                    /* ---------- GENERAL ---------- */

                    body {
                        background: #e9e9e9;
                        color: #9a9a9a;
                        font: 100%/1.5em "Droid Sans", sans-serif;
                        margin: 0;
                    }

                    a { text-decoration: none; }

                    fieldset {
                        border: 0;
                        margin: 0;
                        padding: 0;
                    }

                    h4, h5 {
                        line-height: 1.5em;
                        margin: 0;
                    }

                    hr {
                        background: #e9e9e9;
                        border: 0;
                        -moz-box-sizing: content-box;
                        box-sizing: content-box;
                        height: 1px;
                        margin: 0;
                        min-height: 1px;
                    }

                    img {
                        border: 0;
                        display: block;
                        height: auto;
                        max-width: 100%;
                    }

                    input {
                        border: 0;
                        color: inherit;
                        font-family: inherit;
                        font-size: 100%;
                        line-height: normal;
                        margin: 0;
                    }

                    p { margin: 0; }

                    .clearfix { *zoom: 1; } /* For IE 6/7 */
                    .clearfix:before, .clearfix:after {
                        content: "";
                        display: table;
                    }
                    .clearfix:after { clear: both; }

                    /* ---------- LIVE-CHAT ---------- */

                    #db_live-chat {
                        bottom: 0;
                        font-size: 12px;
                        right: 24px;
                        position: fixed;
                        width: 300px;
                        z-index: 100000;
                    }
                

                    #db_live-chat header {
                        background: #293239;
                        border-radius: 5px 5px 0 0;
                        color: #fff;
                        cursor: pointer;
                        padding: 16px 24px;
                    }

                    #db_live-chat h4:before {
                        background: #1a8a34;
                        border-radius: 50%;
                        content: "";
                        display: inline-block;
                        height: 8px;
                        margin: 0 8px 0 0;
                        width: 8px;
                    }

                    #db_live-chat h4 {
                        font-size: 12px;
                    }

                    #db_live-chat h5 {
                        font-size: 10px;
                    }

                    #db_live-chat form, #db_live-chat .form {
                        padding: 24px;
                    }

                    #db_live-chat input[type="text"],#db_live-chat input[type="number"] {
                        border: 1px solid #ccc;
                        border-radius: 3px;
                        padding: 8px;
                        outline: none;
                        width: 130px;
                    }

                    .chat-message-counter {
                        background: #e62727;
                        border: 1px solid #fff;
                        border-radius: 50%;
                        display: none;
                        font-size: 12px;
                        font-weight: bold;
                        height: 28px;
                        left: 0;
                        line-height: 28px;
                        margin: -15px 0 0 -15px;
                        position: absolute;
                        text-align: center;
                        top: 0;
                        width: 28px;
                    }

                    .chat-close {
                        background: #1b2126;
                        border-radius: 50%;
                        color: #fff;
                        display: block;
                        float: right;
                        font-size: 10px;
                        height: 16px;
                        line-height: 16px;
                        margin: 2px 0 0 0;
                        text-align: center;
                        width: 16px;
                    }

                    .chat {
                        background: #fff;
                    }

                    .chat-history {
                        height: 252px;
                        padding: 8px 24px;
                        overflow-y: scroll;
                    }

                    .chat-message {
                        margin: 16px 0;
                    }

                    .chat-message img {
                        border-radius: 50%;
                        float: left;
                    }
                    .chat-message-content {
                        margin-left: 56px;
                    }
                    .chat-time {
                        float: right;
                        font-size: 10px;
                    }
                    .chat-feedback {
                        font-style: italic;	
                        margin: 0 0 0 80px;
                    }
                    </style>
                    ';

		$html .= ' <!-- Trigger/Open The Modal -->
                        <button type="button" class="single_add_to_cart_button button alt" id="btn" style="display:none;' . ( $btn_color ? 'background-color:' . $btn_color . ';' : '' ) . ( $button_font ? 'font-family:' . $button_font . ';' : '' ) . '">Open DBargain Window</button>
                        <!-- The Modal -->
                        <input type="hidden" id="time_limit" name="time_limit" value="' . ( ( ! empty( $window_delay ) && in_array( 'delay', $display_criteria ) ) ? $window_delay : '0' ) . '">
                        <input type="hidden" id="time_chat_limit" name="time_chat_limit" value="' . ( ( ! empty( $window_chat_delay ) && in_array( 'delay', $display_criteria ) ) ? $window_chat_delay : '0' ) . '">
                        <input type="hidden" id="exit" name="exit" value="' . ( ( ! empty( $display_criteria ) && in_array( 'exit', $display_criteria ) ) ? 'exit' : '' ) . '">
                        <input type="hidden" id="window_layout" name="window_layout" value="' . ( ! empty( $window_layout ) ? $window_layout : 'popup' ) . '">
                        <input type="hidden" name="ajax_url" id="ajax_url" value="' . admin_url( 'admin-ajax.php' ) . '">
                        <input type="hidden" name="session_id" id="session_id" value="' . $_SESSION['dbargain'][ $id ]['session_id'] . '">
                        <input type="hidden" name="product_id" id="product_id" value="' . $id . '">

                        <div id="db_live-chat" style="display:none">
                            <header class="clearfix">
                                <a href="javascript:;" class="chat-close">-</a>
                                <h4>' . $agent_name . '</h4>
                            </header>
                            <div class="chat">
                                <div class="chat-history" id="db_chat-history">
                                ';

		foreach ( $data as $msg ) {
			if ( ! empty( $msg['message'] ) ) {

				$html .= self::chat_message_response( $agent_name, $msg['message'], $msg['offer'] );
			} else {
				$html .= self::chat_message_response( $agent_name, wc_price( $msg['offer'] ), $msg['offer'] );
			}
		}

		$html .= '

                                </div> <!-- end chat-history -->

                                <div class="form"> <!-- form -->
									<input type="hidden" name="nonce" id="nonce" value="' . wp_create_nonce( 'offer' ) . '" >
                                    <fieldset>
                                ';
		if ( ! isset( $_SESSION['dbargain'][ $id ]['dbargain_price'] ) || empty( $_SESSION['dbargain'][ $id ]['dbargain_price'] ) ) {
			$html .= '
                                                    <input type="number" name="offer" id="offer" value="" placeholder="Make your best offer" Offer>
                                                    <button type="button" class="button alt" id="make_offer" style="background-color: #293239;position: absolute;padding: 6px;color: #ffffff;">Submit Offer</button>
                                                    <button type="submit" name="add-to-cart" value="' . $id . '" class="single_add_to_cart_button button alt" id="db_add_to_cart" style="display:none;background-color: #293239;position: absolute;padding: 6px;color: #ffffff;margin: 0;">Add to Cart</button>
                                            ';
		} else {
			$html .= '
                                                    <input type="number" name="offer" id="offer" value="" placeholder="Make your best offer" style="display:none;float: left; width: 60%; margin-right: 4px">
                                                    <button type="button" class="button alt" id="make_offer" style="background-color: #293239;position: absolute;padding: 6px;color: #ffffff;">Submit Offer</button>
                                                    <button type="submit" name="add-to-cart" value="' . $id . '" class="single_add_to_cart_button button alt" id="db_add_to_cart" style="display:none;background-color: #293239;position: absolute;padding: 6px;color: #ffffff;margin: 0;">Add to Cart</button>
                                            ';
		}

		$html .= ' </fieldset>

                                </div> <!-- form -->
                            </div> <!-- end chat -->

                        </div> <!-- end db_live-chat -->

                        <div id="myModal" class="modal">

                        <!-- Modal content -->
                        <div class="modal-content">
                            <!-- <span class="close">&times;</span> -->
                            <div id="chat_window" >
                                <img src="' . plugin_dir_url( __FILE__ ) . 'assets/cart_img.png" />
                                <h1> Hey Wait!</h1>
                                <p>You can negotiate the price, our representative will join you soon!</p>
                                ';
		$html .= '      </div>
                        </div>
                        </div>  ';
		//show modal only on product detail page
		if ( is_product() && ( ( ! empty( $global_threshold ) && $global_threshold > 0 && ! empty( $global_start ) && strtotime( $global_start ) < time() && ! empty( $global_end ) && strtotime( $global_end ) > time() ) || ( ! empty( $threshold ) && $threshold > 0 && ! empty( $start_date ) && strtotime( $start_date ) < time() && ! empty( $end_date ) && strtotime( $end_date ) > time() ) ) ) {
			echo esc_html( $html );
		}
	}

	//Ajax chat handler
	public static function make_offer() {

		if ( ! wp_verify_nonce( sanitize_text_field( $_REQUEST['nonce'], 'offer' ) ) ) {
			$button_status = 'false';
			$chat_status   = 'true';

			wp_send_json(
				array(
					'data'          => '',
					'button_status' => $button_status,
					'chat_status'   => $chat_status,
				)
			);
		}

		$offerdata = array();
		$domain    = home_url( '/' );
		$data      = http_build_query( array( 'domain' => $domain ) );
		$add       = 'api/auth/login';
		$url       = 'https://kz3r4ehy21.execute-api.us-east-1.amazonaws.com/dev/' . $add;
		$ch        = curl_init( $url );

		curl_setopt( $ch, CURLOPT_POST, 1 );
		curl_setopt( $ch, CURLOPT_POSTFIELDS, $data );
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true );

		$result            = curl_exec( $ch );
		$result            = json_decode( $result, true );
		$token             = $result['data']['access_token'];
		$_SESSION['token'] = $token;
		curl_close( $ch );

		try {
			// run your code here

			global $wpdb;

			$text_font  = get_option( 'dbargain_text_font' );
			$html       = '';
			$agent_name = get_option( 'dbargain_agent_name', 'Jone D' );
			$pid        = sanitize_text_field( $_POST['product_id'] );
			$offer      = sanitize_text_field( $_POST['offer'] );

			if ( ! isset( $_SESSION['dbargain'][ $_POST['product_id'] ]['dbargain_price'] ) || empty( $_SESSION['dbargain'][ $_POST['product_id'] ]['dbargain_price'] ) ) {
				$wpdb->insert(
					$wpdb->prefix . 'dbargain_session',
					array(
						'session_id'   => $_SESSION['dbargain'][ $pid ]['session_id'],
						'product_id'   => $pid,
						'offer'        => $offer,
						'status'       => '0',
						'date_created' => 'NOW()',
					)
				);
			} else {
				$data = $wpdb->get_results( $wpdb->prepare( "select * from {$wpdb->prefix}dbargain_session where session_id = %s and product_id = %d", $_SESSION['dbargain'][ $pid ]['session_id'], $pid ), ARRAY_A );
				foreach ( $data as $msg ) {
					if ( ! empty( $msg['message'] ) ) {
						$html .= self::chat_message_response( $agent_name, $msg['message'], $msg['offer'] );
					} else {
						$html .= self::chat_message_response( $agent_name, $msg['offer'], $msg['offer'] );
					}
				}

				wp_send_json(
					array(
						'data'          => $html,
						'button_status' => 'true',
						'chat_status'   => 'false',
					)
				);
				die;
			}

			$message = '';

			$product   = wc_get_product( $pid );
			$price     = $product->get_price();
			$threshold = get_post_meta( $pid, '_dbargain_price_threshold', true );

			if ( empty( $threshold ) ) {
				$global_threshold = get_option( 'dbargain_threshold' );
				$threshold        = $price - ( (int) ( $price * $global_threshold / 100 ) );
			}

			//Check if user reached limit.
			$count = $wpdb->get_var( $wpdb->prepare( "select count(*) from {$wpdb->prefix}dbargain_session where session_id = %s and product_id = %d and offer > 0", $_SESSION['dbargain'][ $pid ]['session_id'], $pid ) );

			if ( $_POST['offer'] >= $threshold && $_POST['offer'] <= $price ) {
				$message = $wpdb->get_var( "select message from {$wpdb->prefix}dbargain_responses where `condition` = 'success' order by rand() limit 1" );
				if ( empty( $message ) ) {
					$message = 'Success!! Thankyou for the great offer. You can buy the product on your offered price';
				}

				$button_status = 'true';
				$chat_status   = 'false';

				$_SESSION['dbargain'][ $pid ]['dbargain_price'] = $offer;

				array_push(
					$_SESSION['offerdata'],
					array(
						'ID'           => $pid,
						'product_id'   => $product->id,
						'session_id'   => $_SESSION['dbargain'][ $pid ]['session_id'],
						'offer'        => $offer,
						'message'      => $message,
						'status'       => 1,
						'date_created' => date( 'Y-m-d H:i:s' ),
					)
				);
				$data = http_build_query(
					array(
						'session_data' => $_SESSION['offerdata'],
						'product_data' => array(
							'id'          => $pid,
							'product_sku' => '',
							'variations'  => '',
							'price'       => $price,
							'name'        => $product->name,
						),
					)
				);

				$add  = 'api/session/store';
				$url  = 'https://kz3r4ehy21.execute-api.us-east-1.amazonaws.com/dev/' . $add;
				$curl = curl_init();
				curl_setopt( $curl, CURLOPT_POST, 1 );
				curl_setopt( $curl, CURLOPT_POSTFIELDS, $data );
				curl_setopt( $curl, CURLOPT_URL, $url );
				curl_setopt(
					$curl,
					CURLOPT_HTTPHEADER,
					array(
						'Authorization: Bearer ' . $token,
						'Content-Type: application/x-www-form-urlencoded',
					)
				);
				curl_setopt( $curl, CURLOPT_RETURNTRANSFER, 1 );
				curl_setopt( $curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC );
				$result = curl_exec( $curl );

				if ( ! $result ) {

				}
				curl_close( $curl );
			} elseif ( $count == $_SESSION['dbargain'][ $_POST['product_id'] ]['attempts'] ) {
				$message = $wpdb->get_var( "select message from {$wpdb->prefix}dbargain_responses where `condition` = 'failure' order by rand() limit 1" );

				if ( empty( $message ) ) {
					$message = 'We are sorry but you have exhausted all your chances. Considering your interest and effort, we can let you buy this product on a discounted price still. Our final price for you is: ';
				}

				$final_price = (int) ( $price + $threshold ) / 2;
				$message    .= wc_price( $final_price );

				$button_status = 'true';
				$chat_status   = 'false';

				$_SESSION['dbargain'][ $pid ]['dbargain_price'] = $final_price;

				array_push(
					$_SESSION['offerdata'],
					array(
						'ID'           => $pid,
						'product_id'   => $product->id,
						'session_id'   => $_SESSION['dbargain'][ $pid ]['session_id'],
						'offer'        => $offer,
						'message'      => $message,
						'status'       => 0,
						'date_created' => date( 'Y-m-d H:i:s' ),
					)
				);
			} elseif ( $_POST['offer'] > $price ) {
				$message = $wpdb->get_var( "select message from {$wpdb->prefix}dbargain_responses where `condition` = 'more' order by rand() limit 1" );

				if ( empty( $message ) ) {
					$message = 'Thankyou for your enthusiasm but the offer you are making, is above the product price itself. You can buy the product on original price';
				}

				$button_status = 'false';
				$chat_status   = 'true';
				array_push(
					$_SESSION['offerdata'],
					array(
						'ID'           => $pid,
						'product_id'   => $product->id,
						'session_id'   => $_SESSION['dbargain'][ $pid ]['session_id'],
						'offer'        => $offer,
						'message'      => $message,
						'status'       => 0,
						'date_created' => date( 'Y-m-d H:i:s' ),
					)
				);
			} else {
				$percentage_difference = (int) ( ( ( $threshold[0] - $offer ) / $threshold[0] ) * 100 );

				//Get all messages in range lesser than calculated $percentage_difference
				$messages = $wpdb->get_results( $wpdb->prepare( "select percentage_difference, message from {$wpdb->prefix}dbargain_responses where `condition` = 'less' and percentage_difference <= %d order by percentage_difference desc", $percentage_difference ), ARRAY_A );

				//We need to pick all messages of closest value
				if ( isset( $messages[0] ) ) {
					$required_value = $messages[0]['percentage_difference'];//echo $required_value;
					$msgs           = array();
					foreach ( $messages as $m ) {
						//Gather all messages from same value in case if there are multiple messages for same value.
						if ( $required_value == $m['percentage_difference'] ) {
							$msgs[] = $m['message'];
						}
					}

					//Pick a random message from same range.
					$message = $msgs[ array_rand( $msgs, 1 ) ];
				}

				if ( empty( $message ) ) {
					$message = 'Please make a better offer ';
				}

				$button_status = 'false';
				$chat_status   = 'true';
				array_push(
					$_SESSION['offerdata'],
					array(
						'ID'           => $pid,
						'product_id'   => $product->id,
						'session_id'   => $_SESSION['dbargain'][ $pid ]['session_id'],
						'offer'        => $offer,
						'message'      => $message,
						'status'       => 0,
						'date_created' => date( 'Y-m-d H:i:s' ),
					)
				);
			}

			$wpdb->insert(
				$wpdb->prefix . 'dbargain_session',
				array(
					'session_id'   => $_SESSION['dbargain'][ $pid ]['session_id'],
					'product_id'   => $pid,
					'message'      => $message,
					'status'       => '0',
					'date_created' => 'NOW()',
				)
			);

			if ( $count == $_SESSION['dbargain'][ $_POST['product_id'] ]['attempts'] - 1 ) {
				$message = $wpdb->get_var( "select message from {$wpdb->prefix}dbargain_responses where `condition` = 'warning' order by rand() limit 1" );
				if ( empty( $message ) ) {
					$message = 'You have exhausted all your attempts. Considering your interest and effort, we are going to grant you one last chance. Please give us your best offer this time.';
				}
				array_push(
					$_SESSION['offerdata'],
					array(
						'ID'           => $pid,
						'product_id'   => $product->id,
						'session_id'   => $_SESSION['dbargain'][ $pid ]['session_id'],
						'offer'        => $offer,
						'message'      => $message,
						'status'       => 0,
						'date_created' => date( 'Y-m-d H:i:s' ),
					)
				);
				$wpdb->insert(
					$wpdb->prefix . 'dbargain_session',
					array(
						'session_id'   => $_SESSION['dbargain'][ $pid ]['session_id'],
						'product_id'   => $pid,
						'message'      => $message,
						'status'       => '0',
						'date_created' => 'NOW()',
					)
				);
			}

			$data = $wpdb->get_results( $wpdb->prepare( "select * from {$wpdb->prefix}dbargain_session where session_id = %s and product_id = %d", $_SESSION['dbargain'][ $pid ]['session_id'], $pid ), ARRAY_A );
			foreach ( $data as $msg ) {
				if ( ! empty( $msg['message'] ) ) {
					$html .= self::chat_message_response( $agent_name, $msg['message'], $msg['offer'] );
				} else {
					$html .= self::chat_message_response( $agent_name, $msg['offer'], $msg['offer'] );
				}
			}
			wp_send_json(
				array(
					'data'          => $html,
					'button_status' => $button_status,
					'chat_status'   => $chat_status,
				)
			);
		} catch ( exception $e ) {
			$button_status = 'false';
			$chat_status   = 'true';
			$html         .= self::chat_message_response( $agent_name, 'Error from Server', null );

			wp_send_json(
				array(
					'data'          => '',
					'button_status' => $button_status,
					'chat_status'   => $chat_status,
				)
			);
		}
	}

	public static function chat_message_response( $agent_name, $msg, $offer ) {
		$res = '<div class="chat-message clearfix">';
		if ( $offer == null ) {
			$res .= '<img src="http://gravatar.com/avatar/2c0ad52fc5943b78d6abe069cc08f320?s=32" alt="" width="32" height="32">';
		}

		$res .= '
                    <div class="chat-message-content clearfix">
                    ';
		if ( $offer == null ) {
			$res .= '<h5>' . $agent_name . '</h5>';
		}
		if ( $offer == null ) {
			$res .= '<p>' . $msg . '</p>';
		} else {
			$res .= '<p style="text-align: right;">' . $msg . '</p>';
		}
		$res .= '
                    </div> <!-- end chat-message-content -->
                </div> <!-- end chat-message -->
                <hr>';
		return $res;
	}
}

DBargain::register();
