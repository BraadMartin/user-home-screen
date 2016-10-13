<?php
/**
 * User Home Screen plugin main class.
 *
 * @package User Home Screen
 */

class User_Home_Screen {

	/**
	 * The meta key we store all user widget data under.
	 */
	public static $user_widgets_meta_key = '_uhs_user_widgets';

	/**
	 * The constructor.
	 */
	public function __construct() {
		// Silence is golden.
	}

	/**
	 * Set up plugin hooks.
	 */
	public function init() {

		// Enqueue our JS and CSS.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );

		// Register our admin page.
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );

		// Ajax handler for adding a widget.
		add_action( 'wp_ajax_uhs_add_widget', array( $this, 'ajax_add_widget' ) );
	}

	/**
	 * Enqueue our JS and CSS.
	 *
	 * @param  string  $hook  The current page hook.
	 */
	public function enqueue_scripts_and_styles( $hook ) {

		// Bail if we're not on our admin page.
		if ( 'toplevel_page_user-home-screen' !== $hook ) {
			return;
		}

		wp_enqueue_script(
			'user-home-screen',
			USER_HOME_SCREEN_URL . 'js/user-home-screen.js',
			array(
				'featherlight',
				'jquery',
				'wp-util',
				'underscore',
				'select2'
			),
			USER_HOME_SCREEN_VERSION,
			true
		);

		wp_enqueue_style(
			'user-home-screen-css',
			USER_HOME_SCREEN_URL . 'css/user-home-screen.css',
			array(
				'featherlight',
				'select2'
			),
			USER_HOME_SCREEN_VERSION
		);

		$uhs_data = $this->get_js_data();

		wp_localize_script(
			'user-home-screen',
			'uhsData',
			$uhs_data
		);
	}

	/**
	 * Build and return the array of data we'll pass to our JS.
	 *
	 * @return  array  The array of JS data.
	 */
	public function get_js_data() {

		$data = array();

		// Define labels.
		$data['labels'] = array(
			'add_widget'         => __( 'Add Widget', 'user-home-screen' ),
			'edit_widget'        => __( 'Edit Widget', 'user-home-screen' ),
			'select_widget_type' => __( 'Select widget type', 'user-home-screen' ),
			'select_default'     => __( 'Select', 'user-home-screen' ),
		);

		// Add widget type data.
		$data['widget_types'] = $this->get_widget_type_data();

		// Add a nonce.
		$data['nonce'] = wp_create_nonce( 'user-home-screen' );

		/**
		 * Allow the JS data to be customized.
		 *
		 * @param  array  $data  The default JS data.
		 */
		return apply_filters( 'user_home_screen_js_data', $data );
	}

	/**
	 * Return the array of widget type data.
	 *
	 * @return  array  The array of widget type data.
	 */
	public function get_widget_type_data() {

		$post_types    = $this->get_post_types();
		$categories    = $this->get_categories();
		$post_statuses = $this->get_post_statuses();
		$authors       = $this->get_authors();

		$widget_types = array(
			'post-list' => array(
				'label'  => __( 'Post List', 'user-home-screen' ),
				'fields' => array(
					array(
						'key'   => 'title',
						'label' => __( 'Widget Title', 'user-home-screen' ),
						'type'  => 'text',
					),
					array(
						'key'         => 'post_types',
						'label'       => __( 'Post Types', 'user-home-screen' ),
						'type'        => 'select-multiple',
						'placeholder' => __( 'Select a Post Type', 'user-home-screen' ),
						'values'      => $post_types,
					),
					array(
						'key'         => 'categories',
						'label'       => __( 'Categories', 'user-home-screen' ),
						'type'        => 'select-multiple',
						'placeholder' => __( 'Select a Category', 'user-home-screen' ),
						'values'      => $categories,
					),
					array(
						'key'         => 'post_statuses',
						'label'       => __( 'Post Statuses', 'user-home-screen' ),
						'type'        => 'select-multiple',
						'placeholder' => __( 'Select a Post Status', 'user-home-screen' ),
						'values'      => $post_statuses,
					),
					array(
						'key'         => 'authors',
						'label'       => __( 'Authors', 'user-home-screen' ),
						'type'        => 'select-multiple',
						'placeholder' => __( 'Select an Author', 'user-home-screen' ),
						'values'      => $authors,
					),
				),
			),
			'rss-feed' => array(
				'label' => __( 'RSS Feed', 'user-home-screen' ),
				'fields' => array(
					array(
						'key'   => 'title',
						'label' => __( 'Widget Title', 'user-home-screen' ),
						'type'  => 'text',
					),
					array(
						'key'   => 'feed_url',
						'label' => __( 'Feed URL', 'user-home-screen' ),
						'type'  => 'text',
					),
				),
			),
		);

		/**
		 * Allow the widget types data to be customized.
		 *
		 * @param  array  $widget_types  The default array of widget types data.
		 */
		return apply_filters( 'user_home_screen_widget_types', $widget_types );
	}

	/**
	 * Return an array of post types that should be selectable in widgets.
	 *
	 * @return  array  The array of post types.
	 */
	public function get_post_types() {

		$full_post_types = get_post_types( array( 'public' => true ), 'objects' );
		$post_types      = array( 'any' => __( 'Any', 'user-home-screen' ) );

		// Transform into a simple array of post_type => Display Name.
		foreach ( $full_post_types as $post_type => $config ) {
			$post_types[ $post_type ] = $config->labels->name;
		}

		/**
		 * Allow the selectable post types to be customized.
		 *
		 * @param  array  $post_types  The default array of selectable post types.
		 */
		return apply_filters( 'user_home_screen_selectable_post_types', $post_types );
	}

	/**
	 * Return an array of post statuses that should be selectable in widgets.
	 *
	 * @return  array  The array of post statuses.
	 */
	public function get_post_statuses() {

		$full_post_statuses = get_post_stati( array( 'show_in_admin_status_list' => 1 ), 'objects' );
		$post_statuses      = array();

		// Transform into a simple array of post_status => Display name.
		foreach ( $full_post_statuses as $post_status => $config ) {
			$post_statuses[ $post_status ] = $config->label;
		}

		/**
		 * Allow the selectable post statuses to be filtered.
		 *
		 * @param  array  $post_statuses  The default array of selectable post statuses.
		 */
		return apply_filters( 'user_home_screen_selectable_post_statuses', $post_statuses );
	}

	/**
	 * Return an array of authors that should be selectable in widgets.
	 *
	 * @return  array  The array of authors.
	 */
	public function get_authors() {

		$full_users = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) );
		$authors    = array();

		// Transform into a simple array of user ID => Display name.
		// Here we have to store user_logins instead of the ID because if we key
		// this array by IDs it won't be ordered by display_name.
		foreach ( $full_users as $user ) {
			$authors[ 'user_' . $user->ID ] = $user->data->display_name;
		}

		/**
		 * Allow the selectable authors to be filtered.
		 *
		 * @param  array  $authors  The default array of selectable authors.
		 */
		return apply_filters( 'user_home_screen_selectable_authors', $authors );
	}

	/**
	 * Return an array of categories that should be selectable in widgets.
	 *
	 * @return  array  The array of categories.
	 */
	public function get_categories() {

		$full_categories = get_terms( array( 'taxonomy' => 'category' ) );
		$categories      = array();

		// Transform into a simple array of user ID => Display name.
		foreach ( $full_categories as $category ) {
			$categories[ 'term_' . $category->term_id ] = $category->name;
		}

		/**
		 * Allow the selectable authors to be filtered.
		 *
		 * @param  array  $authors  The default array of selectable categories.
		 */
		return apply_filters( 'user_home_screen_selectable_categories', $categories );
	}

	/**
	 * Register our admin page.
	 */
	public function register_admin_page() {

		$menu_title = __( 'User Home', 'user-home-screen' );
		/**
		 * Allow the menu title to be customized.
		 *
		 * @param  string   $menu_title  The default menu title.
		 */
		$menu_title = apply_filters( 'user_home_screen_menu_title', $menu_title );

		$menu_icon = 'dashicons-admin-home';
		/**
		 * Allow the menu icon to be customized.
		 *
		 * @param  string  $menu_icon  The default menu icon.
		 */
		$menu_icon = apply_filters( 'user_home_screen_menu_icon', $menu_icon );

		// Register our admin page.
		add_menu_page(
			esc_html__( 'User Home Screen', 'user-home-screen' ),
			$menu_title,
			'read',
			'user-home-screen',
			array( $this, 'output_user_home_screen' ),
			$menu_icon,
			72 // Right after Users
		);
	}

	/**
	 * Return the user widgets config for the passed in user.
	 *
	 * @param  WP_User  $user  The current user object.
	 */
	public function get_user_widgets( $user ) {

		$user_widgets = get_user_meta( $user->ID, self::$user_widgets_meta_key, true );

		/**
		 * Allow the user widgets config to be customized.
		 *
		 * @param  array    $user_widgets  The user widgets config.
		 * @param  WP_User  $user          The current user object.
		 */
		return apply_filters( 'user_home_screen_user_widgets', $user_widgets, $user );
	}

	/**
	 * Output the user home screen.
	 */
	public function output_user_home_screen() {

		// Set up the user data.
		$user         = wp_get_current_user();
		$user_name    = ( ! empty( $user->data->display_name ) ) ? $user->data->display_name : '';
		$user_widgets = $this->get_user_widgets( $user );

		$page_title = __( 'Welcome', 'user-home-screen' ) . ' ' . $user_name;
		/**
		 * Allow the page title to be customized.
		 *
		 * @param  string   $page_title  The default page title.
		 * @param  WP_User  $user        The current user object.
		 */
		$page_title = apply_filters( 'user_home_screen_page_title', $page_title, $user );

		$add_widget_text = $this->get_js_data()['labels']['add_widget'];

		ob_start();

		?>
		<div id="uhs-wrap" class="wrap" data-active-tab="main">
			<div class="uhs-inner-wrap">
				<h1><?php echo esc_html( $page_title ); ?></h1>
				<a class="button button-primary uhs-add-widget"><?php esc_html_e( $add_widget_text ); ?></a>
				<h2 class="nav-tab-wrapper">
					<a class="nav-tab nav-tab-active" data-tab-id="main">
						<?php esc_html_e( 'Main', 'user-home-screen' ); ?>
					</a>
					<a class="nav-tab" data-tab-id="setup">
						<?php esc_html_e( 'Setup', 'user-home-screen' ); ?>
					</a>
				</h2>
				<?php echo $this->output_main_tab( $user, $user_widgets ); ?>
				<?php echo $this->output_setup_tab( $user, $user_widgets ); ?>
			</div>
			<?php echo $this->output_widget_edit_templates(); ?>
		</div>
		<?php

		$screen_html = ob_get_clean();
		/**
		 * Allow the HTML for the User Home Screen to be customized.
		 *
		 * @param  string  $screen_html  The HTML for the User Home Screen.
		 */
		echo apply_filters( 'user_home_screen_html', $screen_html );
	}

	/**
	 * Output the "Main" tab contents.
	 *
	 * @param   WP_User  $user          The current user object.
	 * @param   array    $user_widgets  The current user's widgets.
	 *
	 * @return  string                  The "Main" tab HTML.
	 */
	public function output_main_tab( $user, $user_widgets ) {

		ob_start();

		// If the user doesn't have widgets set up, output an initial setup prompt.
		if ( empty( $user_widgets ) ) {
			echo 'YOLO No Widgets';
		}
		?>
		<div class="uhs-widget-grid">
			<?php
				// If the user has widgets, output them one by one.
				if ( ! empty( $user_widgets ) ) {
					foreach ( $user_widgets as $widget ) {
						echo User_Home_Screen::render_widget( $widget );
					}
				}
			?>
		</div>
		<?php

		$tab_html = ob_get_clean();
		/**
		 * Allow the HTML for the "Main" tab to be customized.
		 *
		 * @param  string  $tab_html  The HTML for the "Main" tab.
		 */
		$tab_html = apply_filters( 'user_home_screen_main_tab', $tab_html );

		// Wrap the HTML in a standard wrapper.
		$tab_html = sprintf(
			'<div class="%s">%s</div>',
			'uhs-main-tab',
			$tab_html
		);

		return $tab_html;
	}

	/**
	 * Build and return the HTML for a widget.
	 *
	 * @param   array  $widget  The widget instance data.
	 *
	 * @return  string          The widget HTML.
	 */
	public static function render_widget( $widget ) {

		$html = '';
		/**
		 * Allow outside code to short-circuit this whole function
		 * and render a custom widget.
		 *
		 * @param  string  $html    The empty string of HTML.
		 * @param  array   $widget  The widget instance data.
		 */
		if ( ! empty( apply_filters( 'user_home_screen_pre_render_widget', $html, $widget ) ) ) {
			return $html;
		}

		switch ( $widget['type'] ) {
			case 'post-list':
				$html = User_Home_Screen::render_post_list_widget( $widget['args'] );
				break;
		}

		/**
		 * Allow the widget HTML to be customized.
		 *
		 * @param  string  $html    The default widget html.
		 * @param  array   $widget  The widget instance data.
		 */
		return apply_filters( 'user_home_screen_widget_html', $html, $widget );
	}

	/**
	 * Render a post-list widget.
	 *
	 * @param   array  $args  The widget args.
	 *
	 * @return  string        The widget HTML.
	 */
	public static function render_post_list_widget( $args ) {

		$html = '';

		// Bail if we don't have query args.
		if ( empty( $args['query_args'] ) ) {
			return $html;
		}

		$parts = ( ! empty( $args['parts'] ) ) ? $args['parts'] : array();

		// Make the query.
		$query = new WP_Query( $args['query_args'] );

		ob_start();

		?>
		<div class="uhs-widget-top-bar">
			<button type="button" class="handlediv button-link"><span class="toggle-indicator" aria-hidden="true"></span></button>
			<h2 class="uhs-widget-title hndle ui-sortable-handle">
				<span><?php echo esc_html( $args['title'] ); ?></span>
			</h2>
		</div>
		<?php

		if ( $query->have_posts() ) {

			while ( $query->have_posts() ) {

				$query->the_post();

				$post_type   = get_post_type_object( $query->post->post_type );
				$post_status = get_post_status_object( $query->post->post_status );

				?>
				<div class="uhs-post-list-widget-post">
					<div class="uhs-post-list-widget-left">
						<h3 class="uhs-post-list-widget-post-title">
							<a href="<?php echo esc_url( get_edit_post_link( get_the_ID(), false ) ); ?>">
								<?php echo esc_html( the_title() ); ?>
							</a>
						</h3>
						<?php if ( in_array( 'author', $parts ) ) : ?>
						<div class="uhs-post-list-widget-post-author">
							<?php echo esc_html__( 'By', 'user-home-screen' ) . ': ' . get_the_author(); ?>
						</div>
						<?php endif; ?>
					</div>
					<div class="uhs-post-list-widget-right">
						<?php if ( in_array( 'post_type', $parts ) ) : ?>
						<div class="uhs-post-list-widget-post-type">
							<?php echo esc_html( $post_type->labels->singular_name ); ?>
						</div>
						<?php endif; ?>
						<?php if ( in_array( 'status', $parts ) ) : ?>
						<div class="uhs-post-list-widget-post-status">
							<?php echo esc_html( $post_status->label ); ?>
						</div>
						<?php endif; ?>
						<?php if ( in_array( 'publish_date', $parts ) ) : ?>
						<div class="uhs-post-list-widget-post-date">
							<?php the_date(); ?>
						</div>
						<?php endif; ?>
					</div>
					<div class="uhs-post-list-widget-bottom">
						<?php /* Hiding this for now.
						<div class="uhs-post-list-widget-action-links">
							<a href="<?php esc_url( the_permalink() ); ?>" target="_blank">
								<?php esc_html_e( 'View', 'user-home-screen' ); ?>
							</a>
						</div>*/ ?>
						<?php if ( in_array( 'category', $parts ) ) : ?>
						<div class="uhs-post-list-widget-categories">
							<?php echo get_the_category_list(); ?>
						</div>
						<?php endif; ?>
					</div>
				</div>
				<?php
			}

			wp_reset_postdata();

		} else {

			?>
			<h3><?php esc_html_e( 'No Posts Found...', 'user-home-screen' ); ?></h3>
			<?php
		}

		$html = ob_get_clean();

		// Wrap the HTML in a standard wrapper.
		$html = sprintf(
			'<div class="%s">%s</div>',
			'uhs-widget type-post-list postbox',
			$html
		);

		return $html;
	}

	/**
	 * Output the "Setup" tab contents.
	 *
	 * @param   WP_User  $user          The current user object.
	 * @param   array    $user_widgets  The current user's widgets.
	 *
	 * @return  string                  The "Setup" tab HTML.
	 */
	public function output_setup_tab( $user, $user_widgets ) {

		ob_start();

		?>
		<div class="uhs-setup-form">
		</div>
		<?php

		$tab_html = ob_get_clean();
		/**
		 * Allow the HTML for the "Setup" tab to be customized.
		 *
		 * @param  string  $tab_html  The HTML for the "Setup" tab.
		 */
		$tab_html = apply_filters( 'user_home_screen_setup_tab', $tab_html );

		// Wrap the HTML in a standard wrapper.
		$tab_html = sprintf(
			'<div class="%s">%s</div>',
			'uhs-setup-tab',
			$tab_html
		);

		return $tab_html;
	}

	/**
	 * Output our widget edit templates.
	 *
	 * @return  string  The widget templates HTML.
	 */
	public function output_widget_edit_templates() {

		// Templates.
		$templates = array(
			USER_HOME_SCREEN_PATH . 'templates/widget-edit.php',
			USER_HOME_SCREEN_PATH . 'templates/field-text.php',
			USER_HOME_SCREEN_PATH . 'templates/field-select.php',
			USER_HOME_SCREEN_PATH . 'templates/field-select-multiple.php',
		);

		// Loop over each template and include it.
		foreach ( $templates as $template ) {

			/**
			 * Allow the template paths to be filtered.
			 *
			 * This filter makes it possible for outside code to swap our templates
			 * for custom templates, and as long as the template ID and data object
			 * keys are kept the same everything should still work.
			 *
			 * @param  string   $template  The template path.
			 */
			include_once apply_filters( 'user_home_screen_js_templates', $template);
		}
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

		$widget_data = array(
			'type' => $clean_type,
			'args' => $clean_args,
		);

		// Validate the widget data.
		$widget_data = $this->validate_widget_data( $widget_data );

		// Add the widget for the user.
		$this->add_widget_for_user( $widget_data, $user );

		$response          = new stdClass();
		$response->message = esc_html__( 'It appears to have worked', 'user-home-screen' );

		wp_send_json( $response );

		wp_die();
	}

	/**
	 * Add a new widget to a user's home screen.
	 *
	 * @param  array    $widget_data  The array of widget data.
	 * @param  WP_User  $user         The user object to update.
	 */
	public function add_widget_for_user( $widget_data, $user ) {

		// Get existing widget data for the user.
		$existing_data = get_user_meta( $user->ID, self::$user_widgets_meta_key, true );

		error_log( 'existing data' );
		error_log( print_r( $existing_data, true ) );

		if ( empty( $existing_data ) || ! is_array( $existing_data ) ) {
			$existing_data = array();
		}

		/**
		 * Allow the widget data to be customized as it's being added.
		 *
		 * @param  array    $widget_data  The array of widget data.
		 * @param  WP_User  $user         The user object being updated.
		 */
		$existing_data[] = apply_filters( 'user_home_screen_add_widget_data', $widget_data, $user );

		$updated_data = $existing_data;

		$this->update_widgets_for_user( $updated_data, $user );
	}

	/**
	 * Remove a widget from a user's home screen.
	 *
	 * @param  int      $widget_index  The index for the widget to remove.
	 * @param  WP_User  $user          The user object to update.
	 */
	public function remove_widget_for_user( $widget_index, $user ) {

		$existing_data = get_user_meta( $user->ID, self::$user_widgets_meta_key, true );

		if ( empty( $existing_data ) || ! is_array( $existing_data ) ) {
			$existing_data = array();
		}

		if ( isset( $existing_data[ $widget_index ] ) ) {
			unset( $existing_data[ $widget_index ] );
		}

		$updated_data = $existing_data;

		$this->update_widgets_for_user( $updated_data, $user );
	}

	/**
	 * Update widgets for a user.
	 *
	 * @param  array    $widgets_data  The array of widgets data.
	 * @param  WP_User  $user          The user object to update.
	 */
	public function update_widgets_for_user( $widgets_data, $user ) {

		error_log( 'about to save user meta' );
		error_log( print_r( $widgets_data, true ) );

		/**
		 * Allow the widget data to be customized as it's being saved.
		 *
		 * @param  array    $widget_data  The array of widget data.
		 * @param  WP_User  $user         The user object being updated.
		 */
		$widgets_data = apply_filters( 'user_home_screen_update_widgets_data', $widgets_data, $user );

		// Ensure the array is sorted properly.
		$widgets_data = array_values( $widgets_data );

		update_user_meta( $user->ID, self::$user_widgets_meta_key, $widgets_data );
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

		return $widget_data;
	}

	/**
	 * Validate args for the Post List widget.
	 *
	 * @param   array  $args  The unvalidated widget args.
	 *
	 * @return  array         The validated widget args.
	 */
	public function validate_post_list_widget_args( $args ) {

		error_log( 'validating' );
		error_log( print_r( $args, true ) );

		// Defaults.
		$updated_args               = array();
		$updated_args['query_args'] = array();
		$updated_args['parts']      = array();

		// Title.
		$updated_args['title'] = ( ! empty( $args['title'] ) ) ? esc_html( $args['title'] ) : '';

		// Post Types.
		if ( ! empty( $args['post_types'] ) ) {
			if ( in_array( 'any', $args['post_types'] ) ) {
				$updated_args['query_args']['post_type'] = 'any';
			} else {
				$updated_args['query_args']['post_type'] = $args['post_types'];
			}
		}

		// Categories.
		if ( ! empty( $args['categories'] ) ) {
			$term_ids = array();
			if ( is_array( $args['categories'] ) ) {
				foreach ( $args['categories'] as $term_key ) {
					$term_id    = str_replace( 'term_', '', $term_key );
					$term_ids[] = (int)$term_id;
				}
			} else {
				$term_id    = str_replace( 'term_', '', $args['categories'] );
				$term_ids[] = (int)$term_id;
			}
			$updated_args['query_args']['category__in'] = $term_ids;
		}

		// Post Statuses.
		if ( ! empty( $args['post_statuses'] ) ) {
			$updated_args['query_args']['post_status'] = $args['post_statuses'];
		}

		// Authors.
		if ( ! empty( $args['authors'] ) ) {
			$author_ids = array();
			if ( is_array( $args['authors'] ) ) {
				foreach ( $args['authors'] as $user_key ) {
					$user_id      = str_replace( 'user_', '', $user_key );
					$author_ids[] = (int)$user_id;
				}
			} else {
				$user_id      = str_replace( 'user_', '', $args['authors'] );
				$author_ids[] = (int)$user_id;
			}
			$updated_args['query_args']['author__in'] = $author_ids;
		}

		// Parts.
		if ( ! empty( $args['parts'] ) ) {
			$updated_args['parts'] = $args['parts'];
		} else {
			$updated_args['parts'] = array(
				'post_type',
				'category',
				'publish_date',
				'status',
				'author',
			);
		}

		return $updated_args;
	}

	/**
	 * Validate args for the RSS Feed widget.
	 *
	 * @param   array  $args  The unvalidated widget args.
	 *
	 * @return  array         The validated widget args.
	 */
	public function validate_rss_feed_widget_args( $args ) {

		$updated_args = $args;

		return $updated_args;
	}

}
