jQuery(document).ready(function () {
	
/* Premium Code Stripped by Freemius */


	jQuery(function () {
		jQuery("#tabs").tabs();
	});


	jQuery('continue_update_paged').click(function () {
		wc4bp_total_user_pages = jQuery('#wc4bp_total_user_pages').val();
		wc4bp_this_user_pages = jQuery('#continue_update_paged').val();
		wc4bp_update_user();
	});

	jQuery('.wc_bp_sync_all_user_data').click(function () {
		update_type = jQuery(this).attr('id');
		visibility_level = jQuery('#' + update_type + '_options').val();

		wc4bp_total_user_pages = jQuery('#wc4bp_total_user_pages').val();
		wc4bp_this_user_pages = 0;
		wc4bp_update_user();
	});

});

function wc4bp_update_user() {

	wc4bp_this_user_pages++;

	jQuery.ajax({
		async: false,
		type: 'POST',
		url: ajaxurl,
		data: {"action": "wc4bp_shop_profile_sync_ajax", "visibility_level": visibility_level, "update_type": update_type, "wc4bp_page": wc4bp_this_user_pages},
		success: function (data) {
			jQuery("#result").html(data);
		},
		error: function () {
			alert('Something went wrong.. ;-(sorry)');
		}
	});

	if (wc4bp_total_user_pages > wc4bp_this_user_pages) {
		window.setTimeout(function () {
			wc4bp_update_user();
		}, 0);
	}

	if (wc4bp_total_user_pages == wc4bp_this_user_pages) {
		jQuery("#result").html('<h2>All Donne! Update Complete ;)</h2>');
	}

}
