	/**
 * User Home Screen JS.
 */

var userHomeScreen = ( function( $, data ) {

	/**
	 * Store key DOM references.
	 */
	var $wrap             = $();
	var $navTabs          = $();
	var $tabContent       = $();
	var $addWidget        = $();
	var $removeWidget     = $();
	var $toggleWidgetInfo = $();

	/**
	 * Modal config options.
	 */
	var modalConfig = {
		variant: 'uhs-modal',
	};

	/**
	 * Initialize.
	 */
	var init = function() {

		// Setup key DOM references.
		$wrap             = $( '#uhs-wrap' );
		$navTabs          = $( 'h2.nav-tab-wrapper .nav-tab' );
		$tabContent       = $( '.uhs-tab-content-wrap' );
		$addWidget        = $( '.uhs-add-widget' );
		$removeWidget     = $( '.uhs-remove-widget' );
		$toggleWidgetInfo = $( '.uhs-toggle-widget-info' );

		setupEvents();

		showInitialActiveTab();
	};

	/**
	 * Show initial active tab, either from a query param or from an HTML attribute.
	 */
	var showInitialActiveTab = function() {
		var tabID = $navTabs.filter( '.nav-tab-active' ).attr( 'data-tab-id' );

		if ( tabID === 'add-new' ) {
			$addWidget.attr( 'disabled', true );
		} else {
			$tabContent.filter( '[data-for-tab="' + tabID + '"]' ).addClass( 'uhs-visible' );
		}
	};

	/**
	 * Setup events.
	 */
	var setupEvents = function() {

		// Tab click.
		$navTabs.on( 'click', function() {
			handleTabClick( this );
		});

		// Remove tab.
		$navTabs.find( '.uhs-remove-tab' ).on( 'click', function( e ) {
			e.preventDefault();
			e.stopPropagation();

			var tabID = $( this ).closest( '.nav-tab' ).attr( 'data-tab-id' );

			openRemoveTabModal( tabID );
		});

		// Add widget.
		$addWidget.on( 'click', function() {

			// If the button is disabled, show a message, otherwise open the modal.
			if ( $( this ).attr( 'disabled' ) ) {
				if ( ! $( '.uhs-no-tabs-notice' ).length ) {
					$( this ).after(
						$( '<div class="uhs-no-tabs-notice" />' ).text( data.labels.no_tabs_notice )
					);
				}
			} else {
				openAddWidgetModal();
			}
		});

		// Remove widget.
		$removeWidget.on( 'click', function() {
			var $clicked = $( this );
			var $widget  = $clicked.closest( '.uhs-widget' );
			var $tab     = $widget.closest( '.uhs-tab-content-wrap' );

			openRemoveWidgetModal( $widget, $tab );
		});

		$toggleWidgetInfo.on( 'click', function() {
			var $clicked = $( this );
			var $widget  = $clicked.closest( '.uhs-widget' );

			toggleWidgetInfo( $widget );
		});

		var $widgetGrids = $( '.uhs-widget-grid' );

		// Make widgets sortable.
		$widgetGrids.sortable({
			placeholder: 'uhs-ui-state-highlight',
			handle: '.uhs-widget-title.hndle',
			revert: 200,
			tolerance: 'pointer',
		});
		$widgetGrids.disableSelection();

		// Save the updated widget order after sorting.
		$widgetGrids.on( 'sortupdate', function( event, ui ) {

			// Grab the right widget grid.
			var $widgetGrid = ui.item.closest( '.uhs-widget-grid' );
			var tabID       = $widgetGrid.closest( '.uhs-tab-content-wrap' ).attr( 'data-for-tab' );

			updateWidgetsOrder( $widgetGrid, tabID );
		});
	};

	/**
	 * Handle the click on a tab.
	 *
	 * @param {object} tab - A DOM reference to the clicked tab.
	 */
	var handleTabClick = function( tab ) {

		var $tab = $( tab );

		// If the clicked tab was already active and wasn't the add-new tab, do nothing.
		if ( $tab.hasClass( 'nav-tab-active' ) && 'add-new' !== $tab.attr( 'data-tab-id' ) ) {
			return;
		}

		var tabID = $tab.attr( 'data-tab-id' );

		// If the clicked tab was our add-new tab, trigger the add-tab modal,
		// otherwise toggle to the tab as usual.
		if ( tabID === 'add-new' ) {
			openAddTabModal();
		} else {
			$navTabs.removeClass( 'nav-tab-active' );
			$tab.addClass( 'nav-tab-active' );
			$wrap.attr( 'data-active-tab', tabID );
			$tabContent.removeClass( 'uhs-visible' );
			$tabContent.filter( '[data-for-tab="' + tabID + '"]' ).addClass( 'uhs-visible' );

			// Update the URL.
			setQueryParam( 'tab', tabID );
		}
	};

	/**
	 * Open the Add Tab modal.
	 */
	var openAddTabModal = function() {

		var editTab   = wp.template( 'uhs-tab-edit' );
		var fieldText = wp.template( 'uhs-field-text' );

		var addTabData = {
			label:   data.labels.tab_name,
			classes: 'uhs-add-tab-name',
			key:     'uhs-tab-name',
			value:   '',
		};

		$.featherlight(
			editTab({
				label:      data.labels.add_tab,
				nameTheTab: fieldText( addTabData ),
				addButton:  data.labels.add_tab,
			}),
			modalConfig
		);

		var $tabNameInput = $( '.uhs-add-tab-name' );

		// Bring focus to the tab name input.
		$tabNameInput.focus();

		// Prevent the modal form from being submitted.
		$( '#uhs-modal-tab-fields' ).on( 'submit', function( e ) {
			e.preventDefault;
		});

		// Save the form data when enter is pressed while focused on the input.
		$tabNameInput.on( 'keydown', function( event ) {
			if ( event.keyCode === 13 ) {
				event.preventDefault();

				var $modal     = $( '.featherlight-content .uhs-tab-edit' );
				var $tabFields = $( '#uhs-modal-tab-fields' );
				var tabData    = $tabFields.serialize();

				ajaxAddTab( $modal, tabData );
			}
		});

		// Save the form data when the save button is clicked.
		$( '#uhs-save-tab-button' ).on( 'click', function() {
			var $modal     = $( '.featherlight-content .uhs-tab-edit' );
			var $tabFields = $( '#uhs-modal-tab-fields' );
			var tabData    = $tabFields.serialize();

			ajaxAddTab( $modal, tabData );
		});
	};

	/**
	 * Send an Ajax request to add a tab.
	 *
	 * @param {object} $modal  - A jQuery object containing the Add Tab modal.
	 * @param {object} tabData - The tab data to send in the Ajax request.
	 */
	var ajaxAddTab = function( $modal, tabData ) {

		var $spinner = $modal.find( '#uhs-save-tab-spinner' );
		var ajaxData = {
			'action':   'uhs_add_tab',
			'nonce':    data.nonce,
			'tab_data': tabData,
		};

		$spinner.css( 'visibility', 'visible' );

		var request = $.post( ajaxurl, ajaxData );

		request.done( function( response ) {
			$modal.empty();
			$modal.append( '<h1 class="uhs-thank-you">Thank you!</h1>' );

			setTimeout( function() {
				location.reload();
			}, 400 );
		});

		request.fail( function() {
			$spinner.css( 'visibility', 'none' );
		});
	};

	/**
	 * Open the Remove Tab modal.
	 *
	 * @param {string} tabID - The ID for the tab to remove.
	 */
	var openRemoveTabModal = function( tabID ) {

		var confirm = wp.template( 'uhs-confirm' );

		$.featherlight(
			confirm({
				label:       data.labels.remove_tab,
				confirmText: data.labels.remove_tab_confirm,
				buttonText:  data.labels.remove_tab,
			}),
			modalConfig
		);

		var $modal = $( '.featherlight-content .uhs-confirm' );
		var $save  = $modal.find( '.uhs-confirm-button' );

		$save.on( 'click', function() {
			var $spinner = $modal.find( '.uhs-confirm-spinner' );
			var ajaxData = {
				'action': 'uhs_remove_tab',
				'nonce':  data.nonce,
				'tab_id': tabID,
			};

			$spinner.addClass( 'uhs-visible' );

			var request = $.post( ajaxurl, ajaxData );

			request.done( function( response ) {
				$modal.empty();
				$modal.append( '<h1 class="uhs-thank-you">Thank you!</h1>' );

				setTimeout( function() {
					location.reload();
				}, 400 );
			});

			request.fail( function() {
				$spinner.css( 'visibility', 'none' );
			});
		});
	};

	/**
	 * Open the Add Widget modal.
	 */
	var openAddWidgetModal = function() {

		var editWidget  = wp.template( 'uhs-widget-edit' );
		var fieldSelect = wp.template( 'uhs-field-select' );

		// Set up the object containing the widget type data for the type select.
		var widgetTypes = {
			placeholder: data.labels.select_default,
		};
		_.each( data.widget_types, function( value, key, list ) {
			widgetTypes[ key ] = value.label;
		});
		var typeSelect = {
			label:   data.labels.select_widget_type,
			name:    'widget_type',
			classes: 'uhs-widget-type-select',
			values:  widgetTypes,
		};

		$.featherlight(
			editWidget({
				label:      data.labels.add_widget,
				typeSelect: fieldSelect( typeSelect ),
				addButton:  data.labels.add_widget,
			}),
			modalConfig
		);

		var $spinner = $( '#uhs-save-widget-spinner' );
		var $save    = $( '#uhs-save-widget-button' );

		// Prevent the modal form from being submitted.
		$( '#uhs-modal-widget-fields' ).on( 'submit', function( e ) {
			e.preventDefault;
		});

		// Update the displayed fields when the type select is changed.
		$( '.uhs-widget-type-select' ).on( 'change', function( e ) {
			var type          = $( this ).val();
			var $widgetFields = $( '#uhs-modal-widget-fields' );

			$widgetFields.empty();

			// If the placeholder option was selected, bail.
			if ( type === 'placeholder' ) {
				$save.removeClass( 'uhs-visible' );
				return;
			}

			var fieldsHTML = getWidgetFieldsHTML( type );

			$widgetFields.append( fieldsHTML );

			// Initialize Select2 on the selects.
			$widgetFields.find( 'select' ).each( function() {
				var $this = $( this );

				if ( $this.hasClass( 'uhs-multi-select' ) ) {
					$this.select2({
						multiple: true,
						placeholder: $this.attr( 'data-placeholder' ),
					});
					$this.val( '' );
				} else {
					$this.select2({
						minimumResultsForSearch: Infinity,
					});
				}

				$this.trigger( 'change' );
			});

			// Add a data attribute to the form indicating the widget type.
			$widgetFields.attr( 'data-widget-type', type );

			$save.addClass( 'uhs-visible' );
		});

		// Save the form data when the save button is clicked.
		$save.on( 'click', function() {
			var $modal        = $( '.featherlight-content .uhs-widget-edit' );
			var $widgetFields = $modal.find( '#uhs-modal-widget-fields' );
			var widgetData    = $widgetFields.serialize();
			var tabID         = $navTabs.filter( '.nav-tab-active' ).attr( 'data-tab-id' );
			var ajaxData      = {
				'action':      'uhs_add_widget',
				'nonce':       data.nonce,
				'widget_type': $widgetFields.attr( 'data-widget-type' ),
				'widget_data': widgetData,
				'tab_id':      tabID,
			};

			$spinner.css( 'visibility', 'visible' );

			var request = $.post( ajaxurl, ajaxData );

			request.done( function( response ) {
				$modal.empty();
				$modal.append( '<h1 class="uhs-thank-you">Thank you!</h1>' );

				setTimeout( function() {
					location.reload();
				}, 400 );
			});

			request.fail( function() {
				$spinner.css( 'visibility', 'none' );
			});
		});
	};

	/**
	 * Open the Remove Widget modal.
	 *
	 * @param {object} $widget - A jQuery object referencing the widget to remove.
	 * @param {object} $tab    - A jQuery object referencing the tab.
	 */
	var openRemoveWidgetModal = function( $widget, $tab ) {

		var confirm = wp.template( 'uhs-confirm' );

		$.featherlight(
			confirm({
				label:       data.labels.remove_widget,
				confirmText: data.labels.remove_widget_confirm,
				buttonText:  data.labels.remove_widget,
			}),
			modalConfig
		);

		var $modal   = $( '.featherlight-content .uhs-confirm' );
		var $save    = $modal.find( '.uhs-confirm-button' );
		var tabID    = $tab.attr( 'data-for-tab' );
		var widgetID = $widget.attr( 'data-widget-id' );

		$save.on( 'click', function() {
			var $spinner = $modal.find( '.uhs-confirm-spinner' );
			var ajaxData = {
				'action':    'uhs_remove_widget',
				'nonce':     data.nonce,
				'tab_id':    tabID,
				'widget_id': widgetID,
			};

			$spinner.addClass( 'uhs-visible' );

			var request = $.post( ajaxurl, ajaxData );

			request.done( function( response ) {
				$modal.empty();
				$modal.append( '<h1 class="uhs-thank-you">Thank you!</h1>' );

				setTimeout( function() {
					location.reload();
				}, 400 );
			});

			request.fail( function() {
				$spinner.css( 'visibility', 'none' );
			});
		});
	};

	/**
	 * Toggle the widget info panel.
	 *
	 * @param {object} $widget - A jQuery object referencing the widget.
	 */
	var toggleWidgetInfo = function( $widget ) {

		var $widgetInfo = $widget.find( '.uhs-widget-info' );

		$widgetInfo.toggleClass( 'uhs-visible' );
	};

	/**
	 * Given a widget type, return the HTML for the widget's fields.
	 *
	 * @param {string} type - The widget type to get fields for.
	 *
	 * @return {string}     - The HTML for the widget's fields.
	 */
	var getWidgetFieldsHTML = function( type ) {

		var widgetData = data.widget_types[ type ];
		var fields     = '';

		// Loop over each field and render it.
		_.each( widgetData.fields, function( field ) {
			var type     = field.type;
			var template = wp.template( 'uhs-field-' + type );

			// Add a unique and shared class to each field.
			field.classes = 'uhs-' + field.key.replace( '_', '-' ) + '-' + type;
			field.classes += ' uhs-input';

			fields += template( field );
		});

		return fields;
	};

	/**
	 * Save the updated widget order after sorting.
	 *
	 * @param {object} $widgetGrid - A jQuery object containing the updated widget grid.
	 * @param {string} tabID       - The ID of the tab the widget grid is on.
	 */
	var updateWidgetsOrder = function( $widgetGrid, tabID ) {

		var widgetOrder = [];

		$widgetGrid.find( '.uhs-widget' ).each( function() {
			widgetOrder.push( $( this ).attr( 'data-widget-id' ) );
		});

		var ajaxData = {
			'action':      'uhs_update_widgets_order',
			'nonce':        data.nonce,
			'tab_id':       tabID,
			'widget_order': widgetOrder,
		};

		var request = $.post( ajaxurl, ajaxData );

		request.done( function( response ) {
			console.log( response );
		});

		request.fail( function() {
		});
	};

	/**
	 * Insert or change a query param.
	 *
	 * See: http://stackoverflow.com/questions/486896/adding-a-parameter-to-the-url-with-javascript
	 *
	 * @param {string} key   - The query param key.
	 * @param {string} value - The query param value.
	 */
	var setQueryParam = function( key, value ) {

		key   = encodeURI( key );
		value = encodeURI( value );

		var kvp = document.location.search.substr( 1 ).split( '&' );
		var i   = kvp.length;
		var x;

		while ( i-- ) {
			x = kvp[ i ].split( '=' );

			if ( x[0] == key ) {
				x[1]     = value;
				kvp[ i ] = x.join( '=' );
				break;
			}
		}

		if ( i < 0 ) {
			kvp[ kvp.length ] = [ key, value ].join( '=' );
		}

		if ( history.pushState ) {
			var newURL = window.location.protocol + "//" + window.location.host + window.location.pathname + '?' + kvp.join( '&' );
			window.history.pushState({
				path: newURL,
			}, '', newURL );
		}
	};

	return {
		init: init,
	};

})( jQuery, uhsData );

// Start the party.
jQuery( document ).ready( function() {
	userHomeScreen.init();
});
