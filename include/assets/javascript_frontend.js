//////////////start Modal rendering code //////////////

// Get the modal
var modal   = document.getElementById( "myModal" );
var db_chat = document.getElementById( "db_live-chat" );

// When the user clicks anywhere outside of the modal, close it
window.onclick = function (event) {
	if (event.target == modal) {
		modal.style.display = "none";
	}
}

/////////// End Modal rendering Code //////////

//////////Start DBargain window rendering conditions //////////////

let startDate       = new Date();
let time_limit      = document.getElementById( 'time_limit' ).value;
let layout          = document.getElementById( 'window_layout' ).value;
let time_chat_limit = document.getElementById( 'time_chat_limit' ).value;

function show_modal()
{
	setTimeout( () => {modal.style.display = "block";}, time_limit * 1000 );
}
if (time_chat_limit > 0) {
	let inactivityTime = function () {
		let time;
		window.onload    = resetTimer;
		document.onclick = resetTimer;

		function show_chat_box()
		{
			modal.style.display   = "none";
			db_chat.style.display = "block";
			jQuery( '#db_chat-history' ).animate( { scrollTop:  jQuery( "#db_chat-history" ).offset().top + 2000}, 'fast' );
		}
		function resetTimer()
		{
			time = setTimeout( show_chat_box, time_chat_limit * 1000 );
		}
	};
	window.onload      = function () {
		inactivityTime();
		if (time_limit > 0) {
			show_modal()
		}
	}
}

if (document.getElementById( 'exit' ).value == 'exit') {
	window.onbeforeunload = function (e) {
		const endDate       = new Date();
		const spentTime     = endDate.getTime() - startDate.getTime();
		modal.style.display = "block";
		return "Would you like to make a bargain offer before leaving?";
	};
}

////////// End DBargain window rendering conditions //////////////
jQuery( document ).ready(
	function () {
		jQuery( '#make_offer' ).on(
			'click',
			function (e) {
				jQuery.post(
					jQuery( '#ajax_url' ).val(),
					{action:"make_offer",session_id:jQuery( '#session_id' ).val(),product_id:jQuery( '#product_id' ).val(),offer:jQuery( '#offer' ).val(), nonce: jQuery( '#nonce' ).val()},
					function (response) {
						jQuery( '#db_chat-history' ).html( response.data );
						jQuery( '#offer' ).val( '' );
						jQuery( '#db_chat-history' ).animate( { scrollTop:  jQuery( "#db_chat-history" ).offset().top + 2000}, 'fast' );

						if (response.button_status == 'true') {
							jQuery( '#db_add_to_cart' ).show();
						} else {
							jQuery( '#db_add_to_cart' ).hide();
						}
						if (response.chat_status == 'true') {
							jQuery( '#make_offer' ).show();
							jQuery( '#offer' ).show();
						} else {
							jQuery( '#make_offer' ).hide();
							jQuery( '#offer' ).hide();
						}
					}
				);
			}
		);

		jQuery( '#db_live-chat header' ).on(
			'click',
			function () {
				jQuery( '.chat' ).slideToggle( 300, 'swing' );
			}
		);
		jQuery( '.chat-close' ).on(
			'click',
			function (e) {
			}
		);
	}
);
