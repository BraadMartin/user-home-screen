/**
 * User Home Screen JS.
 */

var userHomeScreen = ( function( $ ) {

	/**
	 * Store key DOM references.
	 */
	var $wrap    = $();
	var $navTabs = $();

	/**
	 * Initialize.
	 */
	var init = function() {

		// Setup key DOM references.
		$wrap    = $( '#user-home-screen-wrap' );
		$navTabs = $( 'h2.nav-tab-wrapper .nav-tab' );

		// Setup events.
		setupEvents();
	};

	/**
	 * Setup events.
	 */
	var setupEvents = function() {

		// When a nav tab is clicked, toggle classes.
		$navTabs.on( 'click', function() {

			var $this = $( this );

			// If the clicked tab was already active, do nothing.
			if ( $this.hasClass( 'nav-tab-active' ) ) {
				return;
			}

			// Remove the active class from all tabs.
			$navTabs.removeClass( 'nav-tab-active' );

			// Set the active class on the clicked tab.
			$this.addClass( 'nav-tab-active' );

			// Set an attribute on the wrapper indicating which tab is active.
			$wrap.attr( 'data-active-tab', $this.attr( 'data-tab-id' ) );
		});
	};

	return {
		init: init,
	};

})( jQuery );

// Start the party.
jQuery( document ).ready( function() {
	userHomeScreen.init();
});