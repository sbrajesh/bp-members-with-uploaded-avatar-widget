<?php
/**
 * Plugin Name: BP Members With Uploaded Avatars Widget
 * Author: Brajesh Singh
 * Plugin URI: http://buddydev.com/plugins/buddypress-members-with-uploaded-avatars-widget/
 * Author URI: http://BuddyDev.com/members/sbrajesh/
 * Version:1.0.5
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

	/**
	 * Singleton instance.
	 *
	 * @var BP_Members_With_Avatar_Helper
	 */
	private static $instance = null;

	/**
	 * Absolute path to this plugin's directory.
	 *
	 * @var string
	 */
	private $path;

	/**
	 * Temporary option for keeping widget args.
	 *
	 * @var array
	 */
	private $widget_args = null;

	/**
	 * @var array data store.
	 */
	private $data = array();
	/**
	 * Constructor
	 */
	private function __construct() {

	    $this->path = plugin_dir_path( __FILE__ );

	    add_action( 'bp_loaded', array( $this, 'load' ) );
		// record on new avatar upload.
		add_action( 'xprofile_avatar_uploaded', array( $this, 'log_uploaded' ) );
		// on avatar delete.
		add_action( 'bp_core_delete_existing_avatar', array( $this, 'log_deleted' ) );

		// show entry.
		add_action( 'bp_members_with_uploaded_avatar_entry', array(
			$this,
			'member_entry',
		), 10, 2 );// remove this function from the action and use your own to customize the entry.
	}

	/**
	 * Get the singleton object
	 *
	 * @return BP_Members_With_Avatar_Helper object
	 */
	public static function get_instance() {

		if ( is_null( self::$instance ) ) {
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

	/**
	 * Record new avatar upload in meta.
     *
     * @param int $user_id user id.
	 */
	public function log_uploaded( $user_id ) {
		bp_update_user_meta( $user_id, 'has_avatar', 1 );
	}

	/**
	 * Delete the meta on avatar delete.
	 *
	 * @param array $args see args below.
	 */
	public function log_deleted( $args ) {

		if ( $args['object'] != 'user' ) {
			return;
		}
		$user_id = empty( $args['item_id'] ) ? 0 : absint( $args['item_id'] );

		if ( ! $user_id ) {
			if ( bp_is_user() && ( bp_is_my_profile() || is_super_admin() ) ) {
				$user_id = bp_displayed_user_id();
			} else {
				$user_id = bp_loggedin_user_id();
			}
		}

		// we are sure it was user avatar delete
		// remove the log from user meta.
		bp_delete_user_meta( $user_id, 'has_avatar' );
	}

	/**
	 * Get users who have avatar uploaded.
	 *
	 * @param int    $max how many users need to be fetched.
	 * @param string $type user listing type.
	 *
	 * @return array
	 */
	public function get_users_with_avatar( $max, $type = 'random' ) {
		global $wpdb;

		// Find all users with uploaded avatar.
		$ids = $wpdb->get_col( "SELECT user_id FROM {$wpdb->usermeta } WHERE meta_key='has_avatar'" );//we don't need to check for meta value anyway

		if ( empty( $ids ) ) {
			return false;
		}

		// ask buddypress to return the users based on type, I did not write a query as it will need to be redoing the same thing as in the called function
		// with BP 1.7, we don't need the above query as the class is capable of meta query, will add it in next version though.
		if ( class_exists( 'BP_User_Query' ) ) {
			$args = array(
				'type'            => $type,
				'per_page'        => $max,
				'include'         => $ids,
				'populate_extras' => false,
			);

			$args = apply_filters( 'bp_member_with_uploaded_avatar_query_args', $args );

			$qusers = new BP_User_Query( $args );

			$users = array_values( $qusers->results );

		} else {
			// pre BP 1.7
			$users = BP_Core_User::get_users( $type, $max, 1, 0, $ids, false, false );// I know, we are repeating here.
			$users = $users['users'];
		}
		return $users;

	}

	/**
	 * Print user's list.
	 *
	 * @param array $args see args below.
	 */
	public function list_users( $args ) {

		$args = wp_parse_args( (array) $args,
			array(
				'type'                  => 'random',
				'max'                   => 5,
				'size'                  => 'full',
				'width'                 => 50,
				'height'                => 50,
				'use_default_theme'     => 0,
				'excluded_users'        => array(),
				'excluded_member_types' => array(),
				'included_member_types' => array(),
			)
		);

		if ( ! empty( $args['use_default_theme'] ) ) {
			$this->load_default_theme_members_list( $args );
		} else {
			$this->list_legacy( $args );
		}

		echo '<div class="clear"></div>';
	}


	/**
	 * Load members-loop.php from the default theme.
	 *
	 * @param array $args widget args.
	 */
	public function load_default_theme_members_list( $args ) {
		$this->widget_args = $args;

		// add filter.
		add_filter( 'bp_after_has_members_parse_args', array( $this, 'filter_members_list' ) );

		// load list.
		bp_locate_template( array( 'members/members-loop.php' ), true );

		// remove filter.
		remove_filter( 'bp_after_has_members_parse_args', array( $this, 'filter_members_list' ) );

		// reset data.
		$this->widget_args = null;
	}

	/**
	 * Filter members list.
	 *
	 * @param array $args bp_has_members args.
	 *
	 * @return array
	 */
	public function filter_members_list( $args ) {

		if ( empty( $this->widget_args ) ) {
			return $args;
		}

		$args['type']       = $this->widget_args['type'];
		$args['per_page']   = $this->widget_args['max'];
		$args['max']        = $this->widget_args['max'];

		$args['member_type__in']     = isset( $this->widget_args['included_member_types'] ) ? $this->widget_args['included_member_types'] : '';
		$args['member_type__not_in'] = isset( $this->widget_args['excluded_member_types'] ) ? $this->widget_args['excluded_member_types'] : '';
		$args['exclude']             = isset( $this->widget_args['excluded_users'] ) ? $this->widget_args['excluded_users'] : false;

		if ( empty( $this->widget_args['avatar_option'] ) ) {

			$args['meta_key']   = 'has_avatar';
			$args['meta_value'] = 1;
		}

		return $args;
	}
	/**
	 * List legacy style.
	 *
	 * @param array $args see args.
	 */
	public function list_legacy( $args ) {

		$query_args = array(
			'type'                => $args['type'],
			'per_page'            => $args['max'],
			'populate_extras'     => false,
			'member_type__in'     => ! empty( $args['included_member_types'] ) ? $args['included_member_types'] : '',
			'member_type__not_in' => ! empty( $args['excluded_member_types'] ) ? $args['excluded_member_types'] : '',
			'exclude'             => ! empty( $args['excluded_users'] ) ? $args['excluded_users'] : false,
		);

		// if the option to include member with no avatar is not ticked, let us filter more.
		if ( empty( $args['avatar_option'] ) ) {
			$query_args['meta_key']   = 'has_avatar';
			$query_args['meta_value'] = 1;
		}

		$qusers = new BP_User_Query( $query_args );

		$users = array_values( $qusers->results );

		$users = apply_filters( 'bp_member_with_uploaded_avatar_users', $users );

		if ( ! empty( $users ) ) : ?>

			<?php foreach ( $users as $user ) : ?>
				<?php do_action( 'bp_members_with_uploaded_avatar_entry', $user, $args );
				// use this to modify the entry as you want. ?>
			<?php endforeach; ?>

		<?php else : ?>
            <div class="error">
                <p> <?php _e( 'No members found!', 'bp-members-with-uploaded-avatar-widget' ); ?> </p>
            </div>

		<?php endif;
	}

        /**
        * Print single user entry details.
        *
        * @param Object $user user details.
        * @param array  $args args.
        */
        public function member_entry( $user, $args ) {
        ?>
        <a href="<?php echo bp_core_get_user_domain( $user->id ) ?>">
			<?php echo bp_core_fetch_avatar( array(
					'type'    => $args['size'],
					'width'   => $args['width'],
					'height'  => $args['height'],
					'item_id' => $user->id,
				)
			);
			?>
        </a>
		<?php
	}


	/**
	 * Save a random piece of data in global scope.
	 *
	 * @param string $key unique name.
	 * @param mixed  $data data value.
	 */
	public function add_data( $key, $data ) {
		$this->data[ $key ] = $data;
	}

	/**
	 * Get teh data associated with given key.
	 *
	 * @param string $key unique name.
	 *
	 * @return mixed|null
	 */
	public function get_data( $key ) {
		if ( isset( $this->data[ $key ] ) ) {
			return $this->data[ $key ];
		}

		return null;
	}

	/**
	 * Do we have data associated with this key?
	 *
	 * @param string $key unique name.
	 *
	 * @return bool
	 */
	public function has_data( $key ) {
		return isset( $this->data[ $key ] );
	}

}

BP_Members_With_Avatar_Helper::get_instance();
