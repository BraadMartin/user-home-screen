<?php
/**
 * User Home Screen Ajax class.
 *
 * @package User Home Screen
 */

class User_Home_Screen_Ajax {

	/**
	 * Our main plugin class instance.
	 *
	 * @var  User_Home_Screen.
	 */
	private $main = null;

	/**
	 * The constructor.
	 *
	 * @param  User_Home_Screen  $main_class  The instance of our main plugin class.
	 */
	public function __construct( $main_class ) {
		$this->main = $main_class;
	}

	/**
	 * Set up hooks.
	 */
	public function init() {

		// Ajax handler for adding a tab.
		add_action( 'wp_ajax_uhs_add_tab', array( $this, 'ajax_add_tab' ) );

		// Ajax handler for removing a tab.
		add_action( 'wp_ajax_uhs_remove_tab', array( $this, 'ajax_remove_tab' ) );

		// Ajax handler for adding a widget.
		add_action( 'wp_ajax_uhs_add_widget', array( $this, 'ajax_add_widget' ) );

		// Ajax handler for removing a widget.
		add_action( 'wp_ajax_uhs_remove_widget', array( $this, 'ajax_remove_widget' ) );

		// Ajax handler for updating the widget order.
		add_action( 'wp_ajax_uhs_update_widgets_order', array( $this, 'ajax_update_widgets_order' ) );

		// Ajax handler for getting the HTML for a "page" of posts for the Post List widget.
		add_action( 'wp_ajax_uhs_post_list_get_page', array( $this, 'ajax_post_list_widget_get_posts' ) );

		// Ajax handler for saving "template parts" on a Post List widget.
		add_action( 'wp_ajax_uhs_post_list_save_template_parts', array( $this, 'ajax_post_list_widget_save_template_parts' ) );
	}

	/**
	 * Ajax handler for adding a tab.
	 */
	public function ajax_add_tab() {

		// Bail if our nonce is not valid.
		check_ajax_referer( 'user-home-screen', 'nonce', true );

		$user = wp_get_current_user();

		// Bail if we don't have a user.
		if ( empty( $user ) ) {
			$response          = new stdClass();
			$response->message = esc_html__( 'Sorry, you are missing a user.', 'user-home-screen' );
			wp_send_json( $response );
			wp_die();
		}

		// Extract clean arguments from the form data.
		$args_input = urldecode( $_POST['tab_data'] );
		$raw_args   = array();
		$tab_name   = '';

		parse_str( $args_input, $raw_args );

		if ( ! empty( $raw_args['uhs-tab-name'] ) ) {
			$tab_name = sanitize_text_field( $raw_args['uhs-tab-name'] );
		}

		// Add the tab for the user.
		$this->main->data->add_tab_for_user( $tab_name, $user );

		$response          = new stdClass();
		$response->message = esc_html__( 'It appears to have worked', 'user-home-screen' );

		wp_send_json( $response );

		wp_die();
	}

	/**
	 * Ajax handler for removing a tab.
	 */
	public function ajax_remove_tab() {

		// Bail if our nonce is not valid.
		check_ajax_referer( 'user-home-screen', 'nonce', true );

		$user = wp_get_current_user();

		// Bail if we don't have a user.
		if ( empty( $user ) ) {
			$response          = new stdClass();
			$response->message = esc_html__( 'Sorry, you are missing a user.', 'user-home-screen' );
			wp_send_json( $response );
			wp_die();
		}

		$tab_id = sanitize_text_field( $_POST['tab_id'] );

		$this->main->data->remove_tab_for_user( $tab_id, $user, true );

		$response          = new stdClass();
		$response->message = esc_html__( 'It appears to have worked', 'user-home-screen' );

		wp_send_json( $response );

		wp_die();
	}

	/**
	 * Ajax handler for adding a widget.
	 */
	public function ajax_add_widget() {

		// Bail if our nonce is not valid.
		check_ajax_referer( 'user-home-screen', 'nonce', true );

		$user = wp_get_current_user();

		// Bail if we don't have a user.
		if ( empty( $user ) ) {
			$response          = new stdClass();
			$response->message = esc_html__( 'Sorry, you are missing a user.', 'user-home-screen' );
			wp_send_json( $response );
			wp_die();
		}

		// Bail if we don't have a widget type.
		if ( empty( $_POST['widget_type'] ) || empty( $_POST['widget_data'] ) ) {
			$response          = new stdClass();
			$response->message = esc_html__( 'Sorry, you are missing a widget_type or widget_data.', 'user-home-screen' );
			wp_send_json( $response );
			wp_die();
		}

		// Extract clean arguments from the form data.
		$clean_tab   = sanitize_text_field( $_POST['tab_id'] );
		$clean_type  = sanitize_text_field( $_POST['widget_type'] );
		$args_input  = urldecode( $_POST['widget_data'] );
		$widget_args = array();
		$clean_args  = array();

		parse_str( $args_input, $widget_args );

		foreach ( $widget_args as $name => $value ) {
			if ( is_array( $value ) ) {
				$values = array();
				foreach ( $value as $value_item ) {
					$values[] = sanitize_text_field( $value_item );
				}
				$clean_args[ $name ] = $values;
			} else {
				$clean_args[ $name ] = sanitize_text_field( $value );
			}
		}

		// Generate a unique ID for the widget.
		$widget_id = uniqid( 'uhs_', false );

		$widget_data = array(
			'id'   => $widget_id,
			'tab'  => $clean_tab,
			'type' => $clean_type,
			'args' => $clean_args,
		);

		$widget_data = $this->main->data->validate_widget_data( $widget_data );

		$this->main->data->add_widget_for_user( $widget_data, $user );

		$response          = new stdClass();
		$response->message = esc_html__( 'It appears to have worked', 'user-home-screen' );

		wp_send_json( $response );

		wp_die();
	}

	/**
	 * Ajax handler for removing a widget.
	 */
	public function ajax_remove_widget() {

		// Bail if our nonce is not valid.
		check_ajax_referer( 'user-home-screen', 'nonce', true );

		$user = wp_get_current_user();

		// Bail if we don't have a user.
		if ( empty( $user ) ) {
			$response          = new stdClass();
			$response->message = esc_html__( 'Sorry, you are missing a user.', 'user-home-screen' );
			wp_send_json( $response );
			wp_die();
		}

		$tab_id    = sanitize_text_field( $_POST['tab_id'] );
		$widget_id = sanitize_text_field( $_POST['widget_id'] );

		$this->main->data->remove_widget_for_user( $widget_id, $tab_id, $user );

		$response          = new stdClass();
		$response->message = esc_html__( 'It appears to have worked', 'user-home-screen' );

		wp_send_json( $response );

		wp_die();
	}

	/**
	 * Ajax handler for updating the widgets order.
	 */
	public function ajax_update_widgets_order() {

		// Bail if our nonce is not valid.
		check_ajax_referer( 'user-home-screen', 'nonce', true );

		$user = wp_get_current_user();

		// Bail if we don't have a user.
		if ( empty( $user ) ) {
			$response          = new stdClass();
			$response->message = esc_html__( 'Sorry, you are missing a user.', 'user-home-screen' );
			wp_send_json( $response );
			wp_die();
		}

		$tab_id               = sanitize_text_field( $_POST['tab_id'] );
		$widget_order         = ( is_array( $_POST['widget_order'] ) ) ? $_POST['widget_order'] : array();
		$user_widgets         = get_user_meta( $user->ID, User_Home_Screen::$user_widgets_meta_key, true );
		$user_widgets_for_tab = $user_widgets[ $tab_id ];

		// Clear widgets under the tab key that we're updating the order on.
		unset( $user_widgets[ $tab_id ] );

		// Add the widgets for the tab back in the new order.
		foreach ( $widget_order as $widget_id ) {
			$user_widgets[ $tab_id ][ $widget_id ] = $user_widgets_for_tab[ $widget_id ];
		}

		$this->main->data->update_widgets_for_user( $user_widgets, $user );

		$response          = new stdClass();
		$response->message = esc_html__( 'It appears to have worked', 'user-home-screen' );

		wp_send_json( $response );

		wp_die();
	}

	/**
	 * Ajax handler for returning the HTML for a list of posts.
	 */
	public function ajax_post_list_widget_get_posts() {

		// Bail if our nonce is not valid.
		check_ajax_referer( 'user-home-screen', 'nonce', true );

		$user = wp_get_current_user();

		// Bail if we don't have a user.
		if ( empty( $user ) ) {
			$response          = new stdClass();
			$response->message = esc_html__( 'Sorry, you are missing a user.', 'user-home-screen' );
			wp_send_json( $response );
			wp_die();
		}

		// Bail if we're missing required data.
		if ( empty( $_POST['widget_id'] ) || empty( $_POST['tab_id'] ) || empty( $_POST['page'] ) ) {
			$response          = new stdClass();
			$response->message = esc_html__( 'Sorry, you are missing a widget ID, tab ID, or a page.', 'user-home-screen' );
			wp_send_json( $response );
			wp_die();
		}

		$widget_id          = sanitize_text_field( $_POST['widget_id'] );
		$tab_id             = sanitize_text_field( $_POST['tab_id'] );
		$page               = (int) $_POST['page'];
		$user_widgets       = $this->main->data->get_user_widgets( $user );
		$include_pagination = ( ! empty( $_POST['include_pagination'] ) ) ? true : false;

		// Bail if the widget doesn't exist for the user.
		if ( empty( $user_widgets[ $tab_id ][ $widget_id ] ) ) {
			$response          = new stdClass();
			$response->message = esc_html__( 'Sorry, the requested widget doesn\'t appear to exist.', 'user-home-screen' );
			wp_send_json( $response );
			wp_die();
		}

		$args = $user_widgets[ $tab_id ][ $widget_id ];

		// Force the query_args to get regenerated at render time to resolve
		// any issues with the saved query_args in the DB being out of sync.
		if ( ! empty( $args['args']['original_args'] ) ) {

			// This is a rebuild of the original input args.
			$regen_args_input = [
				'id'   => $widget_id,
				'tab'  => $tab_id,
				'type' => 'post-list',
				'args' => $args['args']['original_args'],
			];
			$regen_args = $this->main->data->validate_widget_data( $regen_args_input );

			// Override the saved query args.
			$args['args']['query_args'] = $regen_args['args']['query_args'];
		}

		// Modify the query args to include the new page.
		$args['args']['query_args']['paged'] = $page;

		$html = user_home_screen_render_post_list_widget( $widget_id, $args['args'], $include_pagination );

		$response             = new stdClass();
		$response->message    = esc_html__( 'It appears to have worked', 'user-home-screen' );
		$response->posts_html = $html;

		wp_send_json( $response );

		wp_die();
	}

	/**
	 * Ajax handler for saving "template parts" on a Post List widget.
	 */
	public function ajax_post_list_widget_save_template_parts() {

		// Bail if our nonce is not valid.
		check_ajax_referer( 'user-home-screen', 'nonce', true );

		$user = wp_get_current_user();

		// Bail if we don't have a user.
		if ( empty( $user ) ) {
			$response          = new stdClass();
			$response->message = esc_html__( 'Sorry, you are missing a user.', 'user-home-screen' );
			wp_send_json( $response );
			wp_die();
		}

		// Bail if we're missing required data.
		if ( empty( $_POST['widget_id'] ) || empty( $_POST['tab_id'] ) || empty( $_POST['template_parts'] ) ) {
			$response          = new stdClass();
			$response->message = esc_html__( 'Sorry, you are missing a widget ID, tab ID, or a template parts array.', 'user-home-screen' );
			wp_send_json( $response );
			wp_die();
		}

		$user_widgets   = $this->main->data->get_user_widgets( $user );
		$widget_id      = sanitize_text_field( $_POST['widget_id'] );
		$tab_id         = sanitize_text_field( $_POST['tab_id'] );
		$template_parts = array_map( 'sanitize_text_field', (array) $_POST['template_parts'] );

		// Bail if the widget doesn't exist for the user.
		if ( empty( $user_widgets[ $tab_id ][ $widget_id ] ) ) {
			$response          = new stdClass();
			$response->message = esc_html__( 'Sorry, the requested widget doesn\'t appear to exist.', 'user-home-screen' );
			wp_send_json( $response );
			wp_die();
		}

		$user_widgets[ $tab_id ][ $widget_id ]['args']['template_parts'] = $template_parts;

		$this->main->data->update_widgets_for_user( $user_widgets, $user );

		$response          = new stdClass();
		$response->message = esc_html__( 'It appears to have worked', 'user-home-screen' );

		wp_send_json( $response );

		wp_die();
	}
}
