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
			array( 'jquery' ),
			USER_HOME_SCREEN_VERSION,
			true
		);

		wp_enqueue_style(
			'user-home-screen-css',
			USER_HOME_SCREEN_URL . 'css/user-home-screen.css',
			array(),
			USER_HOME_SCREEN_VERSION
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
	 * @return  string                  The "Main" tab HTML.
	 */
	public function output_setup_tab( $user, $user_widgets ) {

		ob_start();

		?>
		<form class="user-home-screen-setup-form">
			YOLO
		</form>
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
}
