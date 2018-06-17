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

class wc4bp_groups_woo {

	public function __construct() {
		add_filter( 'woocommerce_product_data_tabs', array( $this, 'addProductOptionSection' ), 10, 1 ); // Add section
		add_action( 'woocommerce_product_data_panels', array( $this, 'addProductOptionPanelTab' ) );// Add Section Tab content
		add_action( 'woocommerce_process_product_meta', array( $this, 'saveProductOptionsFields' ), 11, 2 ); // Save option
		if ( bp_is_active( 'groups' ) ) {
			add_action( 'woocommerce_after_add_to_cart_button', array( $this, 'add_field_to_product_page' ) ); //Add field to the product page
			// filters for cart actions
			add_filter( 'woocommerce_add_cart_item_data', array( $this, 'add_cart_item_data' ), 10, 2 );
			add_filter( 'woocommerce_get_cart_item_from_session', array( $this, 'get_cart_item_from_session' ), 10, 2 );
			add_filter( 'woocommerce_get_item_data', array( $this, 'get_item_data' ), 10, 2 );
			add_action( 'woocommerce_add_order_item_meta', array( $this, 'add_order_item_meta' ), 10, 2 );
			//Create the user in the group, depend on the current selected status from the backend
			add_action( 'woocommerce_order_status_changed', array( $this, 'on_process_complete' ), 10, 4 );
			add_filter( 'woocommerce_order_items_meta_display', array( $this, 'on_order_items_meta_display' ), 10, 2 ); //Process the item meta to show in the order in the front
			add_filter( 'woocommerce_display_item_meta', array( $this, 'on_display_items_meta' ), 10, 3 ); //Process the item meta to show in the order in the front
			add_filter( 'woocommerce_hidden_order_itemmeta', array( $this, 'hidden_order_itemmeta' ), 10, 1 ); //Hide the custom meta to avoid show it as code
			add_action( 'woocommerce_after_order_itemmeta', array( $this, 'add_after_oder_item_meta' ), 10, 3 ); //Add the custom meta to the line item in the backend
		}
	}

	/**
	 * Hide the custom meta to avoid show it as code
	 *
	 * @param $hidden_metas
	 *
	 * @return array
	 */
	public function hidden_order_itemmeta( $hidden_metas ) {
		return array_merge( $hidden_metas, array( 'wc4bp_groups' ) );
	}

	/**
	 * Add the custom meta to the line item in the backend
	 *
	 * @param $item_id
	 * @param WC_Order_Item_Product $item
	 * @param WC_Product $_product
	 */
	public function add_after_oder_item_meta( $item_id, $item, $_product ) {
		if ( isset( $item['wc4bp_groups'] ) ) {
			$groups = json_decode( $item['wc4bp_groups'], true );
			echo '<table cellspacing="0" class="display_meta">';
			$final_groups = array();
			foreach ( $groups as $group_id => $group_name ) {
				$option_group   = $this->get_product_group( absint( $_product->get_id() ), $group_id );
				$final_groups[] = $option_group;
			}
			$not_optional_groups = $this->get_product_groups_not_optional( absint( $_product->get_id() ) ); //Process the not optional groups set in the product
			if ( ! empty( $not_optional_groups ) ) {
				$final_groups = array_merge( $final_groups, $not_optional_groups );
			}

			foreach ( $final_groups as $group_id => $group ) {
				$group_obj = new BP_Groups_Group( $group->group_id );
				$link      = bp_get_group_permalink( $group_obj );
				$role      = '';
				if ( $group->member_type == '1' ) {
					$role = '(' . wc4bp_groups_manager::translation( "Moderator" ) . ')';
				} else if ( $group->member_type == '2' ) {
					$role = '(' . wc4bp_groups_manager::translation( "Admin" ) . ')';
				}
				$optional = '';
				if ( $group->is_optional == '1' ) {
					$optional = '(' . wc4bp_groups_manager::translation( "Optional" ) . ')';
				}
				$groups_str[] = '<a target="_blank" href="' . esc_attr( $link ) . '" >' . $group->group_name . ' ' . $role . ' ' . $optional . ' </a>';
			}

			echo '<tr><th>' . wc4bp_groups_manager::translation( "BuddyPress Group(s)" ) . ':</th><td>' . $groups_str = implode( ', ', $groups_str ) . '</td></tr>';
			echo '</table>';
		}
	}

	/**
	 * Process the item meta to show in the order in the front
	 *
	 * @param $output
	 * @param WC_Order_Item_Meta $itemMeta
	 *
	 * @return mixed
	 */
	public function on_order_items_meta_display( $output, $itemMeta ) {
		$meta_list = array();
		foreach ( $itemMeta->get_formatted() as $meta ) {
			if ( $meta['key'] == 'wc4bp_groups' ) {
				$groups      = json_decode( $meta['value'], true );
				$groups_str  = implode( ', ', $groups );
				$meta_list[] = '
						<dt class="variation-' . sanitize_html_class( sanitize_text_field( $meta['key'] ) ) . '">' . wc4bp_groups_manager::translation( "BuddyPress Group(s)" ) . ':</dt>
						<dd class="variation-' . sanitize_html_class( sanitize_text_field( $meta['key'] ) ) . '">' . wp_kses_post( wpautop( $groups_str ) ) . '</dd>
					';
			} else {
				$meta_list[] = '
						<dt class="variation-' . sanitize_html_class( sanitize_text_field( $meta['key'] ) ) . '">' . wp_kses_post( $meta['label'] ) . ':</dt>
						<dd class="variation-' . sanitize_html_class( sanitize_text_field( $meta['key'] ) ) . '">' . wp_kses_post( wpautop( make_clickable( $meta['value'] ) ) ) . '</dd>
					';
			}
		}
		$output = '<dl class="variation">' . implode( '', $meta_list ) . '</dl>';

		return $output;
	}

	/**
	 * Process the item meta to show in the thank you page
	 *
	 * @param String $html
	 * @param WC_Order_Item_Product $item
	 * @param array $args
	 *
	 * @return mixed
	 */
	public function on_display_items_meta( $html, $item, $args ) {
		$strings = array();
		foreach ( $item->get_formatted_meta_data() as $meta_id => $meta ) {
			if ( $meta->key == 'wc4bp_groups' ) {
				$groups     = json_decode( $meta->value, true );
				$groups_str = implode( ', ', $groups );
				$value      = $args['autop'] ? wp_kses_post( $groups_str ) : wp_kses_post( $groups_str );
				$strings[]  = '<strong class="wc-item-meta-label">' . wc4bp_groups_manager::translation( "BuddyPress Group(s)" ) . ':</strong> ' . $value;
			} else {
				$value     = $args['autop'] ? wp_kses_post( wpautop( make_clickable( $meta->display_value ) ) ) : wp_kses_post( make_clickable( $meta->display_value ) );
				$strings[] = '<strong class="wc-item-meta-label">' . wp_kses_post( $meta->display_key ) . ':</strong> ' . $value;
			}
		}

		if ( $strings ) {
			$html = $args['before'] . implode( $args['separator'], $strings ) . $args['after'];
		}

		return $html;
	}

	public function on_process_complete( $order_id, $from, $to, $order ) {
		if ( bp_is_active( 'groups' ) ) {
			$order    = new WC_Order( $order_id );
			$customer = $order->get_user();
			if ( false !== $customer ) {
				$items = $order->get_items();
				/** @var WC_Product $item */
				foreach ( $items as $key => $item ) {
					$product      = $order->get_product_from_item( $item );
					$final_groups = array();
					if ( isset( $item['wc4bp_groups'] ) ) { //Process all selected groups by the user when buy the product
						$groups = json_decode( $item['wc4bp_groups'], true );
						foreach ( $groups as $group_id => $group_name ) {
							$option_group              = $this->get_product_group( absint( $product->get_id() ), $group_id );
							$final_groups[ $group_id ] = $option_group;
						}
					}
					$not_optional_groups = $this->get_product_groups_not_optional( absint( $product->get_id() ) ); //Process the not optional groups set in the product
					if ( ! empty( $not_optional_groups ) ) {
						$final_groups = array_merge( $final_groups, $not_optional_groups );
					}
					foreach ( $final_groups as $group_id => $group ) { //Process all groups related to the current item
						$final_trigger = ( ! isset( $group->trigger ) ) ? 'completed' : $group->trigger;
						if ( stripos( $to, $final_trigger ) !== false ) { //Check the trigger set for the group
							$is_admin = 0;
							$is_mod   = 0;
							if ( '2' === $group->member_type ) {
								$is_admin = 1;
								$is_mod   = 0;
							} elseif ( '1' === $group->member_type ) {
								$is_admin = 0;
								$is_mod   = 1;
							}
							wc4bp_groups_model::add_member_to_group( $group->group_id, $customer->ID, $is_admin, $is_mod );
						}
					}
				}
			}
		}
	}

	private function process_final_group() {

	}

	/**
	 * Add new tab to general product tabs
	 *
	 * @param $sections
	 *
	 * @return mixed
	 */
	public function addProductOptionSection( $sections ) {
		$sections[ wc4bp_groups_manager::getSlug() ] = array(
			'label'  => wc4bp_groups_manager::translation( 'WC4BP Groups' ),
			'target' => wc4bp_groups_manager::getSlug(),
			'class'  => array(),
		);

		return $sections;
	}

	/**
	 * Add content to generated tab
	 */
	public function addProductOptionPanelTab() {
		global $woocommerce, $post;
		$groups_json = get_post_meta( $post->ID, '_wc4bp_groups_json', true );
		$groups_json = html_entity_decode( $groups_json );
		if ( ! empty( $groups_json ) ) {
			$groups = json_decode( $groups_json );
		}
		include WC4BP_GROUP_VIEW_PATH . 'woo_tab_conatiner.php';
	}

	private function show_woo_tab_search_for_group() {
		global $woocommerce, $post;
		include WC4BP_GROUP_VIEW_PATH . 'woo_tab_search.php';
	}

	private function show_woo_tab_item_for_group( $post_id, $group ) {
		include WC4BP_GROUP_VIEW_PATH . 'woo_tab_item.php';
	}

	/**
	 * Save option selected into the tabs
	 *
	 * @param $post_id
	 * @param $post
	 */
	public static function saveProductOptionsFields( $post_id, $post ) {
		if ( bp_is_active( 'groups' ) ) {
			$wc4bp_groups_json     = esc_attr( $_POST['_wc4bp_groups_json'] );
			$wc4bp_groups_json_old = get_post_meta( $post_id, '_wc4bp_groups_json', true );
			if ( ! empty( $wc4bp_groups_json ) ) {
				if ( $wc4bp_groups_json != $wc4bp_groups_json_old ) {
					update_post_meta( $post_id, '_wc4bp_groups_json', esc_attr( $wc4bp_groups_json ) );
				}
			} else {
				if ( ! empty( $wc4bp_groups_json_old ) ) {
					delete_post_meta( $post_id, '_wc4bp_groups_json' );
				}
			}
		}
	}

	/**
	 * Add the view to select the group in the product page
	 */
	public function add_field_to_product_page() {
		global $product;
		$groups_json    = get_post_meta( $product->get_id(), '_wc4bp_groups_json', true );
		$groups_json    = html_entity_decode( $groups_json );
		$groups_to_show = array();
		if ( ! empty( $groups_json ) ) {
			$groups = json_decode( $groups_json );
			if ( is_array( $groups ) ) {
				foreach ( $groups as $group ) {
					if ( $group->is_optional == '1' ) {
						$groups_to_show[ $group->group_id ] = $group->group_name;
					}
				}
			}
		}

		if ( ! empty( $groups_to_show ) ) {
			$this->output_checkbox( array(
				'id'            => '_bp_group[]',
				'wrapper_class' => '_bp_group_field',
				'label'         => wc4bp_groups_manager::translation( "Select BuddyPress Group(s)" ),
				'options'       => $groups_to_show,
			) );
			wp_enqueue_style( 'wc4bp-groups', WC4BP_GROUP_CSS_PATH . 'wc4bp-groups.css', array(), wc4bp_groups_manager::getVersion() );
		}
	}

	/**
	 * Output a checkbox input box.
	 *
	 * @param array $field
	 */
	public function output_checkbox( $field ) {
		global $thepostid, $post;

		$thepostid              = empty( $thepostid ) ? $post->ID : $thepostid;
		$field['class']         = isset( $field['class'] ) ? $field['class'] : 'select short';
		$field['style']         = isset( $field['style'] ) ? $field['style'] : '';
		$field['wrapper_class'] = isset( $field['wrapper_class'] ) ? $field['wrapper_class'] : '';
		$field['value']         = isset( $field['value'] ) ? $field['value'] : get_post_meta( $thepostid, $field['id'], true );
		$field['name']          = isset( $field['name'] ) ? $field['name'] : $field['id'];

		echo '<fieldset class="form-field ' . esc_attr( $field['id'] ) . '_field ' . esc_attr( $field['wrapper_class'] ) . '"><legend>' . wp_kses_post( $field['label'] ) . '</legend><ul class="wc4bp-group-radios">';

		foreach ( $field['options'] as $key => $value ) {

			echo '<li><label><input
				name="' . esc_attr( $field['name'] ) . '"
				value="' . esc_attr( $key ) . '"
				type="checkbox"
				class="' . esc_attr( $field['class'] ) . '"
				style="' . esc_attr( $field['style'] ) . '"
				' . checked( esc_attr( $field['value'] ), esc_attr( $key ), false ) . '
				/> ' . esc_html( $value ) . '</label>
		</li>';
		}
		echo '</ul>';

		if ( ! empty( $field['description'] ) ) {

			if ( isset( $field['desc_tip'] ) && false !== $field['desc_tip'] ) {
				echo wc_help_tip( $field['description'] );
			} else {
				echo '<span class="description">' . wp_kses_post( $field['description'] ) . '</span>';
			}
		}

		echo '</fieldset>';
	}

	/**
	 * When added to cart, save any forms data
	 *
	 * @param mixed $cart_item_meta
	 * @param mixed $product_id
	 *
	 * @return mixed
	 */
	public function add_cart_item_data( $cart_item_meta, $product_id ) {
		if ( ! empty( $_POST['_bp_group'] ) ) {
			$groups = array();
			if ( is_array( $_POST['_bp_group'] ) ) {
				$groups = array_map( 'esc_attr', $_POST['_bp_group'] );
			} else {
				$groups[] = esc_attr( $_POST['_bp_group'] );
			}
			if ( ! empty( $groups ) ) {
				$cart_item_meta['_bp_group'] = $groups;
			}
		}

		return $cart_item_meta;
	}

	/**
	 * Add field data to cart item
	 *
	 * @modifiers GFireM
	 *
	 * @param mixed $cart_item
	 * @param mixed $values
	 *
	 * @return mixed
	 */
	public function get_cart_item_from_session( $cart_item, $values ) {
		if ( ! empty( $values['_bp_group'] ) ) {
			$cart_item['_bp_group'] = $values['_bp_group'];
		}

		return $cart_item;
	}

	/**
	 * Get item data
	 *
	 * @param $item_data
	 * @param $cart_item
	 *
	 * @return array
	 */
	public function get_item_data( $item_data, $cart_item ) {
		$item_data = $this->add_data_as_meta( $item_data, $cart_item, true );

		return $item_data;
	}

	/**
	 * After ordering, add the data to the order line item
	 *
	 * @param mixed $item_id
	 * @param $cart_item
	 *
	 */
	public function add_order_item_meta( $item_id, $cart_item ) {
		$item_data = $this->add_data_as_meta( array(), $cart_item );

		if ( empty ( $item_data ) ) {
			return;
		}

		foreach ( $item_data as $key => $value ) {
			wc_add_order_item_meta( $item_id, strip_tags( $value['key'] ), strip_tags( $value['value'] ) );
		}
	}

	/**
	 * Process the data to create the stream into the cart and the order
	 *
	 * @param $item_data
	 * @param $cart_item
	 * @param bool $output
	 *
	 * @return array
	 */
	private function add_data_as_meta( $item_data, $cart_item, $output = false ) {
		if ( isset( $cart_item['_bp_group'] ) ) {
			$groups = $this->get_product_groups( $cart_item['product_id'] );
			if ( ! empty( $groups ) ) {
				$groups_str = array();
				foreach ( $groups as $group ) {
					if ( in_array( $group->group_id, $cart_item['_bp_group'] ) ) {
						$groups_str[ $group->group_id ] = $group->group_name;
					}
				}
				if ( $output ) {
					$groups_str  = implode( ', ', $groups_str );
					$item_data[] = array(
						'key'   => '<strong>' . wc4bp_groups_manager::translation( "BuddyPress Group(s)" ) . '</strong>',
						'value' => $groups_str
					);
				} else {
					$item_data[] = array(
						'key'   => 'wc4bp_groups',
						'value' => json_encode( $groups_str )
					);
				}
			}
		}

		return $item_data;
	}


	/**
	 * Get the groups configured associated to a product
	 *
	 * @param $product_id
	 *
	 * @return array
	 */
	private function get_product_groups( $product_id ) {
		$groups_json = get_post_meta( $product_id, '_wc4bp_groups_json', true );
		$groups_json = html_entity_decode( $groups_json );
		$result      = array();
		if ( ! empty( $groups_json ) ) {
			$groups = json_decode( $groups_json );
			if ( is_array( $groups ) ) {
				$result = $groups;
			}
		}

		return $result;
	}

	/**
	 * Get the groups configured to a product not optionals
	 *
	 * @param $product_id
	 *
	 * @return array
	 */
	private function get_product_groups_not_optional( $product_id ) {
		$groups_json = get_post_meta( $product_id, '_wc4bp_groups_json', true );
		$groups_json = html_entity_decode( $groups_json );
		$result      = array();
		if ( ! empty( $groups_json ) ) {
			$groups = json_decode( $groups_json );
			if ( is_array( $groups ) ) {
				foreach ( $groups as $group ) {
					if ( ! isset( $group->is_optional ) || $group->is_optional == '0' ) {
						$result[ $group->group_id ] = $group;
					}
				}
			}
		}

		return $result;
	}

	/**
	 * Get group save in the configuration by his id belong to product
	 *
	 * @param $product_id
	 * @param $group_id
	 *
	 * @return bool|stdClass
	 */
	private function get_product_group( $product_id, $group_id ) {
		$option_groups = $this->get_product_groups( $product_id );
		foreach ( $option_groups as $option_group ) {
			if ( intval( $option_group->group_id ) === intval( $group_id ) ) {
				return $option_group;
			}
		}

		return false;
	}


}