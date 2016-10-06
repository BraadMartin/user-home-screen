<?php
/**
 * User Home Screen plugin main class.
 *
 * @package User Home Screen
 */

class User_Home_Screen {

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
			array( 'featherlight', 'jquery', 'wp-util', 'underscore' ),
			USER_HOME_SCREEN_VERSION,
			true
		);

		wp_enqueue_style(
			'user-home-screen-css',
			USER_HOME_SCREEN_URL . 'css/user-home-screen.css',
			array( 'featherlight' ),
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

		$data['widget_types'] = $this->get_widget_type_data();

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
		$post_statuses = $this->get_post_statuses();
		$authors       = $this->get_authors();

		$widget_types = array(
			'post-list' => array(
				'label'  => __( 'Post List', 'user-home-screen' ),
				'fields' => array(
					array(
						'key'    => 'post_types',
						'label'  => __( 'Post Types', 'user-home-screen' ),
						'type'   => 'select',
						'values' => $post_types,
					),
					array(
						'key'    => 'post_statuses',
						'label'  => __( 'Post Statuses', 'user-home-screen' ),
						'type'   => 'select',
						'values' => $post_statuses,
					),
					array(
						'key'    => 'authors',
						'label'  => __( 'Authors', 'user-home-screen' ),
						'type'   => 'select',
						'values' => $authors,					),
				),
			),
			'rss-feed' => array(
				'label' => __( 'RSS Feed', 'user-home-screen' ),
				'fields' => array(
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
		$post_types      = array();

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
			$authors[ $user->data->user_login ] = $user->data->display_name;
		}

		/**
		 * Allow the selectable authors to be filtered.
		 *
		 * @param  array  $authors  The default array of selectable authors.
		 */
		return apply_filters( 'user_home_screen_selectable_authors', $authors );
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

		//$user_widgets = get_user_meta( $user->ID, '_user_home_screen_widgets', true );

		// Mock this for now.
		$user_widgets = array(
			array(
				'type' => 'post-list',
				'args' => array(
					'title' => "Banking Posts",
					'query_args' => array(
						'cat' => 1575,
					),
					'parts' => array(
						'publish_date',
						'status',
					),
				),
			),
			array(
				'type' => 'post-list',
				'args' => array(
					'title' => "Posts by Braad",
					'query_args' => array(
						'author' => 306,
					),
					'parts' => array(
						'status',
					),
				),
			),
			array(
				'type' => 'post-list',
				'args' => array(
					'title' => "Credit Card Posts",
					'query_args' => array(
						'cat' => 5,
					),
					'parts' => array(
						'author',
					),
				),
			),
			array(
				'type' => 'post-list',
				'args' => array(
					'title' => "Posts that are Ready for CE",
					'query_args' => array(
						'post_status' => 'ready-for-ce',
					),
				),
			),
		);

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

		ob_start();

		?>
		<div id="user-home-screen-wrap" class="wrap" data-active-tab="main">
			<div class="user-home-screen-inner-wrap">
				<h1><?php echo esc_html( $page_title ); ?></h1>
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
		<div class="user-home-screen-widget-grid">
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
			'user-home-screen-main-tab',
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
		<div class="user-home-screen-widget-top-bar">
			<button type="button" class="handlediv button-link"><span class="toggle-indicator" aria-hidden="true"></span></button>
			<h2 class="user-home-screen-widget-title hndle ui-sortable-handle">
				<span><?php echo esc_html( $args['title'] ); ?></span>
			</h2>
		</div>
		<?php

		if ( $query->have_posts() ) {

			while ( $query->have_posts() ) {

				$query->the_post();

				?>
				<h3><?php echo esc_html( the_title() ); ?></h3>
				<div class="user-home-screen-widget-extra-data">
					<?php if ( in_array( 'publish_date', $parts ) ) : ?>
					<div class="user-home-screen-widget-post-date">
						<?php the_date(); ?>
					</div>
					<?php endif; ?>
					<?php if ( in_array( 'status', $parts ) ) : ?>
					<div class="user-home-screen-widget-post-status">
						<?php echo esc_html( $query->post->post_status ); ?>
					</div>
					<?php endif; ?>
					<?php if ( in_array( 'author', $parts ) ) : ?>
					<div class="user-home-screen-widget-post-author">
						<?php the_author(); ?>
					</div>
					<?php endif; ?>
				</div>
				<div class="user-home-screen-widget-action-links">
					<a href="<?php echo esc_url( get_edit_post_link( get_the_ID(), false ) ); ?>" target="_blank">
						<?php esc_html_e( 'Edit', 'user-home-screen' ); ?>
					</a>
					<a href="<?php the_permalink(); ?>" target="_blank">
						<?php esc_html_e( 'View', 'user-home-screen' ); ?>
					</a>
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
			'user-home-screen-widget type-post-list postbox',
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

		$add_widget_text = $this->get_js_data()['labels']['add_widget'];

		ob_start();

		?>
		<div class="user-home-screen-setup-form">
			<a class="button button-primary user-home-screen-add-widget"><?php esc_html_e( $add_widget_text ); ?></a>
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
			'user-home-screen-setup-tab',
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
			USER_HOME_SCREEN_PATH . 'templates/field-select.php',
			USER_HOME_SCREEN_PATH . 'templates/field-text.php',
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
}
