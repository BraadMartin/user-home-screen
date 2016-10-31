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
	 * The meta key we store all user tabs under.
	 */
	public static $user_tabs_meta_key = '_uhs_user_tabs';

	/**
	 * The meta key we store all other user options under.
	 */
	public static $user_options_meta_key = '_uhs_user_options';

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

		// Maybe redirect the default WordPress dashboard to User Home Screen.
		add_action( 'wp_dashboard_setup', array( $this, 'maybe_redirect_dashboard' ) );

		// Output our user profile fields.
		add_action( 'personal_options', array( $this, 'output_user_profile_fields' ) );

		// Save our options from the user profile screen.
		add_action( 'personal_options_update', array( $this, 'save_user_profile_options' ) );
		add_action( 'edit_user_profile_update', array( $this, 'save_user_profile_options' ) );

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

		add_action( 'wp_ajax_uhs_post_list_get_page', array( $this, 'ajax_post_list_widget_get_posts' ) );
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
			'add_widget'            => __( 'Add Widget', 'user-home-screen' ),
			'remove_widget'         => __( 'Remove Widget', 'user-home-screen' ),
			'remove_widget_confirm' => __( 'Are you sure you want to remove the selected widget?', 'user-home-screen' ),
			'edit_widget'           => __( 'Edit Widget', 'user-home-screen' ),
			'select_widget_type'    => __( 'Select widget type', 'user-home-screen' ),
			'select_default'        => __( 'Select', 'user-home-screen' ),
			'add_tab'               => __( 'Add Tab', 'user-home-screen' ),
			'remove_tab'            => __( 'Remove Tab', 'user-home-screen' ),
			'remove_tab_confirm'    => __( 'Are you sure you want to remove the selected tab? Widgets added to this tab will also be removed.', 'user-home-screen' ),
			'tab_name'              => __( 'Tab Name', 'user-home-screen' ),
			'no_tabs_notice'        => __( 'Please add a tab first, then you can add widgets', 'user-home-screen' ),
		);

		// Add widget type data.
		$data['widget_types'] = self::get_widget_type_data();

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
	public static function get_widget_type_data() {

		$post_types       = self::get_post_types();
		$categories       = self::get_categories();
		$post_statuses    = self::get_post_statuses();
		$authors          = self::get_authors();
		$order_by_options = self::get_order_by_options();

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
						'values' => array(
							'DESC' => __( 'Descending', 'user-home-screen' ),
							'ASC'  => __( 'Ascending', 'user-home-screen' ),
						),
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
	public static function get_post_types() {

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
	 * Return an array of categories that should be selectable in widgets.
	 *
	 * @return  array  The array of categories.
	 */
	public static function get_categories() {

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
	 * Return an array of post statuses that should be selectable in widgets.
	 *
	 * @return  array  The array of post statuses.
	 */
	public static function get_post_statuses() {

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
	public static function get_authors() {

		$full_users = get_users( array( 'orderby' => 'display_name', 'order' => 'ASC' ) );
		$authors    = array();

		// Transform into a simple array of user ID => Display name.
		// We have to prefix the ID here because otherwise the array would
		// index based on ID.
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
	 * Return an array of order by options that should be selectable in widgets.
	 *
	 * @return  array  The array of order by options.
	 */
	public static function get_order_by_options() {

		$order_by_options = array(
			'date'     => __( 'Publish Date', 'user-home-screen' ),
			'modified' => __( 'Last Modified Date', 'user-home-screen' ),
			'author'   => __( 'Author', 'user-home-screen' ),
			'title'    => __( 'Title', 'user-home-screen' ),
			'type'     => __( 'Post Type', 'user-home-screen' ),
		);

		/**
		 * Allow the selectable order by options to be filtered.
		 *
		 * @param  array  $order_by_options  The default array of selectable order by options.
		 */
		return apply_filters( 'user_home_screen_selectable_order_by_options', $order_by_options );
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
	 * Save our options from the user profile screen.
	 *
	 * @param  int  $user_id  The current user's ID.
	 */
	public function save_user_profile_options( $user_id ) {

		$nonce_valid = ( ! empty( $_POST['uhs-user-profile-nonce'] ) ) ? wp_verify_nonce( $_POST['uhs-user-profile-nonce'], 'uhs_user_profile' ) : false;

		if ( $nonce_valid ) {

			$existing_options = get_user_meta( $user_id, self::$user_options_meta_key, true );
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

			update_user_meta( $user_id, self::$user_options_meta_key, $updated_options );
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
	 * Return the user widgets config for the passed in user.
	 *
	 * @param  WP_User  $user  The current user object.
	 */
	public static function get_user_widgets( $user ) {

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
	 * Return the user tabs config for the passed in user.
	 *
	 * @param  WP_User  $user  The current user object.
	 */
	public function get_user_tabs( $user ) {

		$user_tabs = get_user_meta( $user->ID, self::$user_tabs_meta_key, true );

		/**
		 * Allow the user tabs config to be customized.
		 *
		 * @param  array    $user_widgets  The user tabs config.
		 * @param  WP_User  $user          The current user object.
		 */
		return apply_filters( 'user_home_screen_user_tabs', $user_tabs, $user );
	}

	/**
	 * Output the user home screen.
	 */
	public function output_user_home_screen() {

		// Set up the user data.
		$user         = wp_get_current_user();
		$user_name    = ( ! empty( $user->data->display_name ) ) ? $user->data->display_name : '';
		$user_tabs    = $this->get_user_tabs( $user );
		$user_widgets = self::get_user_widgets( $user );

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
					$widgets_html .= self::render_widget( $widget_id, $widget_args );
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
	 * @param   array  $widget  The widget instance data.
	 *
	 * @return  string          The widget HTML.
	 */
	public static function render_widget( $widget_id, $widget_args ) {

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
		<div class="uhs-widget postbox <?php echo esc_attr( $type_class ); ?>" data-widget-id="<?php echo esc_attr( $widget_id ); ?>">
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
						echo self::render_post_list_widget( $widget_id, $widget_args['args'] );
						break;
					case 'rss-feed':
						echo self::render_rss_feed_widget( $widget_id, $widget_args['args'] );
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

		$widget_info = '';
		/**
		 * Allow the widget info to be customized and custom widget types to be handled.
		 *
		 * @param  string  $widget_id    The current widget ID.
		 * @param  array   $widget_args  The current widget args.
		 */
		$widget_info = apply_filters( 'user_home_screen_widget_info', $widget_info, $widget_id, $widget_args );

		// Use custom widget info if specified.
		if ( ! empty( $widget_info ) ) {
			return $widget_info;
		}

		$widget_type_data = self::get_widget_type_data();

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

		ob_start();

		?>
		<div class="uhs-widget-info-inner">
			<?php echo $widget_info; ?>
		</div>
		<?php

		return ob_get_clean();
	}

	/**
	 * Render a post-list widget.
	 *
	 * @param   string  $widget_id          The widget ID.
	 * @param   array   $args               The widget args.
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

		$parts = ( ! empty( $args['parts'] ) ) ? $args['parts'] : array();

		// Make the query.
		$query = new WP_Query( $args['query_args'] );

		ob_start();

		if ( $query->have_posts() ) {

			$page = ( ! empty( $query->query_vars['paged'] ) ) ? (int) $query->query_vars['paged'] : 1;

			printf(
				'<div class="%s" data-current-page="%s">',
				'uhs-post-list-widget-posts',
				esc_attr( $page )
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

					?>
					<div class="uhs-post-list-widget-post">
						<div class="uhs-post-list-widget-left">
							<h3 class="uhs-post-list-widget-post-title">
								<a href="<?php echo esc_url( get_edit_post_link( $query->post->ID, false ) ); ?>">
									<?php echo esc_html( get_the_title( $query->post->ID ) ); ?>
								</a>
							</h3>
							<?php if ( in_array( 'author', $parts ) ) : ?>
							<div class="uhs-post-list-widget-post-author">
								<?php echo esc_html__( 'By', 'user-home-screen' ) . ' ' . get_the_author(); ?>
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
								<?php echo get_the_date(); ?>
							</div>
							<?php endif; ?>
							<?php if ( in_array( 'modified_date', $parts ) ) : ?>
							<div class="uhs-post-list-widget-post-modified-date">
								<?php echo get_the_modified_date(); ?>
							</div>
							<?php endif; ?>
							<?php if ( in_array( 'category', $parts ) ) : ?>
							<div class="uhs-post-list-widget-categories">
								<?php echo self::get_taxonomy_term_list( $query->post->ID, 'category', '', ', ', false ); ?>
							</div>
							<?php endif; ?>
						</div>
					</div>
					<?php
				}
			}

			echo '</div>';

			if ( $include_pagination ) {
				echo self::render_post_list_widget_pagination( $page, $query->max_num_pages, $query->found_posts );
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
	 * @param   int  $current_page  The current page we're on.
	 * @param   int  $max_pages     The maximum number of pages for the query.
	 *
	 * @return  string              The pagination HTML.
	 */
	public static function render_post_list_widget_pagination( $current_page = 1, $max_pages = 1 ) {

		// Determine whether to initially show next and previous links.
		if ( $max_pages > 1 ) {
			if ( $current_page === 1 ) {

				// We're on the first page and only need to output next.
				$include_next = true;

			} elseif ( $current_page === $max_pages ) {

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
						'<span class="%s">%s</span> %s <span class="%s">%s</span>',
						'uhs-post-list-widget-page-x',
						esc_html( $current_page ),
						__( 'of', 'user-home-screen' ),
						'uhs-post-list-widget-page-x-of',
						esc_html( $max_pages )
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
	 * Render an rss-feed widget.
	 *
	 * @param   string  $widget_id  The widget ID.
	 * @param   array   $args       The widget args.
	 *
	 * @return  string              The widget HTML.
	 */
	public static function render_rss_feed_widget( $widget_id, $args ) {

		ob_start();

		?>
		<div class="uhs-rss-feed-widget-feed-content" data-feed-url="<?php echo esc_url( $args['feed_url'] ); ?>">
			<span class="uhs-spinner spinner"></span>
		</div>
		<?php

		return ob_get_clean();
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
		$this->add_tab_for_user( $tab_name, $user );

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

		$this->remove_tab_for_user( $tab_id, $user, true );

		$response          = new stdClass();
		$response->message = esc_html__( 'It appears to have worked', 'user-home-screen' );

		wp_send_json( $response );

		wp_die();
	}

	/**
	 * Add a tab to a user's home screen.
	 *
	 * @param  array    $tab_data  The array of tab data.
	 * @param  WP_User  $user      The current user object.
	 */
	public function add_tab_for_user( $tab_data, $user ) {

		// Get existing tab data for the user.
		$tabs_data = get_user_meta( $user->ID, self::$user_tabs_meta_key, true );

		error_log( 'existing tabs data' );
		error_log( print_r( $tabs_data, true ) );

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

		$tabs_data = get_user_meta( $user->ID, self::$user_tabs_meta_key, true );

		if ( empty( $tabs_data ) || ! is_array( $tabs_data ) ) {
			$tabs_data = array();
		}
		if ( ! empty( $tabs_data[ $tab_key ] ) ) {
			unset( $tabs_data[ $tab_key ] );
		}

		$this->update_tabs_for_user( $tabs_data, $user );

		// Also remove widgets for the tab if set to.
		if ( $remove_widgets ) {

			$wigets_data = get_user_meta( $user->ID, self::$user_widgets_meta_key, true );

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

		error_log( 'about to save user tabs' );
		error_log( print_r( $tabs_data, true ) );

		/**
		 * Allow the tabs data to be customized as it's being saved.
		 *
		 * @param  array    $tabs_data  The array of tabs data.
		 * @param  WP_User  $user       The user object being updated.
		 */
		$tabs_data = apply_filters( 'user_home_screen_update_tabs_data', $tabs_data, $user );

		update_user_meta( $user->ID, self::$user_tabs_meta_key, $tabs_data );
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
			USER_HOME_SCREEN_PATH . 'templates/tab-edit.php',
			USER_HOME_SCREEN_PATH . 'templates/field-text.php',
			USER_HOME_SCREEN_PATH . 'templates/field-select.php',
			USER_HOME_SCREEN_PATH . 'templates/field-select-multiple.php',
			USER_HOME_SCREEN_PATH . 'templates/confirm.php',
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

		$widget_data = $this->validate_widget_data( $widget_data );

		$this->add_widget_for_user( $widget_data, $user );

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

		$this->remove_widget_for_user( $widget_id, $tab_id, $user );

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
		$user_widgets         = get_user_meta( $user->ID, self::$user_widgets_meta_key, true );
		$user_widgets_for_tab = $user_widgets[ $tab_id ];
		$updated_widgets      = array();

		// Clear widgets under the tab key that we're updating the order on.
		unset( $user_widgets[ $tab_id ] );

		// Add the widgets for the tab back in the new order.
		foreach ( $widget_order as $widget_id ) {
			$updated_widgets[ $tab_id ][ $widget_id ] = $user_widgets_for_tab[ $widget_id ];
		}

		$this->update_widgets_for_user( $updated_widgets, $user );

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

		$widget_id    = sanitize_text_field( $_POST['widget_id'] );
		$tab_id       = sanitize_text_field( $_POST['tab_id'] );
		$page         = (int) $_POST['page'];
		$user_widgets = self::get_user_widgets( $user );

		// Bail if the widget doesn't exist for the user.
		if ( empty( $user_widgets[ $tab_id ][ $widget_id ] ) ) {
			$response          = new stdClass();
			$response->message = esc_html__( 'Sorry, the requested widget doesn\'t appear to exist.', 'user-home-screen' );
			wp_send_json( $response );
			wp_die();
		}

		$args = $user_widgets[ $tab_id ][ $widget_id ];

		// Modify the query args to include the new page.
		$args['args']['query_args']['paged'] = $page;

		$html = self::render_post_list_widget( $widget_id, $args['args'], false );

		$response             = new stdClass();
		$response->message    = esc_html__( 'It appears to have worked', 'user-home-screen' );
		$response->posts_html = $html;
		$response->new_page   = $page;

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
		$widgets_data = get_user_meta( $user->ID, self::$user_widgets_meta_key, true );

		error_log( 'widgets data before add' );
		error_log( print_r( $widgets_data, true ) );

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

		error_log( 'widgets data after add' );
		error_log( print_r( $widgets_data, true ) );

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

		$widgets_data = get_user_meta( $user->ID, self::$user_widgets_meta_key, true );

		if ( empty( $widgets_data ) || ! is_array( $widgets_data ) ) {
			$widgets_data = array();
		}

		error_log( 'widgets data before remove' );
		error_log( print_r( $widgets_data, true ) );

		if ( ! empty( $widgets_data[ $tab_id ][ $widget_id ] ) ) {
			unset( $widgets_data[ $tab_id ][ $widget_id ] );
		}

		error_log( 'widgets data after remove' );
		error_log( print_r( $widgets_data, true ) );

		$this->update_widgets_for_user( $widgets_data, $user );
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
		$updated_args                  = array();
		$updated_args['widget_info']   = array();
		$updated_args['query_args']    = array();
		$updated_args['parts']         = array();

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
		}

		// Order.
		if ( ! empty( $args['order'] ) ) {
			$updated_args['query_args']['order'] = sanitize_text_field( $args['order'] );
		}

		// Parts.
		if ( ! empty( $args['parts'] ) ) {
			$updated_args['parts'] = $args['parts'];
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

			$updated_args['parts'] = $parts;
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

		$updated_args = $args;

		return $updated_args;
	}

	/**
	 * Build and return the HTML for a taxonomy term list.
	 *
	 * @param   int     $post_id    The post ID to use.
	 * @param   string  $taxonomy   The taxonomy slug to output terms from.
	 * @param   string  $label      The label to use.
	 * @param   string  $separator  The separation string.
	 * @param   bool    $link       Whether to link the terms.
	 *
	 * @return  string              The term list HTML.
	 */
	public static function get_taxonomy_term_list( $post_id = 0, $taxonomy = '', $label = '', $separator = ', ', $link = true ) {

		// Taxonomy is required.
		if ( ! $taxonomy ) {
			return '';
		}

		if ( ! $post_id ) {
			$post_id = get_the_ID();
		}

		$terms_args = array(
			'orderby' => 'name',
			'order'   => 'ASC',
			'fields'  => 'all',
		);

		$terms = wp_get_post_terms( $post_id, $taxonomy, $terms_args );

		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}

		$output = sprintf(
			'<div class="%s %s">%s',
			'entry-tax-term-list',
			esc_attr( $taxonomy ) . '-tax-term-list',
			$label
		);

		$i = 0;

		foreach ( $terms as $term_slug => $term_obj ) {

			if ( $link ) {
				$output .= sprintf(
					'<a href="%s" rel="%s %s">%s</a>',
					get_term_link( $term_obj->term_id ),
					esc_attr( $term_obj->slug ),
					esc_attr( $term_obj->taxonomy ),
					esc_html( $term_obj->name )
				);
			} else {
				$output .= esc_html( $term_obj->name );
			}

			$i++;

			if ( count( $terms ) > $i ) {
				$output .= $separator;
			}
		}
		$output .= '</div>';

		return $output;
	}
}
