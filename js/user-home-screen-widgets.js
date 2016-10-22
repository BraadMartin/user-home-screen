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
			var $spinner     = $widget.find( '.uhs-spinner' );

			$spinner.addClass( 'uhs-visible' );

			$feedContent.rss(
				feedURL,
				{
					limit: 10,
					offsetStart: false,
					offsetEnd: false,
					ssl: true,
					layoutTemplate: '<div class="uhs-feed-content-wrap">{entries}</div>',
					entryTemplate: '<div class="uhs-feed-item"><div class="uhs-feed-item-left"><h3 class="uhs-feed-item-title"><a href="{url}">{title}</a></h3></div><div class="uhs-feed-item-right"><div class="uhs-feed-item-date">{date}</div><div class="uhs-feed-item-author">{author}</div></div><div class="uhs-feed-item-content">{shortBodyPlain}...</div></div>',
					tokens: {},
					dateFormat: 'MMM Do, YYYY',
					error: function(){},
					success: function(){
						$feedContent.addClass( 'uhs-loaded' );
					},
					onData: function(){
						$spinner.removeClass( 'uhs-visible' );
					},
				}
			);
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
