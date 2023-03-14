<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$messgae_res = null;
if ( isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( $_REQUEST['_wpnonce'] ), 'dbargain-settings' ) ) {
	

	if ( isset( $_POST['merchant_id'] ) ) {
		update_option( 'dbargain_merchant_id', sanitize_text_field( $_POST['merchant_id'] ), 'yes' );
		update_option( 'dbargain_email_sent',0, 'yes');
		update_option( 'dbargain_email_oneday',0,'yes');


	}

	if ( isset( $_POST['base_url'] ) ) {

		$url = $_POST['base_url'];

		if (substr($url, -1) !== '/') {
			$url .= '/';
		}

		if (!preg_match("~^(?:f|ht)tps?://~i", $url)) {
			$url = "https://" . $url;
		}

		update_option( 'dbargain_api_base_url', sanitize_text_field(  $url ), 'yes' );
	

	}

	
	
	if ( isset( $_POST['upper_limit'] ) ) {
		update_option( 'dbargain_session_upper_limit', sanitize_text_field( $_POST['upper_limit'] ), 'yes' );
	}

	if ( isset( $_POST['lower_limit'] ) ) {
		update_option( 'dbargain_session_lower_limit', sanitize_text_field( $_POST['lower_limit'] ), 'yes' );
	}

	if ( isset( $_POST['bg_color'] ) ) {
		update_option( 'dbargain_bg_color', sanitize_text_field( $_POST['bg_color'] ), 'yes' );
	}

	if ( isset( $_POST['txt_color'] ) ) {
		update_option( 'dbargain_txt_color', sanitize_text_field( $_POST['txt_color'] ), 'yes' );
	}

	if ( isset( $_POST['btn_color'] ) ) {
		update_option( 'dbargain_btn_color', sanitize_text_field( $_POST['btn_color'] ), 'yes' );
	}

	if ( isset( $_POST['heading'] ) ) {
		update_option( 'dbargain_heading_font', sanitize_text_field( $_POST['heading'] ), 'yes' );
	}

	if ( isset( $_POST['text'] ) ) {
		update_option( 'dbargain_text_font', sanitize_text_field( $_POST['text'] ), 'yes' );
	}

	if ( isset( $_POST['button'] ) ) {
		update_option( 'dbargain_button_font', sanitize_text_field( $_POST['button'] ), 'yes' );
	}

	if ( isset( $_POST['label'] ) ) {
		update_option( 'dbargain_label_font', sanitize_text_field( $_POST['label'] ), 'yes' );
	}

	if ( isset( $_POST['layout'] ) ) {
		update_option( 'dbargain_window_layout', sanitize_text_field( $_POST['layout'] ), 'yes' );
	}

	if ( isset( $_POST['criteria'] ) ) {
		update_option( 'dbargain_display_criteria', sanitize_text_field( $_POST['criteria'] ), 'yes' );
	}

	if ( isset( $_POST['delay'] ) ) {
		update_option( 'dbargain_window_delay', sanitize_text_field( $_POST['delay'] ), 'yes' );
	}

	if ( isset( $_POST['threshold'] ) ) {
		update_option( 'dbargain_threshold', sanitize_text_field( $_POST['threshold'] ), 'yes' );
	}

	if ( isset( $_POST['start_date'] ) ) {
		update_option( 'dbargain_start_date', sanitize_text_field( $_POST['start_date'] ), 'yes' );
	}

	if ( isset( $_POST['end_date'] ) ) {
		update_option( 'dbargain_end_date', sanitize_text_field( $_POST['end_date'] ), 'yes' );
	}

	if ( isset( $_POST['chat_delay'] ) ) {
		update_option( 'dbargain_window_chat_delay', sanitize_text_field( $_POST['chat_delay'] ), 'yes' );
	}

	if ( isset( $_POST['agent_name'] ) ) {
		update_option( 'dbargain_agent_name', sanitize_text_field( $_POST['agent_name'] ), 'yes' );
	}

	$messgae_res = 'your setting has been updated';
}

$heading_font = get_option( 'dbargain_heading_font' ) ? get_option( 'dbargain_heading_font' ) : '';
$text_font    = get_option( 'dbargain_text_font' ) ? get_option( 'dbargain_text_font' ) : '';
$button_font  = get_option( 'dbargain_button_font' ) ? get_option( 'dbargain_button_font' ) : '';
$label_font   = get_option( 'dbargain_label_font' ) ? get_option( 'dbargain_label_font' ) : '';
$layout       = get_option( 'dbargain_window_layout' ) ? get_option( 'dbargain_window_layout' ) : '';
$criteria     = get_option( 'dbargain_display_criteria' ) ? get_option( 'dbargain_display_criteria' ) : array();

if ( $isMerchantTimeValid ==false ) { ?>

<div class="notice notice-warning is-dismissible">
    <p>Your subscription will be expiring on <?php echo $dBargainExpiryDate; ?>. Kindly renew your subscription in order to continue availing the services of DBargain.</p>
</div>

<?php } 
if(!$isMerchantIdValid){ ?>
	<div class="notice notice-warning is-dismissible">
		<p>Subscription expired or error occurred. Please contact plugin support for assistance.</p>
	</div>
<?php }  ?>

<div class="wrap">
	<h1 class="wp-heading-inline"> Settings</h1>
	<hr class="wp-header-end">
	<?php
	if ( null != $messgae_res ) {
		?>
		<p style="color: green; font-weight: bold; padding: 10px; border: green solid;"><?php echo esc_attr( $messgae_res ); ?> </p>
		<?php
	}
	?>
	<form name="edit_plaza" action="admin.php?page=dbargain_settings" method="post" id="post">
		<?php wp_nonce_field( 'dbargain-settings' ); ?>
		<br><br>


		<h2>API Detail</h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr class="user-rich-editing-wrap">
					<th scope="row">API ENDPOINT - BASEURL</th>
					<td>
						<label for="base_url">
							<input type="url" name="base_url" id="base_url" value="<?php echo esc_attr( get_option( 'dbargain_api_base_url' ) ? get_option( 'dbargain_api_base_url' ) : 'https://d-bargain.com/' ); ?>" class="regular-text">
						</label>
					</td>
				</tr>
			
			</tbody>
		</table>
		<br><br>


		<h2>Merchant Detail</h2>
		<table class="form-table" role="presentation">
			<tbody>
				<tr class="user-rich-editing-wrap">
					<th scope="row">Merchant Id</th>
					<td>
						<label for="merchant_id"><input type="text" name="merchant_id" id="upper_limit"
														value="<?php echo esc_attr( get_option( 'dbargain_merchant_id' ) ? get_option( 'dbargain_merchant_id' ) : '' ); ?>"
														class="regular-text">
						</label>
					</td>
				</tr>
			
			</tbody>
		</table>
		<br><br>

		<?php 
			if($isMerchantIdValid){
		?>

		<h2>Session Limit</h2>
		<table class="form-table" role="presentation">
			<tbody>
			<tr class="user-rich-editing-wrap">
				<th scope="row">Upper Limit</th>
				<td>
					<label for="upper_limit"><input type="text" name="upper_limit" id="upper_limit"
													value="<?php echo esc_attr( get_option( 'dbargain_session_upper_limit' ) ? get_option( 'dbargain_session_upper_limit' ) : '' ); ?>"
													class="regular-text">
					</label>
				</td>
			</tr>
			<tr class="user-rich-editing-wrap">
				<th scope="row">Lower Limit</th>
				<td>
					<label for="lower_limit"><input type="text" name="lower_limit" id="lower_limit"
													value="<?php echo esc_attr( get_option( 'dbargain_session_lower_limit' ) ? get_option( 'dbargain_session_lower_limit' ) : '' ); ?>"
													class="regular-text">
					</label>
				</td>
			</tr>
			</tbody>
		</table>
		<br><br>

		<h2>Global Settings</h2>
		<table class="form-table" role="presentation">
			<tbody>
			<tr class="user-rich-editing-wrap">
				<th scope="row">Threshold (%)</th>
				<td>
					<label for="threshold"><input type="number" name="threshold" id="threshold"
												  value="<?php echo esc_attr( get_option( 'dbargain_threshold' ) ? get_option( 'dbargain_threshold' ) : '' ); ?>"
												  class="regular-text">
					</label>
				</td>
			</tr>
			<tr class="user-rich-editing-wrap">
				<th scope="row">Start Date</th><?php echo esc_attr( get_option( 'dbargain_start_date' ) ); ?>
				<td>
					<label for="start_date"><input type="text" name="start_date" id="start_date"
												   value="<?php echo esc_attr( get_option( 'dbargain_start_date' ) ? get_option( 'dbargain_start_date' ) : '' ); ?>"
												   class="regular-text datepicker">
					</label>
				</td>
			</tr>
			<tr class="user-rich-editing-wrap">
				<th scope="row">End Date</th>
				<td>
					<label for="end_date"><input type="text" name="end_date" id="end_date"
												 value="<?php echo esc_attr( get_option( 'dbargain_end_date' ) ? get_option( 'dbargain_end_date' ) : '' ); ?>"
												 class="regular-text datepicker">
					</label>
				</td>
			</tr>
			</tbody>
		</table>
		<br><br>

		<h2>Frontend Window Settings</h2>

		<table class="form-table" role="presentation">
			<tbody>
			<tr class="user-rich-editing-wrap">
				<th scope="row">Display Popup When</th>
				<td>
					<input type="checkbox" id="exit" name="criteria[]"
						   value="exit" 
						   <?php
							if ( in_array( 'exit', $criteria ) ) {
								echo esc_attr( 'checked="checked"' );}
							?>
							>
					<label for="exit">User Exit Page</label><br>
					<input type="checkbox" id="delay" name="criteria[]" value="delay"
						   onclick="jQuery('#time_delay').toggle();jQuery('#chat_time_delay').toggle();" 
						   <?php
							if ( in_array( 'delay', $criteria ) ) {
								echo esc_attr( 'checked="checked"' );}
							?>
							>
					<label for="delay">User has spent certain time on page</label>
				</td>
			</tr>
			<tr class="user-rich-editing-wrap"
				id="time_delay" 
				<?php
				if ( ! in_array( 'delay', $criteria ) ) {
					echo esc_attr( 'style="display: none"' );}
				?>
				 >
				<th scope="row">Window Popup delay (seconds)</th>
				<td>
					<label for="delay"><input type="number" name="delay" id="seconds"
											  value="<?php echo esc_attr( get_option( 'dbargain_window_delay' ) ? get_option( 'dbargain_window_delay' ) : '10' ); ?>"
											  class="small-text">
					</label>
				</td>
			</tr>
			<tr class="user-rich-editing-wrap"
				id="chat_time_delay" 
				<?php
				if ( ! in_array( 'delay', $criteria ) ) {
					echo esc_attr( 'style="display: none"' );}
				?>
				 >
				<th scope="row">Window Chat delay (seconds)</th>
				<td>
					<label for="chat_delay"><input type="number" name="chat_delay" id="chat_seconds"
												   value="<?php echo esc_attr( get_option( 'dbargain_window_chat_delay' ) ? get_option( 'dbargain_window_chat_delay' ) : '10' ); ?>"
												   class="small-text">
					</label>
				</td>
			</tr>

			<tr class="user-rich-editing-wrap" id="chat_time_delay">
				<th scope="row">Chat Agent Name</th>
				<td>
					<label for="agent_name"><input type="text" name="agent_name" id="agent_name"
												   value="<?php echo esc_attr( get_option( 'dbargain_agent_name' ) ? get_option( 'dbargain_agent_name' ) : 'Jone D' ); ?>"
												   class="regular-text">
					</label>
				</td>
			</tr>
			</tbody>
		</table>
		<br><br>

		<h2>Color Scheme</h2>

		<table class="form-table" role="presentation">
			<tbody>
			<tr class="user-rich-editing-wrap">
				<th scope="row">Background Color</th>
				<td>
					<label for="bg_color"><input type="text" name="bg_color" id="bg_color"
												 value="<?php echo esc_attr( get_option( 'dbargain_bg_color' ) ? get_option( 'dbargain_bg_color' ) : '' ); ?>"
												 class="regular-text cpa-color-picker">
					</label>
				</td>
			</tr>
			<tr class="user-rich-editing-wrap">
				<th scope="row">Text Color</th>
				<td>
					<label for="txt_color"><input type="text" name="txt_color" id="txt_color"
												  value="<?php echo esc_attr( get_option( 'dbargain_txt_color' ) ? get_option( 'dbargain_txt_color' ) : '' ); ?>"
												  class="regular-text cpa-color-picker">
					</label>
				</td>
			</tr>
			<tr class="user-rich-editing-wrap">
				<th scope="row">Button Color</th>
				<td>
					<label for="btn_color"><input type="text" name="btn_color" id="btn_color"
												  value="<?php echo esc_attr( get_option( 'dbargain_btn_color' ) ? get_option( 'dbargain_btn_color' ) : '' ); ?>"
												  data-default-color="<?php echo esc_attr( get_option( 'dbargain_btn_color' ) ? get_option( 'dbargain_btn_color' ) : '' ); ?>"
												  class="regular-text cpa-color-picker">
					</label>
				</td>
			</tr>
			</tbody>
		</table>
		<br><br>

		<h2>Typography</h2>

		<table class="form-table" role="presentation">
			<tbody>
			<tr class="user-rich-editing-wrap">
				<th scope="row">Headings</th>
				<td>
					<label for="heading">
						<select name="heading" id="heading">
							<option value="Arial" 
							<?php
							if ( 'Arial' == $heading_font ) {
								echo esc_attr( 'selected="selected"' );}
							?>
							>
								Arial
							</option>
							<option
								value="Verdana" 
								<?php
								if ( 'Verdana' == $heading_font ) {
									echo esc_attr( 'selected="selected"' );}
								?>
								>
								Verdana
							</option>
							<option
								value="Helvetica" 
								<?php
								if ( 'Helvetica' == $heading_font ) {
									echo esc_attr( 'selected="selected"' );}
								?>
								>
								Helvetica
							</option>
							<option
								value="sans-serif" 
								<?php
								if ( 'sans-serif' == $heading_font ) {
									echo esc_attr( 'selected="selected"' );}
								?>
								>
								Sans Serif
							</option>
						</select>
					</label>
				</td>
			</tr>
			<tr class="user-rich-editing-wrap">
				<th scope="row">Text</th>
				<td>
					<label for="text">
						<select name="text" id="text">
							<option value="Arial" 
							<?php
							if ( 'Arial' == $text_font ) {
								echo esc_attr( 'selected="selected"' );}
							?>
							>
								Arial
							</option>
							<option value="Verdana" 
							<?php
							if ( 'Verdana' == $text_font ) {
								echo esc_attr( 'selected="selected"' );}
							?>
							>
								Verdana
							</option>
							<option
								value="Helvetica" 
								<?php
								if ( 'Helvetica' == $text_font ) {
									echo esc_attr( 'selected="selected"' );}
								?>
								>
								Helvetica
							</option>
							<option
								value="sans-serif" 
								<?php
								if ( 'sans-serif' == $text_font ) {
									echo esc_attr( 'selected="selected"' );}
								?>
								>
								Sans Serif
							</option>
						</select>
					</label>
				</td>
			</tr>
			<tr class="user-rich-editing-wrap">
				<th scope="row">Button</th>
				<td>
					<label for="button">
						<select name="button" id="button">
							<option value="Arial" 
							<?php
							if ( 'Arial' == $button_font ) {
								echo esc_attr( 'selected="selected"' );}
							?>
							>
								Arial
							</option>
							<option value="Verdana" 
							<?php
							if ( 'Verdana' == $button_font ) {
								echo esc_attr( 'selected="selected"' );}
							?>
							>
								Verdana
							</option>
							<option
								value="Helvetica" 
								<?php
								if ( 'Helvetica' == $button_font ) {
									echo esc_attr( 'selected="selected"' );}
								?>
								>
								Helvetica
							</option>
							<option
								value="sans-serif" 
								<?php
								if ( 'sans-serif' == $button_font ) {
									echo esc_attr( 'selected="selected"' );}
								?>
								>
								Sans Serif
							</option>
						</select>
					</label>
				</td>
			</tr>
			<tr class="user-rich-editing-wrap">
				<th scope="row">Label</th>
				<td>
					<label for="label">
						<select name="label" id="label">
							<option value="Arial" 
							<?php
							if ( 'Arial' == $label_font ) {
								echo esc_attr( 'selected="selected"' );}
							?>
							>
								Arial
							</option>
							<option value="Verdana" 
							<?php
							if ( 'Verdana' == $label_font ) {
								echo esc_attr( 'selected="selected"' );}
							?>
							>
								Verdana
							</option>
							<option
								value="Helvetica" 
								<?php
								if ( 'Helvetica' == $label_font ) {
									echo esc_attr( 'selected="selected"' );}
								?>
								>
								Helvetica
							</option>
							<option
								value="sans-serif" 
								<?php
								if ( 'sans-serif' == $label_font ) {
									echo esc_attr( 'selected="selected"' );}
								?>
								>
								Sans Serif
							</option>
						</select>
					</label>
				</td>
			</tr>
			</tbody>
		</table>
		
		<?php } ?>
						
		<p class="submit">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Settings">
		</p>
	</form>
</div>
