<?php
/**
 * Plugin Name: User Home Screen
 * Version:     0.8.2
 * Description: A central admin dashboard that gives users an at-a-glance view into the content and data they care about most.
 * Author:      Braad Martin, NerdWallet
 * Author URI:  https://www.nerdwallet.com
 * Text Domain: user-home-screen
 *
 * @package User Home Screen
 */

define( 'USER_HOME_SCREEN_VERSION', '0.8.2' );
define( 'USER_HOME_SCREEN_PATH', plugin_dir_path( __FILE__ ) );
define( 'USER_HOME_SCREEN_URL', plugin_dir_url( __FILE__ ) );

// Include general functions.
require_once USER_HOME_SCREEN_PATH . 'functions.php';

// Include plugin classes.
require_once USER_HOME_SCREEN_PATH . 'inc/class-user-home-screen.php';
require_once USER_HOME_SCREEN_PATH . 'inc/class-user-home-screen-data.php';
require_once USER_HOME_SCREEN_PATH . 'inc/class-user-home-screen-ajax.php';

add_action( 'plugins_loaded', 'user_home_screen_init' );
/**
 * Initialize.
 */
function user_home_screen_init() {

	// Only if we're serving an admin request.
	if ( is_admin() ) {

		$cap = user_home_screen_user_capability();

		// Only if the current user has the required capability.
		if ( ! current_user_can( $cap ) ) {
			return;
		}

		// Load translation files.
		load_plugin_textdomain(
			'user-home-screen',
			false,
			USER_HOME_SCREEN_PATH . 'languages/'
		);

		// Make the instance of this plugin's class accessible.
		global $user_home_screen;

		$user_home_screen = new User_Home_Screen();
		$user_home_screen->init();
	}
}
