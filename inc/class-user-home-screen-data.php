<?php
/**
 * User Home Screen Data class.
 *
 * This class handles all CRUD operations on the data used in User Home Screen.
 *
 * @package User Home Screen
 */

class User_Home_Screen_Data {

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

		// Save our options from the user profile screen.
		add_action( 'personal_options_update', array( $this, 'save_user_profile_options' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_profile_options' ) );

	}

	/**
	 * Save our options from the user profile screen.
	 *
	 * @param  int  $user_id  The current user's ID.
	 */
	public function save_user_profile_options( $user_id ) {

		$nonce_valid = ( ! empty( $_POST['uhs-user-profile-nonce'] ) ) ? wp_verify_nonce( $_POST['uhs-user-profile-nonce'], 'uhs_user_profile' ) : false;

		if ( $nonce_valid ) {

			$existing_options = get_user_meta( $user_id, User_Home_Screen::$user_options_meta_key, true );
			$updated_options  = array();

			// Handle existing options being empty.
			if ( empty( $existing_options ) ) {
				$existing_options = array();
			}

			if ( ! empty( $_POST['uhs-redirect-dashboard'] ) ) {
				$updated_options['redirect_dashboard'] = 1;
			} else {
				$updated_options['redirect_dashboard'] = 0;
			}

			$updated_options = array_merge( $existing_options, $updated_options );

			update_user_meta( $user_id, User_Home_Screen::$user_options_meta_key, $updated_options );
		}
	}

	/**
	 * Return the user tabs config for the passed in user.
	 *
	 * @param  WP_User  $user  The current user object.
	 */
	public static function get_user_tabs( $user ) {

		$user_tabs = get_user_meta( $user->ID, User_Home_Screen::$user_tabs_meta_key, true );

		/**
		 * Allow the user tabs config to be customized.
		 *
		 * @param  array    $user_widgets  The user tabs config.
		 * @param  WP_User  $user          The current user object.
		 */
		return apply_filters( 'user_home_screen_user_tabs', $user_tabs, $user );
	}

	/**
	 * Add a tab to a user's home screen.
	 *
	 * @param  array    $tab_data  The array of tab data.
	 * @param  WP_User  $user      The current user object.
	 */
	public function add_tab_for_user( $tab_data, $user ) {

		// Get existing tab data for the user.
		$tabs_data = get_user_meta( $user->ID, User_Home_Screen::$user_tabs_meta_key, true );

		if ( empty( $tabs_data ) || ! is_array( $tabs_data ) ) {
			$tabs_data = array();
		}

		// Generate a unique key for the tab.
		$tab_key = uniqid( 'uhs_', false );

		/**
		 * Allow the tab data to be customized as it's being added.
		 *
		 * @param  array    $tab_data  The array of tab data.
		 * @param  WP_User  $user      The user object being updated.
		 */
		$tabs_data[ $tab_key ] = apply_filters( 'user_home_screen_add_tab_data', $tab_data, $user );

		$this->update_tabs_for_user( $tabs_data, $user );
	}

	/**
	 * Remove a tab from a user's home screen.
	 *
	 * @param  string   $tab_key         The key for the tab to remove.
	 * @param  WP_User  $user            The user object to update.
	 * @param  bool     $remove_widgets  Whether to also remove widgets for the tab (optional).
	 */
	public function remove_tab_for_user( $tab_key, $user, $remove_widgets = false ) {

		$tabs_data = get_user_meta( $user->ID, User_Home_Screen::$user_tabs_meta_key, true );

		if ( empty( $tabs_data ) || ! is_array( $tabs_data ) ) {
			$tabs_data = array();
		}
		if ( ! empty( $tabs_data[ $tab_key ] ) ) {
			unset( $tabs_data[ $tab_key ] );
		}

		$this->update_tabs_for_user( $tabs_data, $user );

		// Also remove widgets for the tab if set to.
		if ( $remove_widgets ) {

			$widgets_data = get_user_meta( $user->ID, User_Home_Screen::$user_widgets_meta_key, true );

			unset( $widgets_data[ $tab_key ] );

			$this->update_widgets_for_user( $widgets_data, $user );
		}
	}

	/**
	 * Update tabs on a user's home screen.
	 *
	 * @param  array    $tabs_data  The array of tab data.
	 * @param  WP_User  $user       The current user object.
	 */
	public function update_tabs_for_user( $tabs_data, $user ) {

		/**
		 * Allow the tabs data to be customized as it's being saved.
		 *
		 * @param  array    $tabs_data  The array of tabs data.
		 * @param  WP_User  $user       The user object being updated.
		 */
		$tabs_data = apply_filters( 'user_home_screen_update_tabs_data', $tabs_data, $user );

		update_user_meta( $user->ID, User_Home_Screen::$user_tabs_meta_key, $tabs_data );
	}

	/**
	 * Return the user widgets config for the passed in user.
	 *
	 * @param  WP_User  $user  The current user object.
	 */
	public static function get_user_widgets( $user ) {

		$user_widgets = get_user_meta( $user->ID, User_Home_Screen::$user_widgets_meta_key, true );

		/**
		 * Allow the user widgets config to be customized.
		 *
		 * @param  array    $user_widgets  The user widgets config.
		 * @param  WP_User  $user          The current user object.
		 */
		return apply_filters( 'user_home_screen_user_widgets', $user_widgets, $user );
	}

	/**
	 * Add a new widget to a user's home screen.
	 *
	 * @param  array    $widget_data  The array of widget data.
	 * @param  WP_User  $user         The user object to update.
	 */
	public function add_widget_for_user( $widget_data, $user ) {

		// Get existing widget data for the user.
		$widgets_data = get_user_meta( $user->ID, User_Home_Screen::$user_widgets_meta_key, true );

		if ( empty( $widgets_data ) || ! is_array( $widgets_data ) ) {
			$widgets_data = array();
		}

		$widget_id = $widget_data['id'];
		$tab_id    = $widget_data['tab'];

		if ( empty( $widgets_data[ $tab_id ] ) ) {
			$widgets_data[ $tab_id ] = array();
		}

		// Remove the widget ID, since we use this to key the array.
		unset( $widget_data['id'] );

		/**
		 * Allow the widget data to be customized as it's being added.
		 *
		 * @param  array    $widget_data  The array of widget data.
		 * @param  WP_User  $user         The user object being updated.
		 */
		$widgets_data[ $tab_id ][ $widget_id ] = apply_filters( 'user_home_screen_add_widget_data', $widget_data, $user );

		$this->update_widgets_for_user( $widgets_data, $user );
	}

	/**
	 * Remove a widget from a user's home screen.
	 *
	 * @param  string   $widget_id  The ID of the widget to remove.
	 * @param  string   $tab_id     The ID of the tab the widget to remove is on.
	 * @param  WP_User  $user       The user object to update.
	 */
	public function remove_widget_for_user( $widget_id, $tab_id, $user ) {

		$widgets_data = get_user_meta( $user->ID, User_Home_Screen::$user_widgets_meta_key, true );

		if ( empty( $widgets_data ) || ! is_array( $widgets_data ) ) {
			$widgets_data = array();
		}

		if ( ! empty( $widgets_data[ $tab_id ][ $widget_id ] ) ) {
			unset( $widgets_data[ $tab_id ][ $widget_id ] );
		}

		$this->update_widgets_for_user( $widgets_data, $user );
	}

	/**
	 * Update widgets for a user.
	 *
	 * @param  array    $widgets_data  The array of widgets data.
	 * @param  WP_User  $user          The user object to update.
	 */
	public function update_widgets_for_user( $widgets_data, $user ) {

		/**
		 * Allow the widget data to be customized as it's being saved.
		 *
		 * @param  array    $widget_data  The array of widget data.
		 * @param  WP_User  $user         The user object being updated.
		 */
		$widgets_data = apply_filters( 'user_home_screen_update_widgets_data', $widgets_data, $user );

		update_user_meta( $user->ID, User_Home_Screen::$user_widgets_meta_key, $widgets_data );
	}

	/**
	 * Validate widget data.
	 *
	 * @param   array  $widget_data  The raw widget data.
	 *
	 * @return  array                The validated widget data.
	 */
	public function validate_widget_data( $widget_data ) {

		switch ( $widget_data['type'] ) {

			case 'post-list':

				$widget_data['args'] = $this->validate_post_list_widget_args( $widget_data['args'] );

				break;

			case 'rss-feed':

				$widget_data['args'] = $this->validate_rss_feed_widget_args( $widget_data['args'] );

				break;
		}

		/**
		 * Allow outside code to validate custom widget types or modify the core widget types.
		 *
		 * @param  array  $widget_data  The array of widget data.
		 */
		return apply_filters( 'user_home_screen_validate_widget_data', $widget_data );
	}

	/**
	 * Validate args for the Post List widget.
	 *
	 * @param   array  $args  The unvalidated widget args.
	 *
	 * @return  array         The validated widget args.
	 */
	public function validate_post_list_widget_args( $args ) {

		// Defaults.
		$updated_args                   = array();
		$updated_args['widget_info']    = array();
		$updated_args['query_args']     = array();
		$updated_args['template_parts'] = array();

		// Store the array of original args to support editing an existing widget.
		$updated_args['original_args'] = $args;

		// Title.
		$updated_args['title'] = ( ! empty( $args['title'] ) ) ? esc_html( $args['title'] ) : '';

		// Post Types.
		if ( ! empty( $args['post_types'] ) ) {
			if ( in_array( 'any', $args['post_types'] ) ) {
				$updated_args['query_args']['post_type']  = 'any';
				$post_types = esc_html__( 'All', 'user-home-screen' );
			} else {
				$updated_args['query_args']['post_type']  = $args['post_types'];

				$post_types = array();

				// Loop over each post type and get a usable post type name.
				foreach ( $args['post_types'] as $post_type ) {
					$post_type_object = get_post_type_object( $post_type );

					if ( ! empty( $post_type_object->labels->name ) ) {
						$post_types[] = $post_type_object->labels->name;
					}
				}

				$post_types = implode( ', ', $post_types );
			}

			// Add widget info.
			$updated_args['widget_info']['post_types'] = sprintf(
				'<span class="%s">%s:</span> %s',
				'uhs-widget-info-label',
				esc_html__( 'Post Types', 'user-home-screen' ),
				esc_html( $post_types )
			);
		}

		// Categories.
		if ( ! empty( $args['categories'] ) ) {
			$term_ids = array();

			// Parse clean term IDs.
			if ( is_array( $args['categories'] ) ) {
				foreach ( $args['categories'] as $term_key ) {
					$term_id    = str_replace( 'term_', '', $term_key );
					$term_ids[] = (int)$term_id;
				}
			} else {
				$term_id    = str_replace( 'term_', '', $args['categories'] );
				$term_ids[] = (int)$term_id;
			}

			// Set clean query arg.
			$updated_args['query_args']['category__in'] = $term_ids;

			$categories = array();

			// Loop over each term ID and get a usable category name.
			foreach ( $term_ids as $term_id ) {
				$term = get_term_by( 'id', $term_id, 'category' );
				if ( ! empty( $term->name ) ) {
					$categories[] = esc_html( $term->name );
				}
			}
			$categories = implode( ', ', $categories );

			// Add widget info.
			$updated_args['widget_info']['categories'] = sprintf(
				'<span class="%s">%s:</span> %s',
				'uhs-widget-info-label',
				esc_html__( 'Categories', 'user-home-screen' ),
				esc_html( $categories )
			);
		}

		// Post Statuses.
		if ( ! empty( $args['post_statuses'] ) ) {
			$updated_args['query_args']['post_status'] = $args['post_statuses'];

			$post_stati = array();

			// Loop over each post status and get a usable post status label.
			foreach ( $args['post_statuses'] as $post_status ) {
				$post_status_object = get_post_status_object( $post_status );

				if ( ! empty( $post_status_object->label ) ) {
					$post_stati[] = $post_status_object->label;
				}
			}

			$post_stati = implode( ', ', $post_stati );

			// Add widget info.
			$updated_args['widget_info']['post_statuses'] = sprintf(
				'<span class="%s">%s:</span> %s',
				'uhs-widget-info-label',
				esc_html__( 'Post Statuses', 'user-home-screen' ),
				esc_html( $post_stati )
			);
		}

		// Authors.
		if ( ! empty( $args['authors'] ) ) {
			$author_ids = array();

			// Parse clean user IDs.
			if ( is_array( $args['authors'] ) ) {
				foreach ( $args['authors'] as $user_key ) {
					$user_id      = str_replace( 'user_', '', $user_key );
					$author_ids[] = (int)$user_id;
				}
			} else {
				$user_id      = str_replace( 'user_', '', $args['authors'] );
				$author_ids[] = (int)$user_id;
			}

			// Set clean query arg.
			$updated_args['query_args']['author__in'] = $author_ids;

			$authors = array();

			// Loop over each author and get a username.
			foreach ( $author_ids as $author_id ) {
				$user = get_userdata( $author_id );

				if ( ! empty( $user->display_name ) ) {
					$authors[] = $user->display_name;
				}
			}

			$authors = implode( ', ', $authors );

			// Add widget info.
			$updated_args['widget_info']['authors'] = sprintf(
				'<span class="%s">%s:</span> %s',
				'uhs-widget-info-label',
				esc_html__( 'Authors', 'user-home-screen' ),
				esc_html( $authors )
			);
		}

		// Order by.
		if ( ! empty( $args['order_by'] ) ) {
			$updated_args['query_args']['orderby'] = sanitize_text_field( $args['order_by'] );

			$order_by_options = user_home_screen_get_order_by_options();

			if ( in_array( $args['order_by'], array_keys( $order_by_options ) ) ) {
				$updated_args['widget_info']['order_by'] = sprintf(
					'<span class="%s">%s:</span> %s',
					'uhs-widget-info-label',
					esc_html__( 'Order By', 'user-home-screen' ),
					esc_html( $order_by_options[ $args['order_by'] ] )
				);
			}
		}

		// Order.
		if ( ! empty( $args['order'] ) ) {
			$updated_args['query_args']['order'] = sanitize_text_field( $args['order'] );

			$order_options = user_home_screen_get_order_options();

			if ( in_array( $args['order'], array_keys( $order_options ) ) ) {
				$updated_args['widget_info']['order'] = sprintf(
					'<span class="%s">%s:</span> %s',
					'uhs-widget-info-label',
					esc_html__( 'Order', 'user-home-screen' ),
					esc_html( $order_options[ $args['order'] ] )
				);
			}
		}

		// Parts.
		if ( ! empty( $args['template_parts'] ) ) {
			$updated_args['template_parts'] = $args['template_parts'];
		} else {
			$parts = array();
			$query_args = $updated_args['query_args'];

			// Show the post type if no post type, post_type is 'any',
			// or multiple post types.
			if (
				empty( $query_args['post_type'] ) ||
				( ! empty( $query_args['post_type'] ) && 'any' === $query_args['post_type'] ) ||
				! empty( $query_args['post_type'][1] )
			) {
				$parts[] = 'post_type';
			}

			// Show the categories if no category or multiple categories.
			if (
				empty( $query_args['category__in'] ) ||
				! empty( $query_args['category__in'][1] )
			) {
				$parts[] = 'category';
			}

			// Show the publish date if post_status includes publish or schedule.
			if (
				! empty( $query_args['post_status'] ) &&
				is_array( $query_args['post_status'] ) &&
				( in_array( 'publish', $query_args['post_status'] ) || in_array( 'schedule', $query_args['post_status'] ) )
			) {
				$parts[] = 'publish_date';
			}

			// Show the last modified date if the order by is set to last modified date.
			if ( ! empty( $query_args['orderby'] ) && 'modified' === $query_args['orderby'] ) {
				$parts[] = 'modified_date';
			}

			// Show the post status if no post_status or multiple post statuses.
			if (
				empty( $query_args['post_status'] ) ||
				! empty( $query_args['post_status'][1] )
			) {
				$parts[] = 'status';
			}

			// Always show the author.
			$parts[] = 'author';

			$updated_args['template_parts'] = $parts;
		}

		/**
		 * Allow the args to be customized.
		 *
		 * @param  array  $updated_args  The updated args array.
		 * @param  array  $args          The original args array.
		 */
		return apply_filters( 'user_home_screen_post_list_args', $updated_args, $args );
	}

	/**
	 * Validate args for the RSS Feed widget.
	 *
	 * @param   array  $args  The unvalidated widget args.
	 *
	 * @return  array         The validated widget args.
	 */
	public function validate_rss_feed_widget_args( $args ) {

		$updated_args                = array();
		$updated_args['widget_info'] = array();

		// Title.
		$updated_args['title'] = ( ! empty( $args['title'] ) ) ? esc_html( $args['title'] ) : '';

		// Feed URL.
		$updated_args['feed_url'] = ( ! empty( $args['feed_url'] ) ) ? esc_url( $args['feed_url'] ) : '';

		// Widget Info.
		$updated_args['widget_info']['feed_url'] = sprintf(
			'<span class="%s">%s:</span> <a href="%s" target="_blank">%s</a>',
			'uhs-widget-info-label',
			esc_html__( 'Feed URL', 'user-home-screen' ),
			esc_url( $updated_args['feed_url'] ),
			esc_html( $updated_args['feed_url'] )
		);

		/**
		 * Allow the args to be customized.
		 *
		 * @param  array  $updated_args  The updated args array.
		 * @param  array  $args          The original args array.
		 */
		return apply_filters( 'user_home_screen_post_list_args', $updated_args, $args );
	}
}
