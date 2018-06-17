<?php
/**
 * @package        WordPress
 * @subpackage     BuddyPress, Woocommerce, WC4BP
 * @author         ThemKraft Dev Team
 * @copyright      2017, Themekraft
 * @link           http://themekraft.com/store/woocommerce-buddypress-integration-wordpress-plugin/
 * @license        http://www.opensource.org/licenses/gpl-2.0.php GPL License
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class wc4bp_groups_woo_elem_integration {
	private $loaded_script = false;
	
	public function __construct() {
		add_filter( 'bf_woo_element_woo_implemented_tab', array( $this, 'implemented_tab' ), 10, 1 );
		add_filter( 'buddyforms_formbuilder_fields_options', array( $this, 'add_wc_form_element_tab' ), 10, 3 );
		add_filter( 'buddyforms_create_edit_form_display_element', array( $this, 'form_display_element' ), 10, 2 );
		add_action( 'buddyforms_update_post_meta', array( $this, 'buddyforms_product_save_data' ), 99, 2 );
	}
	
	public function implemented_tab( $existing ) {
		
		return array_merge( $existing, array( 'wc4bp_groups' ) );
	}
	
	public function add_wc_form_element_tab( $form_fields, $field_type, $field_id ) {
		global $post;
		
		$buddyform = get_post_meta( $post->ID, '_buddyforms_options', true );
		
		$hook_field_types = array( 'woocommerce' );
		
		if ( ! in_array( $field_type, $hook_field_types ) ) {
			return $form_fields;
		}
		
		$form_fields['WC4BP-Groups']['hide_wc4bp_groups'] = new Element_Checkbox( '<b>' . __( 'Tab WC4BP Groups: ', 'buddyforms' ) . '</b>', "buddyforms_options[form_fields][" . $field_id . "][hide_wc4bp_groups]", array( 'hidden' => __( 'Hide WC4BP Groups Tab', 'buddyforms' ) ), array(
			'id'    => 'hide_wc4bp_groups_' . $field_id,
			'value' => isset( $buddyform['form_fields'][ $field_id ]['hide_wc4bp_groups'] ) ? $buddyform['form_fields'][ $field_id ]['hide_wc4bp_groups'] : 'false'
		) );
		
		return $form_fields;
	}
	
	public function form_display_element( $form, $form_args ) {
		extract( $form_args );
		if ( ! isset( $customfield['type'] ) ) {
			return $form;
		}
		if ( ( $customfield['type'] == 'woocommerce' || $customfield['type'] == 'product-gallery' ) && is_user_logged_in() ) {
			if ( ! $this->loaded_script ) {
				$this->add_scripts( $customfield );
			}
		}
		
		return $form;
	}
	
	private function add_scripts( $custom_field ) {
		wc4bp_groups_manager::enqueue_js( '', true );
		wc4bp_groups_manager::enqueue_style( '', true );
		wp_register_script( 'wc4bp_groups_woo_element', WC4BP_GROUP_JS_PATH . 'wc4bp-groups-woo-element.js', array( 'jquery' ), wc4bp_groups_manager::getVersion(), true );
		wp_enqueue_script( 'wc4bp_groups_woo_element' );
		wp_localize_script( 'wc4bp_groups_woo_element', 'wc4bp_groups_woo_element', $custom_field );
		$this->loaded_script = true;
	}
	
	/**
	 * Saves the data inputed into the product boxes, as post meta data
	 *
	 *
	 * @param int $post_id   the post (product) identifier
	 * @param stdClass $post the post (product)
	 *
	 */
	public function buddyforms_product_save_data( $post, $post_id ) {
		if ( $post['type'] == 'woocommerce' ) {
			wc4bp_groups_woo::saveProductOptionsFields( $post_id, $post );
		}
	}
}