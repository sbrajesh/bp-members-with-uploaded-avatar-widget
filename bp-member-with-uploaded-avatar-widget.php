<?php
// Do not allow direct access over web.
if ( ! defined( 'ABSPATH' ) ) {
	exit( 0 );
}
/**
 * Avatar Widget.
 */
class BP_Members_With_Uploaded_Avatar_Widget extends WP_Widget {

	/**
	 * Constructor.
	 */
	public function __construct() {
		parent::__construct( false, __( 'Members With Uploaded Avatars' ) );
	}

	/**
	 * Output the content/list members here
	 *
	 * @param array $args widget args.
	 * @param array $instance Instance of the widget.
	 */
	public function widget( $args, $instance ) {

		extract( $args );

		echo $before_widget . $before_title . $instance['title'] . $after_title;

		$helper = BP_Members_With_Avatar_Helper::get_instance();

		$helper->list_users( $instance );

		echo $after_widget;
	}


	/**
	 * Update widget settings.
	 *
	 * @param array $new_instance new instance.
	 * @param array $old_instance old instance.
	 *
	 * @return array
	 */
	public function update( $new_instance, $old_instance ) {

		$instance = $old_instance;

		$instance['title'] = esc_html( $new_instance['title'] );

		$instance['max']           = absint( $new_instance['max'] );
		$instance['avatar_option'] = absint( $new_instance['avatar_option'] );
		$instance['type']          = esc_html( $new_instance['type'] );
		$instance['size']          = esc_html( $new_instance['size'] );
		$instance['height']        = absint( $new_instance['height'] );
		$instance['width']         = absint( $new_instance['width'] );

		return $instance;
	}


	/**
	 * Render form.
	 *
	 * @param object $instance current instance.
	 */
	public function form( $instance ) {

		$default = array(
			'title'  => __( 'Recent Members' ),
			'type'   => 'random',
			'max'    => 5,
			'size'   => 'full',
			'width'  => 50,
			'height' => 50,
		);

		$instance = (array) $instance;// type cast to array.

		$instance = wp_parse_args( $instance, $default );
		extract( $instance );

		?>

		<p>
			<label for="bp-member-with-avatar-title"><strong><?php _e( 'Title:' ); ?> </strong>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
				       name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
				       value="<?php echo esc_attr( $title ); ?>" style="width: 100%"/>
			</label>
		</p>
		<p>
			<label for="bp-member-with-avatar-max"><?php _e( 'Maximum no. of Users to show' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'max' ); ?>"
				       name="<?php echo $this->get_field_name( 'max' ); ?>" type="text"
				       value="<?php echo esc_attr( $max ); ?>" style="width: 30%"/>
			</label>
		</p>
		<p>
			<label for="bp-member-with-avatar-type"><?php _e( 'Order By' ); ?>
				<select class="widefat" id="<?php echo $this->get_field_id( 'type' ); ?>"
				        name="<?php echo $this->get_field_name( 'type' ); ?>" style="width: 30%">
					<option value="random" <?php selected( $type, 'random' ); ?> >Random</option>
					<option value="alphabetical" <?php selected( $type, 'alphabetical' ); ?> >Alphabetically</option>
					<option value="active" <?php selected( $type, 'active' ); ?> >Active</option>
					<option value="newest" <?php selected( $type, 'newest' ); ?> >Recently Joined</option>
					<option value="popular" <?php selected( $type, 'popular' ); ?> >Popularity</option>
				</select>

			</label>
		</p>
		<p>
			<label>
				<input type='checkbox' name="<?php echo $this->get_field_name( 'avatar_option' ); ?>"
				       id="<?php echo $this->get_field_id( 'avatar_option' ); ?>"
				       value="1" <?php echo checked( 1, $avatar_option ); ?> />
				<?php _e( 'Show Members without avatars too?' ); ?>
			</label>
		</p>
		<p>
			<label for="bp-member-with-avatar-size"><?php _e( 'Avatar Size' ); ?>
				<select class="widefat" id="<?php echo $this->get_field_id( 'size' ); ?>"
				        name="<?php echo $this->get_field_name( 'size' ); ?>" style="width: 30%">
					<option value="full" <?php selected( $size, 'full' ); ?> >Full</option>
					<option value="thumb" <?php selected( $size, 'thumb' ); ?> >Thumb</option>


				</select>

			</label>
		</p>
		<p>

			<label for="bp-member-with-avatar-height"><?php _e( 'Avatar height' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'height' ); ?>"
				       name="<?php echo $this->get_field_name( 'height' ); ?>" type="text"
				       value="<?php echo esc_attr( $height ); ?>" style="width: 30%"/>
			</label>
		</p>
		<p>
			<label for="bp-member-with-avatar-width"><?php _e( 'Avatar Widtht' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'width' ); ?>"
				       name="<?php echo $this->get_field_name( 'width' ); ?>" type="text"
				       value="<?php echo esc_attr( $width ); ?>" style="width: 30%"/>
			</label>
		</p>

		<?php
	}

}

/**
 * Register the widget
 */
add_action( 'bp_widgets_init', 'bp_register_member_with_avatar_widget' );

function bp_register_member_with_avatar_widget() {

	register_widget( 'BP_Members_With_Uploaded_Avatar_Widget' );

}
