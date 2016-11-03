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

			var $widget   = $( this );
			var widgetID  = $widget.attr( 'data-widget-id' );
			var tabID     = $widget.attr( 'data-tab-id' );

			// Make an Ajax request to fetch the post list HTML.
			var request = ajaxFetchPostList( widgetID, tabID, '1', true );

			request.done( function( response ) {

				if ( response.hasOwnProperty( 'posts_html' ) ) {

					updatePostListWidgetPosts( $widget, response.posts_html );

					// Set up references to the pagination, which we only output once
					// and then update after that.
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

				} else {
					console.log( data.labels.post_list_ajax_fail );
				}
			});

			request.fail( function() {
				console.log( data.labels.post_list_ajax_fail );
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
		var tabID       = $widget.attr( 'data-tab-id' );
		var $postList   = $widget.find( '.uhs-post-list-widget-posts' );
		var currentPage = $postList.attr( 'data-current-page' );
		var newPage     = ( 'next' === direction ) ? parseInt( currentPage ) + 1 : parseInt( currentPage ) - 1;

		// Bail if the new page somehow ended up being 0;
		if ( 0 === newPage ) {
			return;
		}

		$postList.html( $( '<span />' ).attr( 'class', 'spinner uhs-spinner' ) );

		var request = ajaxFetchPostList( widgetID, tabID, newPage, false );

		request.done( function( response ) {
			console.log( response );
			if ( response.hasOwnProperty( 'posts_html' ) ) {

				updatePostListWidgetPosts( $widget, response.posts_html );

			} else {

				console.log( data.labels.post_list_ajax_fail );
			}
		});

		request.fail( function() {
			console.log( data.labels.post_list_ajax_fail );
		});
	};

	/**
	 * Make an ajax request for a list of posts.
	 *
	 * @param  {string} widgetID          - The ID for the Post List widget.
	 * @param  {string} tabID             - The ID for the tab the widget is on.
	 * @param  {number} page              - The current "page" in the pagination sense.
	 * @param  {bool}   includePagination - Whether to include pagination.
	 *
	 * @return {object}                   - The ajax response object.
	 */
	var ajaxFetchPostList = function( widgetID, tabID, page, includePagination ) {

		var pagination = ( includePagination ) ? 1 : 0;
		var ajaxData   = {
			'action':             'uhs_post_list_get_page',
			'nonce':              data.nonce,
			'widget_id':          widgetID,
			'tab_id':             tabID,
			'page':               page,
			'include_pagination': pagination,
		};

		// Make an Ajax request to get the new Posts and return a promise.
		return $.post( ajaxurl, ajaxData );
	};

	/**
	 * Update the post list HTML in a Post List widget.
	 *
	 * @param {object} $widget - A jQuery object containing the widget.
	 * @param {string} html    - The new HTML for the post list.
	 */
	var updatePostListWidgetPosts = function( $widget, html ) {

		// Update post list.
		$widget.find( '.uhs-post-list-widget-posts' ).replaceWith( html );

		// Grab fresh DOM references.
		var $postList      = $widget.find( '.uhs-post-list-widget-posts' );
		var $pagination    = $widget.find( '.uhs-post-list-widget-pagination' );
		var page           = $postList.attr( 'data-current-page' );
		var totalPages     = $postList.attr( 'data-total-pages' );
		var currentPostMin = $postList.attr( 'data-current-post-min' );
		var currentPostMax = $postList.attr( 'data-current-post-max' );

		// Update pagination.
		$pagination.find( '.uhs-post-list-widget-post-x-x' ).text( currentPostMin + ' - ' + currentPostMax );

		// Maybe show/hide next/previous links.
		if ( page === 1 ) {
			$pagination.find( '.uhs-post-list-widget-previous' ).removeClass( 'uhs-visible' );
			$pagination.find( '.uhs-post-list-widget-next' ).addClass( 'uhs-visible' );
		} else if ( page === totalPages ) {
			$pagination.find( '.uhs-post-list-widget-previous' ).addClass( 'uhs-visible' );
			$pagination.find( '.uhs-post-list-widget-next' ).removeClass( 'uhs-visible' );
		} else {
			$pagination.find( '.uhs-post-list-widget-previous' ).addClass( 'uhs-visible' );
			$pagination.find( '.uhs-post-list-widget-next' ).addClass( 'uhs-visible' );
		}
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

			var $widget     = $( this );
			var $pagination = $widget.find( '.uhs-rss-feed-widget-pagination' );
			var $prevPage   = $pagination.find( '.uhs-rss-feed-widget-previous' );
			var $nextPage   = $pagination.find( '.uhs-rss-feed-widget-next' );

			updateRssFeedWidgetPosts( $widget, 1 );

			// Handle clicks on the pagination controls.
			$prevPage.on( 'click', function() {
				updateRssFeedWidgetPosts( $widget, 2 );
			});
			$nextPage.on( 'click', function() {
				updateRssFeedWidgetPosts( $widget, 2 );
			});
		});
	};

	/**
	 * Update the Posts HTML in an RSS Feed widget.
	 *
	 * @param {object} $widget - A jQuery object containing the widget.
	 * @param {number} page    - The "page" to display, in the pagination sense.
	 */
	var updateRssFeedWidgetPosts = function( $widget, page ) {

		var $feedContent     = $widget.find( '.uhs-rss-feed-widget-feed-content' );
		var $feedContentWrap = $feedContent.find( '.uhs-feed-content-wrap' );
		var $pagination      = $widget.find( '.uhs-rss-feed-widget-pagination' );
		var feedURL          = $feedContent.attr( 'data-feed-url' );
		var $spinner         = $widget.find( '.uhs-spinner' );
		var limit            = 10;
		var offsetStart      = limit * page;

		$feedContentWrap.empty();
		$spinner.addClass( 'uhs-visible' );

		$feedContentWrap.rss( feedURL, {
			limit: limit,
			offsetStart: offsetStart,
			ssl: true,
			layoutTemplate: '{entries}',
			entryTemplate: '<div class="uhs-feed-item"><div class="uhs-feed-item-left"><h3 class="uhs-feed-item-title"><a href="{url}">{title}</a></h3></div><div class="uhs-feed-item-right"><div class="uhs-feed-item-date">{date}</div><div class="uhs-feed-item-author">{author}</div></div><div class="uhs-feed-item-content">{shortBodyPlain}...</div></div>',
			tokens: {},
			dateFormat: 'MMM Do, YYYY',
			error: function() {},
			success: function() {
				$feedContent.addClass( 'uhs-loaded' );
			},
			onData: function() {
				$spinner.removeClass( 'uhs-visible' );
			},
		});

		$pagination.find( '.uhs-rss-feed-widget-page-x' ).text( page );

		// Maybe show/hide next/previous links.
		if ( page === 1 ) {
			$pagination.find( '.uhs-rss-feed-widget-previous' ).removeClass( 'uhs-visible' );
			$pagination.find( '.uhs-rss-feed-widget-next' ).addClass( 'uhs-visible' );
		} else if ( page === totalPages ) {
			$pagination.find( '.uhs-rss-feed-widget-previous' ).addClass( 'uhs-visible' );
			$pagination.find( '.uhs-rss-feed-widget-next' ).removeClass( 'uhs-visible' );
		} else {
			$pagination.find( '.uhs-rss-feed-widget-previous' ).addClass( 'uhs-visible' );
			$pagination.find( '.uhs-rss-feed-widget-next' ).addClass( 'uhs-visible' );
		}
	};

	return {
		init: init,
	};

})( jQuery, uhsData );

// Start the party.
jQuery( document ).ready( function() {
	userHomeScreenWidgets.init();
});
