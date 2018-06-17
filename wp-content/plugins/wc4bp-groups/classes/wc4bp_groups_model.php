<?php
/**
 * @package        WordPress
 * @subpackage     BuddyPress, Woocommerce, WC4BP, wc4bp-groups
 * @author         ThemKraft Dev Team
 * @copyright      2017, Themekraft
 * @link           http://themekraft.com/store/woocommerce-buddypress-integration-wordpress-plugin/
 * @license        http://www.opensource.org/licenses/gpl-2.0.php GPL License
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class wc4bp_groups_model {

	public function __construct() {
		add_action( 'wp_ajax_wc4bp_group_search', array( $this, 'group_search' ) );
		add_action( 'wp_ajax_wc4bp_get_group_view', array( $this, 'get_group_view' ) );
	}

	/**
	 * Filter the groups from ajax
	 */
	public function group_search() {
		check_ajax_referer( 'wc4bp-nonce', 'security' );
		$groups_founded = array();
		if ( ! empty( $_GET['term'] ) ) {
			$groups = $this->search_groups( wc_clean( stripslashes( $_GET['term'] ) ) );
			if ( ! empty( $groups['groups'] ) ) {
				foreach ( $groups['groups'] as $group_id ) {
					$group                                 = new BP_Groups_Group( $group_id->group_id );
					$groups_founded[ $group_id->group_id ] = esc_attr( $group->name );
				}
			}
		}
		wp_send_json( $groups_founded );
	}

	/**
	 * Get a list of groups, filtered by a search string, including the hidden groups.
	 *
	 *
	 * @param string $filter Search term. Matches against 'name' and
	 *                             'description' fields.
	 *
	 * @return array {
	 * @type array $groups Array of matched and paginated group IDs,
	 * @type int $total Total count of groups matching the query.
	 * }
	 */
	public function search_groups( $filter ) {
		$args = array(
			'search_terms' => $filter,
			'show_hidden'  => true,
		);

		$groups = BP_Groups_Group::get( $args );

		// Modify the results to match the old format.
		$paged_groups = array();
		$i            = 0;
		foreach ( $groups['groups'] as $group ) {
			$paged_groups[ $i ]           = new stdClass;
			$paged_groups[ $i ]->group_id = $group->id;
			$i ++;
		}

		return array(
			'groups' => $paged_groups,
			'total'  => $groups['total'],
		);
	}

	/**
	 * Return the view to add a group
	 */
	public function get_group_view() {
		check_ajax_referer( 'wc4bp-nonce', 'security' );
		if ( ! empty( $_POST['group'] ) && wp_doing_ajax() && is_string( $_POST['group'] ) ) {
			$data = json_decode( stripslashes( $_POST['group'] ) );
			if ( is_object( $data ) ) {
				$group             = new stdClass();
				$group->group_id   = esc_attr( $data->id );
				$group->group_name = esc_attr( $data->text );
				ob_start();
				include WC4BP_GROUP_VIEW_PATH . 'woo_tab_item.php';
				$str = ob_get_clean();
				echo "$str";
			}
		}

		die();
	}

	/**
	 * Add member to group as admin
	 * credits go to boon georges. This function is coppyed from the group management plugin.
	 *
	 * @package wc4bp_groups
	 *
	 * @param $group_id
	 * @param bool $user_id
	 * @param int $is_admin
	 * @param int $is_mod
	 *
	 * @return bool
	 */
	public static function add_member_to_group( $group_id, $user_id = false, $is_admin = 0, $is_mod = 0 ) {
		global $bp;
		if ( ! $user_id ) {
			$user_id = $bp->loggedin_user->id;
		}
		/* Check if the user has an outstanding invite, is so delete it. */
		if ( groups_check_user_has_invite( $user_id, $group_id ) ) {
			groups_delete_invite( $user_id, $group_id );
		}
		/* Check if the user has an outstanding request, is so delete it. */
		if ( groups_check_for_membership_request( $user_id, $group_id ) ) {
			groups_delete_membership_request( $user_id, $group_id );
		}
		/* User is already a member, just return true */
		if ( groups_is_user_member( $user_id, $group_id ) ) {
			return true;
		}
		if ( ! $bp->groups->current_group ) {
			$bp->groups->current_group = new BP_Groups_Group( $group_id );
		}
		$new_member                = new BP_Groups_Member;
		$new_member->group_id      = $group_id;
		$new_member->user_id       = $user_id;
		$new_member->inviter_id    = 0;
		$new_member->is_admin      = $is_admin;
		$new_member->is_mod        = $is_mod;
		$new_member->user_title    = '';
		$new_member->date_modified = gmdate( "Y-m-d H:i:s" );
		$new_member->is_confirmed  = 1;
		if ( ! $new_member->save() ) {
			return false;
		}
		/* Record this in activity streams */
		groups_record_activity( array(
			'user_id' => $user_id,
			'action'  => apply_filters( 'groups_activity_joined_group', sprintf( __( '%s joined the group %s', 'buddyforms' ), bp_core_get_userlink( $user_id ), '<a href="' . bp_get_group_permalink( $bp->groups->current_group ) . '">' . esc_html( $bp->groups->current_group->name ) . '</a>' ) ),
			'type'    => 'joined_group',
			'item_id' => $group_id
		) );
		/* Modify group meta */
		groups_update_groupmeta( $group_id, 'total_member_count', (int) groups_get_groupmeta( $group_id, 'total_member_count' ) + 1 );
		groups_update_groupmeta( $group_id, 'last_activity', gmdate( "Y-m-d H:i:s" ) );
		do_action( 'groups_join_group', $group_id, $user_id );

		return true;
	}

}