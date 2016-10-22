/**
 * User Home Screen Widgets JS.
 */

var userHomeScreenWidgets = ( function( $, data ) {

	/**
	 * Initialize.
	 */
	var init = function() {

		// Initialize RSS Feed widgets.
		initRssFeedWidgets();
	};

	/**
	 * Initialize RSS Feed widgets.
	 */
	var initRssFeedWidgets = function() {

		var $feedWidgets = $( '.uhs-widget.type-rss-feed' );

		// Bail if we don't have any RSS feed widgets.
		if ( $feedWidgets.length === 0 ) {
			return;
		}

		$feedWidgets.each( function() {

			var $widget      = $( this );
			var $feedContent = $widget.find( '.uhs-rss-feed-widget-feed-content' );
			var feedURL      = $feedContent.attr( 'data-feed-url' );

			console.log( feedURL );

			$feedContent.rss( feedURL );
		});
	};

	return {
		init: init,
	};

})( jQuery, uhsData );

// Start the party.
jQuery( document ).ready( function() {
	userHomeScreenWidgets.init();
});
