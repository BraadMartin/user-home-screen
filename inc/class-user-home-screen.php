<?php
/**
 * User Home Screen main class.
 *
 * @package User Home Screen
 */

class User_Home_Screen {

	/**
	 * The meta key we store all user widget data under.
	 */
	public static $user_widgets_meta_key = '_uhs_user_widgets';

	/**
	 * The meta key we store all user tabs under.
	 */
	public static $user_tabs_meta_key = '_uhs_user_tabs';

	/**
	 * The meta key we store all other user options under.
	 */
	public static $user_options_meta_key = '_uhs_user_options';

	/**
	 * Our Data class instance.
	 *
	 * @var  User_Home_Screen_Data.
	 */
	public $data = null;

	/**
	 * Our Ajax class instance.
	 *
	 * @var  User_Home_Screen_Ajax.
	 */
	public $ajax = null;

	/**
	 * The constructor.
	 */
	public function __construct() {
		// Silence is golden.
	}

	/**
	 * Set up hooks and initialize other plugin classes.
	 */
	public function init() {

		// Enqueue our JS and CSS.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );

		// Register our admin page.
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );

		// Maybe redirect the default WordPress dashboard to User Home Screen.
		add_action( 'wp_dashboard_setup', array( $this, 'maybe_redirect_dashboard' ) );

		// Output our user profile fields.
		add_action( 'personal_options', array( $this, 'output_user_profile_fields' ) );

		// Output extra content in the widget info section of certain widget types.
		add_filter( 'user_home_screen_widget_info', array( $this, 'output_widget_info_extras' ), 10, 3 );

		$this->data = new User_Home_Screen_Data( $this );
		$this->data->init();

		// Initialize Ajax class if we're serving an Ajax request.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$this->ajax = new User_Home_Screen_Ajax( $this );
			$this->ajax->init();
		}
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

		// Check for an already registered version of featherlight,
		// and register ours if none is found.
		if ( ! wp_script_is( 'featherlight', 'registered' ) ) {
			wp_register_script(
				'featherlight',
				USER_HOME_SCREEN_URL . 'vendor/featherlight/featherlight.min.js',
				array( 'jquery' ),
				'1.4.0',
				true
			);
		}
		if ( ! wp_style_is( 'featherlight', 'registered' ) ) {
			wp_register_style(
				'featherlight',
				USER_HOME_SCREEN_URL . 'vendor/featherlight/featherlight.min.css',
				array(),
				'1.4.0'
			);
		}

		// Check for an already registered version of select2,
		// and register ours if none is found.
		if ( ! wp_script_is( 'select2', 'registered' ) ) {
			wp_register_script(
				'select2',
				USER_HOME_SCREEN_URL . 'vendor/select2/select2.min.js',
				array( 'jquery' ),
				'4.0.3',
				true
			);
		}
		if ( ! wp_style_is( 'select2', 'registered' ) ) {
			wp_register_style(
				'select2',
				USER_HOME_SCREEN_URL . 'vendor/select2/select2.min.css',
				array(),
				'4.0.3'
			);
		}

		// Check for an already registered version of Moment JS,
		// and register ours if none is found.
		if ( ! wp_script_is( 'moment', 'registered' ) ) {
			wp_register_script(
				'moment',
				USER_HOME_SCREEN_URL . 'vendor/moment/moment.min.js',
				array(),
				'2.15.1',
				true
			);
		}

		// Check for an already registered version of jquery-rss,
		// and register ours if none is found.
		if ( ! wp_script_is( 'jquery-rss', 'registered' ) ) {
			wp_register_script(
				'jquery-rss',
				USER_HOME_SCREEN_URL . 'vendor/jquery-rss/jquery.rss.min.js',
				array(
					'jquery',
					'moment',
				),
				'3.2.1',
				true
			);
		}

		wp_enqueue_script(
			'user-home-screen',
			USER_HOME_SCREEN_URL . 'js/user-home-screen.js',
			array(
				'featherlight',
				'jquery',
				'jquery-ui-core',
				'jquery-ui-sortable',
				'wp-util',
				'underscore',
				'select2'
			),
			USER_HOME_SCREEN_VERSION,
			true
		);

		wp_enqueue_script(
			'user-home-screen-widgets',
			USER_HOME_SCREEN_URL . 'js/user-home-screen-widgets.js',
			array(
				'jquery',
				'jquery-rss'
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

		$uhs_data = user_home_screen_get_js_data();

		wp_localize_script(
			'user-home-screen',
			'uhsData',
			$uhs_data
		);
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
	 * Maybe redirect the default WordPress dashboard to User Home Screen.
	 */
	public function maybe_redirect_dashboard() {

		$user         = wp_get_current_user();
		$user_options = get_user_meta( $user->ID, self::$user_options_meta_key, true );

		if ( ! empty( $user_options['redirect_dashboard'] ) ) {
			wp_safe_redirect( admin_url( 'admin.php?page=user-home-screen' ) );
		}
	}

	/**
	 * Output our user profile fields.
	 *
	 * @param  WP_User  $user  The current user object.
	 */
	public function output_user_profile_fields( $user ) {

		$user_options = get_user_meta( $user->ID, self::$user_options_meta_key, true );

		// Handle empty user options.
		if ( empty( $user_options ) ) {
			$user_options = array();
		}

		$default_options = array(
			'redirect_dashboard' => false,
		);

		$user_options = array_merge( $default_options, $user_options );

		$redirect_dashboard_label = __( 'Redirect Dashboard?', 'user-home-screen' );
		$redirect_dashboard_desc  = __( 'Redirect the default WordPress dashboard to your User Home Screen', 'user-home-screen' );

		?>
		<tr class="uhs-user-profile-field">
			<th scope="row"><?php esc_html_e( $redirect_dashboard_label ); ?></th>
			<td>
				<fieldset>
					<legend class="screen-reader-text"><span><?php echo esc_html( $redirect_dashboard_label ); ?></span></legend>
					<label for="uhs-redirect-dashboard">
					<input name="uhs-redirect-dashboard" type="checkbox" id="uhs-redirect-dashboard" value="1" <?php checked( $user_options['redirect_dashboard'] ); ?>>
						<?php echo esc_html( $redirect_dashboard_desc ); ?></label><br>
				</fieldset>
			</td>
		</tr>
		<?php

		// Generate and output a nonce.
		wp_nonce_field( 'uhs_user_profile', 'uhs-user-profile-nonce' );
	}

	/**
	 * Output the user home screen.
	 */
	public function output_user_home_screen() {

		// Set up the user data.
		$user         = wp_get_current_user();
		$user_name    = ( ! empty( $user->data->display_name ) ) ? $user->data->display_name : '';
		$user_tabs    = $this->data->get_user_tabs( $user );
		$user_widgets = $this->data->get_user_widgets( $user );

		$page_title = __( 'Welcome', 'user-home-screen' ) . ' ' . $user_name;
		/**
		 * Allow the page title to be customized.
		 *
		 * @param  string   $page_title  The default page title.
		 * @param  WP_User  $user        The current user object.
		 */
		$page_title = apply_filters( 'user_home_screen_page_title', $page_title, $user );

		$add_widget_text = user_home_screen_get_js_data()['labels']['add_widget'];

		ob_start();

		?>
		<div id="uhs-wrap" class="wrap" data-active-tab="main">
			<div class="uhs-inner-wrap">
				<h1><?php echo esc_html( $page_title ); ?></h1>
				<a class="button button-primary uhs-add-widget"><?php esc_html_e( $add_widget_text ); ?></a>
				<span class="uhs-widget-spinner uhs-spinner spinner"></span>
				<span class="uhs-widget-save-confirm"><?php esc_html_e( 'Widgets Saved', 'user-home-screen' ); ?></span>
				<h2 class="nav-tab-wrapper">
					<?php

					// Handle initial empty tabs and ensure our add new tab is always present.
					if ( ! is_array( $user_tabs ) ) {
						$user_nav_tabs = array(
							'add-new' => '+',
						);
					} else {
						$user_nav_tabs = array_merge( $user_tabs, array( 'add-new' => '+' ) );
					}

					// Grab a "tab" query param from the URL if present.
					$active_tab = ( ! empty( $_GET['tab'] ) ) ? $_GET['tab'] : '';

					// If the active tab is the add-new tab, set the active tab to the first tab.
					if ( 'add-new' === $active_tab && ! empty( $user_nav_tabs ) ) {
						$active_tab = reset( $user_nav_tabs );
					}

					foreach ( $user_nav_tabs as $tab_key => $tab_name ) {

						// If an active tab is set in the URL use it, otherwise use the first tab from
						// the user's tabs, otherwise if the user hasn't set up tabs use the default tab.
						if ( ! empty( $active_tab ) && $tab_key === $active_tab ) {
							$active_class = 'nav-tab-active';
						} elseif ( empty( $active_tab ) ) {
							if ( array_key_exists( $tab_key, array_slice( $user_nav_tabs, 0, 1 ) ) ) {
								$active_class = 'nav-tab-active';
							} else {
								$active_class = '';
							}
						} else {
							$active_class = '';
						}

						if ( 'add-new' !== $tab_key ) {
							$remove_button = '<button type="button" class="uhs-remove-tab"><span class="dashicons dashicons-no-alt"></span></button>';
						} else {
							$remove_button = '';
						}

						printf(
							'<a class="nav-tab %s" data-tab-id="%s">%s%s</a>',
							esc_attr( $active_class ),
							esc_attr( $tab_key ),
							esc_html( $tab_name ),
							$remove_button
						);
					}

					// If the user hasn't set up any tabs yet, output a helper message.
					if ( 1 === count( $user_nav_tabs ) && ! empty( $user_nav_tabs['add-new'] ) ) {
						printf(
							'<span class="%s"><span class="%s"></span>%s</span>',
							'uhs-tab-setup-notice',
							'dashicons dashicons-arrow-left-alt',
							esc_html__( 'Click here to set up your first tab', 'user-home-screen' )
						);
					}

					?>
				</h2>
				<?php echo $this->output_tab_widgets( $user, $user_tabs, $user_widgets ); ?>
			</div>
			<?php echo $this->output_js_templates(); ?>
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
	 * Output all of the user's widgets for each of the user's tabs.
	 *
	 * @param   WP_User  $user          The current user object.
	 * @param   array    $user_tabs     The current user's tabs.
	 * @param   array    $user_widgets  The current user's widgets.
	 *
	 * @return  string                  The HTML for each tab's widgets.
	 */
	public function output_tab_widgets( $user, $user_tabs, $user_widgets ) {

		$tabs_html = '';

		// Handle emty user tabs.
		if ( empty( $user_tabs ) ) {
			$user_tabs = array();
		}

		// Handle empty user widgets.
		if ( empty( $user_widgets ) ) {
			$user_widgets = array();
		}

		// Loop over each tab.
		foreach ( $user_tabs as $tab_key => $tab_name ) {

			$widgets_html = '';

			// Output user widgets for the current tab, or a prompt to add widgets.
			if ( ! empty( $user_widgets[ $tab_key ] ) ) {

				$widgets_html .= sprintf(
					'<div class="%s" data-for-tab="%s"><div class="%s">',
					'uhs-tab-content-wrap',
					esc_attr( $tab_key ),
					'uhs-widget-grid'
				);

				foreach ( $user_widgets[ $tab_key ] as $widget_id => $widget_args ) {
					$widgets_html .= self::render_widget( $widget_id, $widget_args, $tab_key );
				}

				$widgets_html .= '</div></div>';

			} else {

				$widgets_html .= sprintf(
					'<div class="%s" data-for-tab="%s">%s</div>',
					'uhs-tab-content-wrap',
					esc_attr( $tab_key ),
					esc_html__( "Looks like you haven't yet added widgets to this tab. Click the \"Add Widget\" button on the top right of your screen to get started.", 'user-home-screen' )
				);
			}

			$tabs_html .= $widgets_html;
		}

		/**
		 * Allow the HTML for each tab's widgets to be customized.
		 *
		 * @param  string  $tab_html      The HTML for each tab's widgets.
		 * @param  array   $user_tabs     The current user's tabs.
		 * @param  array   $user_widgets  The current user's widgets.
		 */
		return apply_filters( 'user_home_screen_tabs_html', $tabs_html, $user_tabs, $user_widgets );
	}

	/**
	 * Build and return the HTML for a widget.
	 *
	 * @param   string  $widget_id    The widget ID.
	 * @param   array   $widget_args  The widget instance data.
	 * @param   string  $tab_id       The tab ID.
	 *
	 * @return  string                The widget HTML.
	 */
	public static function render_widget( $widget_id, $widget_args, $tab_id ) {

		$html = '';
		/**
		 * Allow outside code to short-circuit this whole function
		 * and render a custom widget.
		 *
		 * @param  string  $html    The empty string of HTML.
		 * @param  array   $widget  The widget instance args.
		 */
		if ( ! empty( apply_filters( 'user_home_screen_pre_render_widget', $html, $widget_args ) ) ) {
			return $html;
		}

		/**
		 * Allow outside code to customize the widget args before rendering.
		 *
		 * @param  array  $widget  The widget instance data.
		 */
		$widget_args = apply_filters( 'user_home_screen_widget_args', $widget_args );

		$type_class = 'type-' . $widget_args['type'];

		ob_start();

		?>
		<div class="uhs-widget postbox <?php echo esc_attr( $type_class ); ?>" data-widget-id="<?php echo esc_attr( $widget_id ); ?>" data-tab-id="<?php echo esc_attr( $tab_id ); ?>">
			<div class="uhs-widget-top-bar">
				<button type="button" class="uhs-toggle-widget-info"><span class="dashicons dashicons-arrow-down"></span></button>
				<button type="button" class="uhs-remove-widget"><span class="dashicons dashicons-no-alt"></span></button>
				<h2 class="uhs-widget-title hndle ui-sortable-handle">
					<span><?php echo esc_html( $widget_args['args']['title'] ); ?></span>
				</h2>
			</div>
			<div class="uhs-widget-info">
				<?php echo self::output_widget_info( $widget_id, $widget_args ); ?>
			</div>
			<?php
				switch ( $widget_args['type'] ) {
					case 'post-list':
						echo self::render_post_list_widget_placeholder( $widget_args['args'] );
						break;
					case 'rss-feed':
						echo self::render_rss_feed_widget_placeholder( $widget_args['args']['feed_url'] );
						break;
				}
			?>
		</div>
		<?php

		$html = ob_get_clean();

		/**
		 * Allow the widget HTML to be customized.
		 *
		 * @param  string  $html         The default widget html.
		 * @param  array   $widget_args  The widget instance data.
		 */
		return apply_filters( 'user_home_screen_widget_html', $html, $widget_args );
	}

	/**
	 * Output a widget info panel.
	 *
	 * @param   string  $widget_id    The widget ID.
	 * @param   array   $widget_args  The widget args.
	 *
	 * @return  string                The widget info HTML.
	 */
	public static function output_widget_info( $widget_id, $widget_args ) {

		$widget_type_data = user_home_screen_get_widget_type_data();
		$widget_info      = '';

		// Add a standard Widget Type field if the widget type has a label.
		if (
			! empty( $widget_type_data[ $widget_args['type'] ] ) &&
			! empty( $widget_type_data[ $widget_args['type'] ]['label'] )
		) {
			$widget_info .= sprintf(
				'<div class="%s"><span class="%s">%s:</span> %s</div>',
				'uhs-widget-info-type',
				'uhs-widget-info-label',
				esc_html__( 'Widget Type', 'user-home-screen' ),
				esc_html( $widget_type_data[ $widget_args['type'] ]['label'] )
			);
		}

		// Add any widget info fields that have been saved in the widget args.
		if ( ! empty( $widget_args['args']['widget_info'] ) ) {
			foreach ( $widget_args['args']['widget_info'] as $arg_key => $arg_info ) {
				$widget_info .= sprintf(
					'<div class="%s">%s</div>',
					'uhs-widget-info-' . str_replace( '_', '-', esc_attr( $arg_key ) ),
					wp_kses_post( $arg_info )
				);
			}
		}

		/**
		 * Allow the widget info to be customized and custom widget types to be handled.
		 *
		 * This filter is used internally to add extra functionality for specific widget
		 * types into the widget info section.
		 *
		 * @param  string  $widget_info  The default widget info.
		 * @param  string  $widget_id    The current widget ID.
		 * @param  array   $widget_args  The current widget args.
		 */
		$widget_info = apply_filters( 'user_home_screen_widget_info', $widget_info, $widget_id, $widget_args );

		ob_start();

		?>
		<div class="uhs-widget-info-inner">
			<?php echo $widget_info; ?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Output extra content in the widget info section of certain widget types.
	 *
	 * @param   string  $widget_info  The default widget info.
	 * @param   string  $widget_id    The current widget ID.
	 * @param   array   $widget_args  The current widget args.
	 *
	 * @return  string                The updated widget info.
	 */
	public function output_widget_info_extras( $widget_info, $widget_id, $widget_args ) {

		if ( 'post-list' === $widget_args['type'] ) {

			$template_parts          = user_home_screen_get_post_list_template_parts();
			$template_parts_selector = '<div class="uhs-post-list-template-part-selector">';
			$template_parts_selector .= '<h3 class="uhs-post-list-template-part-selector-title">' . esc_html__( 'Template Parts', 'user-home-screen' ) . '</h3>';

			foreach ( $template_parts as $part => $name ) {

				$part_slug  = str_replace( '_', '-', $part );
				$class      = 'uhs-post-list-template-part-selector-for-' . $part_slug;
				$show_class = 'uhs-post-list-show-' . $part_slug;
				$checked    = ( in_array( $part, $widget_args['args']['template_parts'] ) ) ? 'checked="true"' : '';

				$template_parts_selector .= sprintf(
					'<div class="%s"><label><input type="checkbox" data-show-class="%s" data-template-part="%s" %s /><span>%s</span></label></div>',
					esc_attr( $class ),
					esc_attr( $show_class ),
					esc_attr( $part ),
					$checked,
					esc_html( $name )
				);
			}

			$template_parts_selector .= sprintf(
				'<button type="button" class="%s">%s</button><span class="%s">%s</span><span class="%s"></span>',
				'uhs-post-list-template-part-selector-save button button-secondary',
				esc_html__( 'Save Template Parts', 'user-home-screen' ),
				'uhs-post-list-template-part-selector-save-confirm',
				esc_html__( 'Widget Saved', 'user-home-screen' ),
				'uhs-spinner spinner'
			);

			$template_parts_selector .= '</div>';

			$widget_info .= $template_parts_selector;
		}

		return $widget_info;
	}

	/**
	 * Return the HTML placeholder for a Post List widget.
	 *
	 * @param   array  $args  The widget args.
	 *
	 * @return  string        The widget HTML.
	 */
	public static function render_post_list_widget_placeholder( $args ) {

		$parts          = ( ! empty( $args['template_parts'] ) ) ? $args['template_parts'] : array();
		$classes        = array();
		$template_parts = user_home_screen_get_post_list_template_parts();

		foreach ( $template_parts as $template_part => $template_part_name ) {
			if ( in_array( $template_part, $parts ) ) {
				$classes[] = 'uhs-post-list-show-' . str_replace( '_', '-', $template_part );
			}
		}

		$classes = implode( ' ', $classes );

		ob_start();

		?>
		<div class="uhs-post-list-widget-posts-wrap <?php echo esc_attr( $classes ); ?>">
			<div class="uhs-post-list-widget-posts">
				<span class="uhs-spinner spinner"></span>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render a post-list widget.
	 *
	 * @param   string  $widget_id           The widget ID.
	 * @param   array   $args                The widget args.
	 * @param   bool    $include_pagination  Whether to include the pagination HTML.
	 *
	 * @return  string                       The widget HTML.
	 */
	public static function render_post_list_widget( $widget_id, $args, $include_pagination = true ) {

		$html = '';

		// Bail if we don't have query args.
		if ( empty( $args['query_args'] ) ) {
			return $html;
		}

		// Make the query.
		$query = new WP_Query( $args['query_args'] );

		ob_start();

		if ( $query->have_posts() ) {

			// Determine which "page" we're on in the pagination sense.
			$page = ( ! empty( $query->query_vars['paged'] ) ) ? (int) $query->query_vars['paged'] : 1;

			// Determine which set of posts we're on in the pagination sense.
			if ( $page < $query->max_num_pages ) {
				$current_posts_min = ( $query->post_count * ( $page - 1 ) ) + 1;
				$current_posts_max = $query->post_count * $page;
			} else {
				$current_posts_min = $query->found_posts - $query->post_count + 1;
				$current_posts_max = $query->found_posts;
			}

			printf(
				'<div class="%s" data-current-page="%s" data-total-pages="%s" data-current-post-min="%s" data-current-post-max="%s">',
				'uhs-post-list-widget-posts',
				esc_attr( $page ),
				esc_attr( $query->max_num_pages ),
				esc_attr( $current_posts_min ),
				esc_attr( $current_posts_max )
			);

			while ( $query->have_posts() ) {

				$query->the_post();

				$custom_html = '';
				/**
				 * Allow custom HTML to be used.
				 *
				 * @param  string    $custom_template  The HTML for a custom template.
				 * @param  WP_Post   $post             The current post object.
				 * @param  WP_Query  $query            The current query object.
				 * @param  array     $args             The array of widget args.
				 * @param  array     $parts            The array of template parts.
				 */
				$custom_html = apply_filters( 'user_home_screen_post_list_widget_post_html', $custom_html, $query->post, $query, $args, $parts );

				// Use custom HTML if provided, otherwise use the default HTML.
				if ( ! empty( $custom_html ) ) {

					echo $custom_html;

				} else {

					$post_type   = get_post_type_object( $query->post->post_type );
					$post_status = get_post_status_object( $query->post->post_status );
					$post_title  = ( ! empty( get_the_title( $query->post->ID ) ) ) ? get_the_title( $query->post->ID ) : __( 'Untitled', 'user-home-screen' );

					?>
					<div class="uhs-post-list-widget-post">
						<div class="uhs-post-list-widget-left">
							<h3 class="uhs-post-list-widget-post-title">
								<a href="<?php echo esc_url( get_edit_post_link( $query->post->ID, false ) ); ?>" target="_blank">
									<?php echo esc_html( $post_title ); ?>
								</a>
							</h3>
							<div class="uhs-post-list-widget-author">
								<?php echo esc_html__( 'By', 'user-home-screen' ) . ' ' . get_the_author(); ?>
							</div>
						</div>
						<div class="uhs-post-list-widget-right">
							<div class="uhs-post-list-widget-post-type">
								<?php echo esc_html( $post_type->labels->singular_name ); ?>
							</div>
							<div class="uhs-post-list-widget-status">
								<?php echo esc_html( $post_status->label ); ?>
							</div>
							<div class="uhs-post-list-widget-publish-date">
								<?php echo get_the_date(); ?>
							</div>
							<div class="uhs-post-list-widget-modified-date">
								<?php echo get_the_modified_date(); ?>
							</div>
							<div class="uhs-post-list-widget-category">
								<?php echo user_home_screen_get_taxonomy_term_list( $query->post->ID, 'category', '', ', ', false ); ?>
							</div>
						</div>
					</div>
					<?php
				}
			}

			echo '</div>';

			if ( $include_pagination ) {
				echo self::render_post_list_widget_pagination( $current_posts_min, $current_posts_max, $query->found_posts );
			}

			wp_reset_postdata();

		} else {

			?>
			<h3><?php esc_html_e( 'No Posts Found...', 'user-home-screen' ); ?></h3>
			<?php
		}

		return ob_get_clean();
	}

	/**
	 * Return the HTML for the pagination section of the Post List widget.
	 *
	 * @param   int  $current_posts_min  The current first post in the list.
	 * @param   int  $current_posts_max  The current last post in the list.
	 * @param   int  $found_posts        The number of found posts.
	 *
	 * @return  string                   The pagination HTML.
	 */
	public static function render_post_list_widget_pagination( $current_posts_min, $current_posts_max, $found_posts = 0 ) {

		// Determine whether to initially show next and previous links.
		if ( $current_posts_max < $found_posts ) {
			if ( $current_posts_min === 1 ) {

				// We're on the first page and only need to output next.
				$include_next = true;

			} elseif ( $current_posts_max === $found_posts ) {

				// We're on the last page and only need to output previous.
				$include_previous = true;

			} else {

				// We're on a page that is not the first or last and need to output the full pagination.
				$include_next     = true;
				$include_previous = true;
			}
		}

		$prev_class = ( ! empty( $include_previous ) ) ? 'uhs-visible' : '';
		$next_class = ( ! empty( $include_next ) ) ? 'uhs-visible' : '';

		ob_start();

		?>
		<div class="uhs-post-list-widget-pagination">
			<div class="uhs-post-list-widget-previous <?php echo esc_attr( $prev_class ); ?>">
				<?php esc_html_e( 'Previous', 'user-home-screen' ); ?>
			</div>
			<div class="uhs-post-list-widget-pagination-numbers">
				<?php
					printf(
						'<span class="%s">%s - %s</span> %s <span class="%s">%s</span>',
						'uhs-post-list-widget-post-x-x',
						esc_html( $current_posts_min ),
						esc_html( $current_posts_max ),
						__( 'of', 'user-home-screen' ),
						'uhs-post-list-widget-total-posts',
						esc_html( $found_posts )
					);
				?>
			</div>
			<div class="uhs-post-list-widget-next <?php echo esc_attr( $next_class ); ?>">
				<?php esc_html_e( 'Next', 'user-home-screen' ); ?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Return the HTML placeholder for the RSS Feed widget.
	 *
	 * @param   string  $feed_url  The feed URL.
	 *
	 * @return  string             The widget HTML.
	 */
	public static function render_rss_feed_widget_placeholder( $feed_url ) {

		ob_start();

		?>
		<div class="uhs-rss-feed-widget-feed-content" data-feed-url="<?php echo esc_url( $feed_url ); ?>">
			<span class="uhs-spinner spinner"></span>
			<div class="uhs-feed-content-wrap"></div>
			<?php echo self::render_rss_feed_widget_pagination(); ?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Return the HTML for the pagination section of the RSS Feed widget.
	 *
	 * @return  string  The pagination HTML.
	 */
	public static function render_rss_feed_widget_pagination() {

		ob_start();

		?>
		<div class="uhs-rss-feed-widget-pagination">
			<div class="uhs-rss-feed-widget-previous">
				<?php esc_html_e( 'Previous', 'user-home-screen' ); ?>
			</div>
			<div class="uhs-rss-feed-widget-pagination-numbers">
				<?php
					printf(
						'%s <span class="%s"></span>',
						esc_html__( 'Page', 'user-home-screen' ),
						'uhs-rss-feed-widget-page-x'
					);
				?>
			</div>
			<div class="uhs-rss-feed-widget-next">
				<?php esc_html_e( 'Next', 'user-home-screen' ); ?>
			</div>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Output our JS templates.
	 */
	public function output_js_templates() {

		// Templates.
		$templates = array(
			USER_HOME_SCREEN_PATH . 'templates/widget-edit.php',
			USER_HOME_SCREEN_PATH . 'templates/tab-edit.php',
			USER_HOME_SCREEN_PATH . 'templates/field-text.php',
			USER_HOME_SCREEN_PATH . 'templates/field-select.php',
			USER_HOME_SCREEN_PATH . 'templates/field-select-multiple.php',
			USER_HOME_SCREEN_PATH . 'templates/confirm.php',
			USER_HOME_SCREEN_PATH . 'templates/message.php',
		);

		// Loop over each template and include it.
		foreach ( $templates as $template ) {

			/**
			 * Allow the template paths to be filtered.
			 *
			 * This filter makes it possible for outside code to swap our templates
			 * for custom templates. As long as the template ID and data object keys
			 * are kept the same everything should still work.
			 *
			 * @param  string  $template  The template path.
			 */
			include_once apply_filters( 'user_home_screen_js_templates', $template );
		}
	}
}
