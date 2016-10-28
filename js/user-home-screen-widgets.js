/**
 * User Home Screen Widgets JS.
 */

var userHomeScreenWidgets = ( function( $, data ) {

	/**
	 * Store key DOM references.
	 */
	var $navTabs         = $();
	var $postListWidgets = $();
	var $feedWidgets     = $();

	/**
	 * Initialize.
	 */
	var init = function() {

		$navTabs         = $( 'h2.nav-tab-wrapper .nav-tab' );
		$postListWidgets = $( '.uhs-widget.type-post-list' );
		$feedWidgets     = $( '.uhs-widget.type-rss-feed' );

		// Initialize Post List widgets.
		initPostListWidgets();

		// Initialize RSS Feed widgets.
		initRssFeedWidgets();
	};

	/**
	 * Initialize Post List widgets.
	 */
	var initPostListWidgets = function() {

		$postListWidgets.each( function() {

			var $widget     = $( this );
			var $pagination = $widget.find( '.uhs-post-list-widget-pagination' );
			var $prevPage   = $pagination.find( '.uhs-post-list-widget-previous' );
			var $nextPage   = $pagination.find( '.uhs-post-list-widget-next' );

			// Handle clicks on the pagination controls.
			$prevPage.on( 'click', function() {
				handlePostListPaginationClick( 'previous', $widget );
			});
			$nextPage.on( 'click', function() {
				handlePostListPaginationClick( 'next', $widget );
			});
		});
	};

	/**
	 * Handle clicks on the pagination controls.
	 *
	 * @param {string} direction - Which direction we're paginating in.
	 * @param {object} $widget   - A jQuery object containing the widget.
	 */
	var handlePostListPaginationClick = function( direction, $widget ) {

		var widgetID    = $widget.attr( 'data-widget-id' );
		var tabID       = $navTabs.filter( '.nav-tab-active' ).attr( 'data-tab-id' );
		var currentPage = $widget.find( '.uhs-post-list-widget-posts' ).attr( 'data-current-page' );
		var newPage     = ( 'next' === direction ) ? currentPage + 1 : currentPage - 1;
		var $postList   = $widget.find( '.uhs-post-list-widget-posts' );

		// Bail if the new page somehow ended up being 0;
		if ( 0 === newPage ) {
			return;
		}

		var ajaxData = {
			'action':    'uhs_post_list_get_page',
			'nonce':     data.nonce,
			'widget_id': widgetID,
			'tab_id'   : tabID,
			'page'     : newPage,
		};

		$postList.html( $( '<span />' ).attr( 'class', 'spinner uhs-spinner' ) );

		// Make an Ajax request to get the new Posts.
		var request = $.post( ajaxurl, ajaxData );

		request.done( function( response ) {
			console.log( response );
			console.log( response.posts_html );

			$postList.replaceWith( response.posts_html );
		});

		request.fail( function( response ) {
			console.log( 'Something went wrong when trying to fetch new posts in a Post List widget.' );
		});
	};

	/**
	 * Initialize RSS Feed widgets.
	 */
	var initRssFeedWidgets = function() {

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
