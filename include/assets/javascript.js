function hide_detail(id)
{
	jQuery( '#detail_' + id ).slideUp();
	jQuery( '#' + id ).show();
}
(function ($) {
	$(
		function () {

			// Add Color Picker to all inputs that have 'color-field' class
			$( '.cpa-color-picker' ).wpColorPicker();

			// Show Details of Offer
			$( '.show_detail' ).on(
				'click',
				function (e) {
					if ($( this ).hasClass( 'populate_detail' )) {
						$( this ).parent().parent().prop( 'id', $( this ).prop( 'rel' ) ).hide();
						$( this ).removeClass( 'populate_detail' );

						$.post(
							'admin-ajax.php',
							{'action': 'fetch_details', 'id': $( this ).prop( 'rel' ), '_wpnonce': $( this ).data( 'nonce' )},
							function (res) {
								id = res.data.id;

								var html = '<tr id="detail_' + id + '"><td colspan="6" width="100%">' + res.data.data + '</td></tr>';
								$( html ).insertAfter( $( '#' + id ) );
							}
						);
					} else {
						$( '#' + $( this ).prop( 'rel' ) ).hide();
						$( '#detail_' + $( this ).prop( 'rel' ) ).slideDown();
					}
				}
			);

			$( '.datepicker' ).datepicker( {dateFormat: 'DD, d MM, yy', numberOfMonths: 1} );
		}
	);
})( jQuery );
