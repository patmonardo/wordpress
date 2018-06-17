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

class wc4bp_groups_manager {

	private static $plugin_slug = 'wc4bp_groups';
	protected static $version = '1.2.1';

	public function __construct() {
		require_once WC4BP_GROUP_CLASSES_PATH . 'wc4bp_groups_log.php';
		new wc4bp_groups_log();
		try {
			//loading_dependency
			require_once WC4BP_GROUP_CLASSES_PATH . 'wc4bp_groups_model.php';
			require_once WC4BP_GROUP_CLASSES_PATH . 'wc4bp_groups_woo.php';
			new wc4bp_groups_model();
			new wc4bp_groups_woo();

			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_js' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_style' ) );

			if ( wc4bp_groups_required::is_woo_elem_active() ) {
				require_once WC4BP_GROUP_CLASSES_PATH . 'wc4bp_groups_woo_elem_integration.php';
				new wc4bp_groups_woo_elem_integration();
			}
		} catch ( Exception $ex ) {
			wc4bp_groups_log::log( array(
				'action'         => get_class( $this ),
				'object_type'    => wc4bp_groups_manager::getSlug(),
				'object_subtype' => 'loading_dependency',
				'object_name'    => $ex->getMessage(),
			) );
		}
	}

	/**
	 * Include styles in admin
	 *
	 * @param $hook
	 * @param bool $force
	 */
	public static function enqueue_style( $hook, $force = false ) {
		global $post;
		if ( ( ( $hook == 'post.php' || $hook == 'post-new.php' ) && $post->post_type == 'product' ) || $force ) {
			wp_enqueue_style( 'jquery' );
			wp_enqueue_style( 'wc4bp-groups', WC4BP_GROUP_CSS_PATH . 'wc4bp-groups.css', array(), wc4bp_groups_manager::getVersion() );
		}
	}

	/**
	 * Include script
	 *
	 * @param $hook
	 * @param bool $force
	 */
	public static function enqueue_js( $hook, $force = false ) {
		global $post;
		if ( ( ( $hook == 'post.php' || $hook == 'post-new.php' ) && $post->post_type == 'product' ) || $force ) {
			wp_register_script( 'wc4bp_groups', WC4BP_GROUP_JS_PATH . 'wc4bp-groups.js', array( "jquery" ), wc4bp_groups_manager::getVersion() );
			wp_enqueue_script( 'wc4bp_groups' );
			wp_localize_script( 'wc4bp_groups', 'wc4bp_groups', array(
				'ajax_url'            => admin_url( 'admin-ajax.php' ),
				'search_groups_nonce' => wp_create_nonce( "wc4bp-nonce" ),
				'is_force'            => $force,
				'general_error'       => wc4bp_groups_manager::translation( 'General Error, contact the admin. #1' ),
				'remove'              => wc4bp_groups_manager::translation( 'General Error, contact the admin. #2' ),
			) );
		}
	}

	/**
	 * Get plugins version
	 *
	 * @return mixed
	 */
	static function getVersion() {
		return self::$version;
	}

	/**
	 * Get plugins slug
	 *
	 * @return string
	 */
	static function getSlug() {
		return self::$plugin_slug;
	}

	/**
	 * Retrieve the translation for the plugins. Wrapper for @see __()
	 *
	 * @param $str
	 *
	 * @return string
	 */
	public static function translation( $str ) {
		return __( $str, 'wc4bp_groups' );
	}


	/**
	 * Display the translation for the plugins. Wrapper for @see _e()
	 *
	 * @param $str
	 */
	public static function echo_translation( $str ) {
		_e( $str, 'wc4bp_groups' );
	}

	/**
	 * Display the translation for the plugins.
	 *
	 * @param $str
	 */
	public static function echo_esc_attr_translation( $str ) {
		echo esc_attr( self::translation( $str ) );
	}
}
