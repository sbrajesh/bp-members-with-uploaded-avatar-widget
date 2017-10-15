<?php
/**
 * Plugin Name: BP Members With Uploaded Avatars Widget
 * Author: Brajesh Singh
 * Plugin URI: http://buddydev.com/plugins/buddypress-members-with-uploaded-avatars-widget/
 * Author URI: http://BuddyDev.com/members/sbrajesh/
 * Version:1.0.3
 * Description:Show the members who have uploaded avatar on a BuddyPress Based Social Network
 */

// Do not allow direct access over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}

/**
 * Helper class for keeping record of avatar change & providing list of the users
 * It is implemented as a singleton class
 */
class BP_Members_With_Avatar_Helper {

	private static $instance;

	private $path;
	private function __construct() {

	    $this->path = plugin_dir_path( __FILE__ );

	    add_action( 'bp_loaded', array( $this, 'load' ) );
		//record on new avatar upload
		add_action( 'xprofile_avatar_uploaded', array( $this, 'log_uploaded' ) );
		//on avatar delete
		add_action( 'bp_core_delete_existing_avatar', array( $this, 'log_deleted' ) );
		//show entry
		add_action( 'bp_members_with_uploaded_avatar_entry', array(
			$this,
			'member_entry'
		), 10, 2 );//remove this function from the action and use your own to customize the entry
	}

	/**
	 * Get the singleton object
	 * @return BP_Members_With_Avatar_Helper object
	 */
	public static function get_instance() {

		if ( ! isset( self::$instance ) ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Load files.
	 */
	public function load() {

	    require_once $this->path . 'bp-member-with-uploaded-avatar-widget.php';

    }
	//on new avatar upload, record it to user meta
	public function log_uploaded() {

		bp_update_user_meta( bp_loggedin_user_id(), 'has_avatar', 1 );
	}

	//on delete avatar, delete it from user meta
	public function log_deleted( $args ) {

		if ( $args['object'] != 'user' ) {
			return;
		}
		//we are sure it was user avatar delete

		//remove the log from user meta
		bp_delete_user_meta( bp_loggedin_user_id(), 'has_avatar' );
	}

	/**
	 *
	 * @global type $wpdb
	 *
	 * @param type $type
	 *
	 * @return type Return an array of Users object with the specifie
	 */
	public function get_users_with_avatar( $max, $type = 'random' ) {
		global $wpdb;

		//Find all users with uploaded avatar
		$ids = $wpdb->get_col( "SELECT user_id FROM {$wpdb->usermeta } WHERE meta_key='has_avatar'" );//we don't need to check for meta value anyway

		if ( empty( $ids ) ) {
			return false;
		}
		//ask buddypress to return the users based on type, I did not write a query as it will need to be redoing the samething as in the called function
		//with BP 1.7, we don't need the above query as the class is capable of meta query, will add it in next vesrion though

		if ( class_exists( 'BP_User_Query' ) ) {
			$args = array(
				'type'            => $type,
				'per_page'        => $max,
				'include'         => $ids,
				'populate_extras' => false
			);

			$args = apply_filters( 'bp_member_with_uploaded_avatar_query_args', $args );

			$qusers = new BP_User_Query( $args );

			$users = array_values( $qusers->results );


		} else {
			//pre 1.7
			$users = BP_Core_User::get_users( $type, $max, 1, 0, $ids, false, false );//I know, we are repeating here
			$users = $users['users'];
		}


		return $users;

	}

	//helper to list users with avatars, will extend in future to show more details
	public function list_users( $args ) {

		$args = wp_parse_args( (array) $args,
			array(
				'type'   => 'random',
				'max'    => 5,
				'size'   => 'full',
				'width'  => 50,
				'height' => 50,
			) );

		extract( $args );
		//$avatar_option is not empty if the admin has checked show member without avatar too
		if ( ! empty( $avatar_option ) ) {

			if ( class_exists( 'BP_User_Query' ) ) {

				$qusers = new BP_User_Query( array(
						'type'            => $type,
						'per_page'        => $max,
						'populate_extras' => false,
					)
				);

				$users = array_values( $qusers->results );

			} else {

				$users = BP_Core_User::get_users( $type, $max );//I know, we are repeating here
				$users = $users['users'];
			}

		} else {
			//it will be called when only members with uploaded avatar are included
			$users = self::get_users_with_avatar( $max, $type );
		}

		$users = apply_filters( 'bp_member_with_uploaded_avatar_users', $users );

		if ( ! empty( $users ) ): ?>

			<?php foreach ( $users as $user ): ?>
				<?php do_action( 'bp_members_with_uploaded_avatar_entry', $user, $args );//use this to modify the entry as you want ?>

			<?php endforeach; ?>

		<?php else: ?>

            <div class="error"><p> No members found! </p></div>

		<?php endif; ?>
		<?php

		echo '<div class="clear"></div>';


	}

	public function member_entry( $user, $args ) {

		extract( $args );
		?>

        <a href="<?php echo bp_core_get_user_domain( $user->id ) ?>">
			<?php echo bp_core_fetch_avatar( array(
					'type'    => $size,
					'width'   => $width,
					'height'  => $height,
					'item_id' => $user->id
				)
			) ?>
        </a>


		<?php

	}

}

BP_Members_With_Avatar_Helper::get_instance();