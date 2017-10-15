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
		parent::__construct( false, __( 'Members With Uploaded Avatars', 'bp-members-with-uploaded-avatar-widget' ) );
	}

	/**
	 * Output the content/list members here
	 *
	 * @param array $args widget args.
	 * @param array $instance Instance of the widget.
	 */
	public function widget( $args, $instance ) {

		echo $args['before_widget'] . $args['before_title'] . $instance['title'] . $args['after_title'];

		$helper = BP_Members_With_Avatar_Helper::get_instance();

		$helper->list_users( $instance );

		echo $args['after_widget'];
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
			'title'  => __( 'Recent Members', 'bp-members-with-uploaded-avatar-widget' ),
			'type'   => 'random',
			'max'    => 5,
			'size'   => 'full',
			'width'  => 50,
			'height' => 50,
		);

		$instance = (array) $instance;// type cast to array.

		$instance = wp_parse_args( $instance, $default );

		$max           = absint( $instance['max'] );
		$title         = esc_html( $instance['title'] );
		$type          = $instance['type'];
		$avatar_option = isset( $instance['avatar_option'] ) ? $instance['avatar_option'] : 0;
		$size          = $instance['size'];
		$width         = $instance['width'];
		$height        = $instance['height'];
		?>

		<p>
			<label for="bp-member-with-avatar-title"><strong><?php _e( 'Title:', 'bp-members-with-uploaded-avatar-widget' ); ?> </strong>
				<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>"
				       name="<?php echo $this->get_field_name( 'title' ); ?>" type="text"
				       value="<?php echo esc_attr( $title ); ?>" style="width: 100%"/>
			</label>
		</p>
		<p>
			<label for="bp-member-with-avatar-max"><?php _e( 'Maximum no. of Users to show', 'bp-members-with-uploaded-avatar-widget' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'max' ); ?>"
				       name="<?php echo $this->get_field_name( 'max' ); ?>" type="text"
				       value="<?php echo esc_attr( $max ); ?>" style="width: 30%"/>
			</label>
		</p>
		<p>
			<label for="bp-member-with-avatar-type"><?php _e( 'Order By', 'bp-members-with-uploaded-avatar-widget' ); ?>
				<select class="widefat" id="<?php echo $this->get_field_id( 'type' ); ?>" name="<?php echo $this->get_field_name( 'type' ); ?>" style="width: 30%">
					<option value="random" <?php selected( $type, 'random' ); ?> ><?php _e( 'Random', 'bp-members-with-uploaded-avatar-widget' ); ?></option>
					<option value="alphabetical" <?php selected( $type, 'alphabetical' ); ?> ><?php _e( 'Alphabetically', 'bp-members-with-uploaded-avatar-widget' ); ?></option>
					<option value="active" <?php selected( $type, 'active' ); ?> ><?php _e( 'Active', 'bp-members-with-uploaded-avatar-widget' ); ?></option>
					<option value="newest" <?php selected( $type, 'newest' ); ?> ><?php _e( 'Recently Joined', 'bp-members-with-uploaded-avatar-widget' ); ?></option>
					<option value="popular" <?php selected( $type, 'popular' ); ?> ><?php _e( 'Popularity', 'bp-members-with-uploaded-avatar-widget' ); ?></option>
				</select>
			</label>
		</p>
		<p>
			<label>
				<input type='checkbox' name="<?php echo $this->get_field_name( 'avatar_option' ); ?>"
				       id="<?php echo $this->get_field_id( 'avatar_option' ); ?>"
				       value="1" <?php echo checked( 1, $avatar_option ); ?> />
				<?php _e( 'Show Members without avatars too?', 'bp-members-with-uploaded-avatar-widget' ); ?>
			</label>
		</p>
		<p>
			<label for="bp-member-with-avatar-size"><?php _e( 'Avatar Size', 'bp-members-with-uploaded-avatar-widget' ); ?>
				<select class="widefat" id="<?php echo $this->get_field_id( 'size' ); ?>"
				        name="<?php echo $this->get_field_name( 'size' ); ?>" style="width: 30%">
					<option value="full" <?php selected( $size, 'full' ); ?> ><?php _e( 'Full', 'bp-members-with-uploaded-avatar-widget' );?></option>
					<option value="thumb" <?php selected( $size, 'thumb' ); ?> ><?php _e( 'Thumb', 'bp-members-with-uploaded-avatar-widget' );?></option>
				</select>
			</label>
		</p>
		<p>
			<label for="bp-member-with-avatar-height"><?php _e( 'Avatar height', 'bp-members-with-uploaded-avatar-widget' ); ?>
				<input class="widefat" id="<?php echo $this->get_field_id( 'height' ); ?>"
				       name="<?php echo $this->get_field_name( 'height' ); ?>" type="text"
				       value="<?php echo esc_attr( $height ); ?>" style="width: 30%"/>
			</label>
		</p>
		<p>
			<label for="bp-member-with-avatar-width"><?php _e( 'Avatar Widtht', 'bp-members-with-uploaded-avatar-widget' ); ?>
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
function bp_register_member_with_avatar_widget() {
	register_widget( 'BP_Members_With_Uploaded_Avatar_Widget' );
}

add_action( 'bp_widgets_init', 'bp_register_member_with_avatar_widget' );
