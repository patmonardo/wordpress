<?php
/**
 * @package        WordPress
 * @subpackage     BuddyPress, Woocommerce, WC4BP
 * @author         ThemKraft Dev Team
 * @copyright      2017, Themekraft
 * @link           http://themekraft.com/store/woocommerce-buddypress-integration-wordpress-plugin/
 * @license        http://www.opensource.org/licenses/gpl-2.0.php GPL License
 */

// No direct access is allowed
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class wc4bp_groups_required {

	public function __construct() {
		add_action( 'init', array( $this, 'setup_init' ), 1, 1 );
	}

	public function setup_init() {
		// Only Check for requirements in the admin
		if ( ! is_admin() ) {
			return;
		}
		add_action( 'wc4bp_group_tgmpa_register', array( $this, 'setup_and_check' ) );
		add_action( 'in_admin_footer', array( $this, 'remove_woo_footer' ) );
	}

	public function remove_woo_footer() {
		$current_screen = get_current_screen();
		if ( isset( $current_screen->id ) && $current_screen->id == 'admin_page_wc4bp-groups-install-plugins' && class_exists( 'WC_Admin' ) ) {
			$this->remove_anonymous_callback_hook( 'admin_footer_text', 'WC_Admin', 'admin_footer_text' );
		}
	}

	private function remove_anonymous_callback_hook( $tag, $class, $method ) {
		$filters = $GLOBALS['wp_filter'][ $tag ];

		if ( empty ( $filters ) || empty( $filters->callbacks ) ) {
			return;
		}

		foreach ( $filters->callbacks as $priority => $filter ) {
			foreach ( $filter as $identifier => $function ) {
				if ( is_array( $function ) && is_a( $function['function'][0], $class ) && $method === $function['function'][1] ) {
					remove_filter( $tag, array( $function['function'][0], $method ), $priority );
				}
			}
		}
	}

	public static function wc4bp_groups_is_plugins_active( $slug, $is_premium ) {
		$active_plugins_basenames = get_option( 'active_plugins' );
		if ( $is_premium ) {
			$slug .= '-premium';
		}
		foreach ( $active_plugins_basenames as $plugin_basename ) {
			if ( 0 === strpos( $plugin_basename, $slug . '/' ) ) {
				return true;
			}
		}

		return false;
	}

	public static function load_plugins_dependency() {
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
	}

	public static function is_woocommerce_active() {
		self::load_plugins_dependency();

		return is_plugin_active( 'woocommerce/woocommerce.php' );
	}

	public static function is_buddypress_active() {
		self::load_plugins_dependency();

		return is_plugin_active( 'buddypress/bp-loader.php' );
	}

	public static function is_wc4bp_active() {
		self::load_plugins_dependency();

		return ( is_plugin_active( 'wc4bp-premium/wc4bp-basic-integration.php' ) || is_plugin_active( 'wc4bp/wc4bp-basic-integration.php' ) );
	}

	public static function is_woo_elem_active() {
		self::load_plugins_dependency();

		return ( is_plugin_active( 'buddyforms-woocommerce-form-elements-premium/loader.php' ) || is_plugin_active( 'buddyforms-woocommerce-form-elements/loader.php' ) );
	}

	public static function is_current_active() {
		self::load_plugins_dependency();

		return is_plugin_active( 'wc4bp-groups/wc4bp-groups.php' );
	}

	public function setup_and_check() {
		$wc4bp_slug = 'wc4bp';
		if ( self::wc4bp_groups_is_plugins_active( $wc4bp_slug, true ) ) {
			$wc4bp_slug = 'wc4bp-premium';
		}
		// Create the required required_plugins array
		$required_plugins = array(
			array(
				'name'     => 'BuddyPress',
				'slug'     => 'buddypress',
				'version'  => '2.9.0',
				'required' => true,
			),
			array(
				'name'     => 'WooCommerce',
				'slug'     => 'woocommerce',
				'version'  => '3.1.0',
				'required' => true,
			),
			array(
				'name'     => 'WC4BP -> WooCommerce BuddyPress Integration',
				'slug'     => $wc4bp_slug,
				'version'  => '3.0.15',
				'required' => true,
			),
		);

		$config = array(
			'id'           => 'wc4bp-groups',                 // Unique ID for hashing notices for multiple instances of TGMPA.
			'menu'         => 'wc4bp-groups-install-plugins', // Menu slug.
			'parent_slug'  => 'admin.php',            // Parent menu slug.
			'capability'   => 'manage_options',    // Capability needed to view plugin install page, should be a capability associated with the parent menu used.
			'has_notices'  => true,                    // Show admin notices or not.
			'dismissable'  => false,                    // If false, a user cannot dismiss the nag message.
			'dismiss_msg'  => '',                      // If 'dismissable' is false, this message will be output at top of nag.
			'is_automatic' => false,                   // Automatically activate plugins after installation or not.
			'strings' => array(
				'notice_can_install_required'     => _n_noop(
				/* translators: 1: plugin name(s). */
					'<u>WC4BP -> Groups</u> plugin requires the following plugin: %1$s.',
					'<u>WC4BP -> Groups</u> plugin requires the following plugins: %1$s.',
					'wc4bp_groups'
				),
				'notice_can_install_recommended'  => _n_noop(
				/* translators: 1: plugin name(s). */
					'<u>WC4BP -> Groups</u> plugin recommends the following plugin: %1$s.',
					'<u>WC4BP -> Groups</u> plugin recommends the following plugins: %1$s.',
					'wc4bp_groups'
				),
				'notice_can_activate_required'    => _n_noop(
				/* translators: 1: plugin name(s). */
					'The following is a required plugin for <u>WC4BP -> Groups</u> and is currently inactive: %1$s.',
					'The following is a required plugins for <u>WC4BP -> Groups</u> and they are currently inactive: %1$s.',
					'wc4bp_groups'
				),
				'notice_ask_to_update'            => _n_noop(
				/* translators: 1: plugin name(s). */
					'The following plugin needs to be updated to its latest version to ensure maximum compatibility with this plugin: %1$s.',
					'The following plugins need to be updated to their latest version to ensure maximum compatibility with this plugin: %1$s.',
					'wc4bp_groups'
				),
			),
		);

		// Call the tgmpa function to register the required required_plugins
		wc4bp_group_tgmpa( $required_plugins, $config );
	}

}