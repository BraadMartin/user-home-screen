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
	 * The widget type data.
	 *
	 * @var  array
	 */
	private $widget_types = array();

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

		// Register our core widget types.
		add_action( 'load-toplevel_page_user-home-screen', array( $this, 'register_widget_types' ), 0 );

		// Enqueue our JS and CSS.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts_and_styles' ) );

		// Register our admin page.
		add_action( 'admin_menu', array( $this, 'register_admin_page' ) );

		// Maybe redirect the default WordPress dashboard to User Home Screen.
		add_action( 'wp_dashboard_setup', array( $this, 'maybe_redirect_dashboard' ) );

		// Output our user profile fields.
		add_action( 'personal_options', array( $this, 'output_user_profile_fields' ) );

		// Output extra content in the widget info section of certain widget types.
		add_filter( 'user_home_screen_render_widget_info', 'user_home_screen_include_widget_info_extras', 10, 3 );

		// Initialize data class.
		$this->data = new User_Home_Screen_Data( $this );
		$this->data->init();

		// Initialize Ajax class if we're serving an Ajax request.
		if ( defined( 'DOING_AJAX' ) && DOING_AJAX ) {
			$this->ajax = new User_Home_Screen_Ajax( $this );
			$this->ajax->init();
		}
	}

	/**
	 * Register our core widget types.
	 */
	public function register_widget_types() {

		$post_types       = user_home_screen_get_post_types();
		$categories       = user_home_screen_get_categories();
		$post_statuses    = user_home_screen_get_post_statuses();
		$authors          = user_home_screen_get_authors();
		$order_by_options = user_home_screen_get_order_by_options();
		$order_options    = user_home_screen_get_order_options();

		$core_widget_types = array(
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
					array(
						'key'    => 'order_by',
						'label'  => __( 'Order By', 'user-home-screen' ),
						'type'   => 'select',
						'values' => $order_by_options,
					),
					array(
						'key'    => 'order',
						'label'  => __( 'Order', 'user-home-screen' ),
						'type'   => 'select',
						'values' => $order_options,
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

		// Update our widget types class property and allow widget types registered with the
		// user_home_screen_register_widget_type() function to override our core widget types.
		$this->widget_types = array_merge( $core_widget_types, $this->widget_types );
	}

	/**
	 * Return the registered widget types.
	 *
	 * @return  array  The registered widget types.
	 */
	public function get_widget_types() {

		/**
		 * Allow the widget types data to be customized as it's being retrieved.
		 *
		 * @param  array  $widget_types  The default array of widget types data.
		 */
		return apply_filters( 'user_home_screen_get_widget_types', $this->widget_types );
	}

	/**
	 * Update the registered widget types.
	 *
	 * @param  array  $widget_types  The updated array of widget types.
	 */
	public function update_widget_types( $widget_types ) {

		/**
		 * Allow the widget types data to be customized as it's being updated.
		 *
		 * @param  array  $widget_types  The updated array of widget types data.
		 */
		$this->widget_types = (array) apply_filters( 'user_home_screen_update_widget_types', $widget_types );
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
			<th scope="row"><?php echo esc_html( $redirect_dashboard_label ); ?></th>
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
		<div id="uhs-wrap" class="wrap">
			<div class="uhs-inner-wrap">
				<h1><?php echo esc_html( $page_title ); ?></h1>
				<a class="button button-primary uhs-add-widget"><?php echo esc_html( $add_widget_text ); ?></a>
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
		 * Allow the HTML for User Home Screen to be customized.
		 *
		 * @param  string  $screen_html  The HTML for User Home Screen.
		 */
		echo apply_filters( 'user_home_screen_html', $screen_html );
	}

	/**
	 * Output each of the user's widgets for each of the user's tabs.
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
					$widgets_html .= user_home_screen_render_widget( $widget_id, $widget_args, $tab_key );
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
