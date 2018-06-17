/*
 * @package WordPress
 * @subpackage BuddyPress, Woocommerce, WC4BP
 * @author ThemKraft Dev Team
 * @copyright 2017, Themekraft
 * @link http://buddyforms.com/downloads/buddyforms-woocommerce-form-elements/
 * @license GPLv2 or later
 */
jQuery(function ($) {
	if (wc4bp_groups_woo_element.hide_wc4bp_groups !== undefined && wc4bp_groups_woo_element.hide_wc4bp_groups[0] !== undefined &&
		wc4bp_groups_woo_element.hide_wc4bp_groups[0] !== 'false') {
		$('.wc4bp_groups_tab').hide();
		$('#wc4bp_groups').hide();
	}
});