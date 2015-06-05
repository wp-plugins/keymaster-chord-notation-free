<?php
class KCN_Free_Widget extends WP_Widget {

	/**
	 * Register widget with WordPress.
	 */
	function __construct() {
		parent::__construct(
			'kcn_free_widget', // Base ID
			__( 'Keymaster Chord Notation (Free) Controls'), // Name
			array( 'description' => __( 'Display the chord notation controls'), ) // Args
		);
	}

	/**
	 * Front-end display of widget.
	 *
	 * @see WP_Widget::widget()
	 *
	 * @param array $args     Widget arguments.
	 * @param array $instance Saved values from database.
	 */
	public function widget( $args, $instance ) {
		echo $args['before_widget'];
		if ( ! empty( $instance['title'] ) ) {
			echo $args['before_title'] . apply_filters( 'widget_title', $instance['title'] ). $args['after_title'];
		}
		$layout = !empty( $instance['vertical'] ) ? 'layout="vertical"' :'';
		$size = !empty( $instance['size'] ) ? 'size="'.$instance['size'].'"' :'';
		$hides = !empty( $instance['hide'] ) ? 'hide="'.implode(',',array_keys($instance['hide'])).'"' :'';
		$shortcode = "[kcn_buttons $layout $size $hides]";
		echo do_shortcode($shortcode);
		echo $args['after_widget'];
	}

	/**
	 * Back-end widget form.
	 *
	 * @see WP_Widget::form()
	 *
	 * @param array $instance Previously saved values from database.
	 */
	public function form( $instance ) {
		$title = ! empty( $instance['title'] ) ? $instance['title'] : __( '', 'text_domain' );
		$checkedV = !empty( $instance['vertical'] ) ? 'checked="checked"' :'';
		$current = !empty( $instance['size'] ) ? $instance['size'] :'';
		$hides = !empty( $instance['hide'] ) ? (array)$instance['hide'] :array();
		?>
		<p>
		<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:' ); ?></label> 
		<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo esc_attr( $title ); ?>">
		<label><?php _e('Vertical alignment') ?> <input id="<?php echo $this->get_field_id( 'vertical' ); ?>" name="<?php echo $this->get_field_name( 'vertical' ); ?>" type="checkbox" name="KCNvertical" <?php echo $checkedV ?> /></label><br />
		<h4><?php _e('Size') ?></h4>
		<input id="button_size-xs" name="<?php echo $this->get_field_name( 'size' ); ?>" value="xs" type="radio" <?php checked('xs', $current); ?>>
		<label for="button_size-xs"><?php _e('Extra small') ?></label><br><input id="button_size-sm" name="<?php echo $this->get_field_name( 'size' ); ?>" value="sm" type="radio" <?php checked('sm', $current); ?>>
		<label for="button_size-sm"><?php _e('Small') ?></label><br><input id="button_size-md" name="<?php echo $this->get_field_name( 'size' ); ?>" value="md" type="radio" <?php checked('md', $current); ?>>
		<label for="button_size-md"><?php _e('Medium') ?></label><br><input id="button_size-lg" name="<?php echo $this->get_field_name( 'size' ); ?>" value="lg" type="radio" <?php checked('lg', $current); ?>>
		<label for="button_size-lg"><?php _e('Large') ?></label>
		<h4><?php _e('Hide these') ?>:</h4>
		<input id="hide_buttons-show_hide" name="<?php echo $this->get_field_name( 'hide' ); ?>[show_hide]" type="checkbox" <?php checked(array_key_exists('show_hide', $hides)) ?>>
		<label for="hide_buttons-show_hide"><?php _e('Show/Hide Chords') ?></label><br><input id="hide_buttons-transpose_down" name="<?php echo $this->get_field_name( 'hide' ); ?>[transpose_down]" type="checkbox" <?php checked(array_key_exists('transpose_down', $hides)) ?>>
		<label for="hide_buttons-transpose_down">-</label><br><input id="hide_buttons-transpose_fs" name="<?php echo $this->get_field_name( 'hide' ); ?>[transpose_fs]" type="checkbox" <?php checked(array_key_exists('transpose_fs', $hides)) ?>>
		<label for="hide_buttons-transpose_fs"><?php _e('Transpose ♯↔♭') ?></label><br><input id="hide_buttons-transpose_up" name="<?php echo $this->get_field_name( 'hide' ); ?>[transpose_up]" type="checkbox" <?php checked(array_key_exists('transpose_up', $hides)) ?>>
		<label for="hide_buttons-transpose_up">+</label><br><input id="hide_buttons-print" name="<?php echo $this->get_field_name( 'hide' ); ?>[print]" type="checkbox" <?php checked(array_key_exists('print', $hides)) ?>>
		<label for="hide_buttons-print"><?php _e('Print') ?></label>
		</p>
		<?php 
	}

	/**
	 * Sanitize widget form values as they are saved.
	 *
	 * @see WP_Widget::update()
	 *
	 * @param array $new_instance Values just sent to be saved.
	 * @param array $old_instance Previously saved values from database.
	 *
	 * @return array Updated safe values to be saved.
	 */
	public function update( $new_instance, $old_instance ) {
		$instance = array();
		$instance['title'] = ( ! empty( $new_instance['title'] ) ) ? strip_tags( $new_instance['title'] ) : '';
		$instance['vertical'] = ( ! empty( $new_instance['vertical'] ) ) ?  $new_instance['vertical'] : '';
		$instance['size'] = ( ! empty( $new_instance['size'] ) ) ?  $new_instance['size'] : 'md';
		$instance['hide'] = ( ! empty( $new_instance['hide'] ) ) ?  $new_instance['hide'] : array();
		return $instance;
	}

}
function register_KCN_Free_Widget() {
    register_widget( 'KCN_Free_Widget' );
}
add_action( 'widgets_init', 'register_KCN_Free_Widget' );