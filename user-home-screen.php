<?php
/**
 * Plugin Name: User Home Screen
 * Version:     0.8.0
 * Description: Allows you to build a personalized dashboard with only the things you care about.
 * Author:      Braad Martin
 * Author URI:  http://www.braadmartin.com
 * Text Domain: user-home-screen
 *
 * @package User Home Screen
 */

define( 'USER_HOME_SCREEN_VERSION', '0.8.0' );
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
		$user_home_screen = new User_Home_Screen();
		$user_home_screen->init();
	}
}
