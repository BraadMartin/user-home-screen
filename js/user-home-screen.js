/**
 * User Home Screen JS.
 */

var userHomeScreen = ( function( $, data ) {

	/**
	 * Store key DOM references.
	 */
	var $wrap         = $();
	var $navTabs      = $();
	var $tabContent   = $();
	var $addWidget    = $();
	var $removeWidget = $();

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

		console.log( data );

		// Setup key DOM references.
		$wrap         = $( '#uhs-wrap' );
		$navTabs      = $( 'h2.nav-tab-wrapper .nav-tab' );
		$tabContent   = $( '.uhs-tab-content-wrap' );
		$addWidget    = $( '.uhs-add-widget' );
		$removeWidget = $( '.uhs-remove-widget' );

		// Setup events.
		setupEvents();

		// Show initial active tab.
		showInitialActiveTab();
	};

	/**
	 * Show initial active tab.
	 */
	var showInitialActiveTab = function() {
		var tabID = $navTabs.filter( '.nav-tab-active' ).attr( 'data-tab-id' );
		$tabContent.filter( '[data-for-tab="' + tabID + '"]' ).addClass( 'uhs-visible' );
	}

	/**
	 * Setup events.
	 */
	var setupEvents = function() {

		// When a nav tab is clicked, toggle classes.
		$navTabs.on( 'click', function() {
			handleTabClick( this );
		});

		// When the Add Widget button is clicked, open the Add Widget modal.
		$addWidget.on( 'click', function() {
			openAddWidgetModal();
		});

		// When the Remove Widget button is clicked, remove the widget.
		$removeWidget.on( 'click', function() {
			
		});
	};

	/**
	 * Handle the click on a tab.
	 *
	 * @param {object} tab - A DOM reference to the clicked tab.
	 */
	var handleTabClick = function( tab ) {

		var $tab = $( tab );

		// If the clicked tab was already active, do nothing.
		if ( $tab.hasClass( 'nav-tab-active' ) ) {
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
		}
	}

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

			// Clear the current fields.
			$widgetFields.empty();

			// If the placeholder option was selected, bail.
			if ( type === 'placeholder' ) {
				$save.removeClass( 'uhs-visible' );
				return;
			}

			// Grab the HTML for the selected widget's fields.
			var fieldsHTML = getWidgetFieldsHTML( type );

			// Inject the right fields for the selected widget type.
			$widgetFields.append( fieldsHTML );

			// Initialize Select2 on the selects.
			$widgetFields.find( 'select' ).each( function() {
				var $this = $( this );
				$this.select2({
					multiple: true,
					placeholder: $this.attr( 'data-placeholder' ),
				});
				$this.val( '' );
				$this.trigger( 'change' );
			});

			// Add a data attribute to the form indicating the widget type.
			$widgetFields.attr( 'data-widget-type', type );

			// Make the Save Widget button visible.
			$save.addClass( 'uhs-visible' );
		});

		// Save the form data when the save button is clicked.
		$save.on( 'click', function() {

			// Grab the modal and form.
			var $modal        = $( '.featherlight-content .uhs-widget-edit' );
			var $widgetFields = $( '#uhs-modal-widget-fields' );

			// Grab data from the form.
			var widgetData = $widgetFields.serialize();

			// Prepare data to save via ajax.
			var ajaxData = {
				'action'     : 'uhs_add_widget',
				'nonce'      : data.nonce,
				'widget_type': $widgetFields.attr( 'data-widget-type' ),
				'widget_data': widgetData,
			};

			$spinner.css( 'visibility', 'visible' );

			var request = $.post( ajaxurl, ajaxData );

			request.done( function( response ) {
				$modal.empty();
				$modal.append( '<h1>Thank you!</h1>' );

				setTimeout( function() {
					location.reload();
				}, 1200 );

				console.log( response );
			});

			request.fail( function( response ) {
				$spinner.css( 'visibility', 'none' );
				console.log( 'Something went wrong when trying to save a widget.' );
			});
		});
	};

	/**
	 * Given a widget type, return the HTML for the widget's fields.
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

		var $spinner = $( '#uhs-save-tab-spinner' );
		var $save    = $( '#uhs-save-tab-button' );

		// Prevent the modal form from being submitted.
		$( '#uhs-modal-tab-fields' ).on( 'submit', function( e ) {
			e.preventDefault;
		});

		// Save the form data when the save button is clicked.
		$save.on( 'click', function() {

			// Grab the modal and form.
			var $modal     = $( '.featherlight-content .uhs-tab-edit' );
			var $tabFields = $( '#uhs-modal-tab-fields' );

			// Grab data from the form.
			var tabData = $tabFields.serialize();

			// Prepare data to save via ajax.
			var ajaxData = {
				'action'  : 'uhs_add_tab',
				'nonce'   : data.nonce,
				'tab_data': tabData,
			};

			$spinner.css( 'visibility', 'visible' );

			var request = $.post( ajaxurl, ajaxData );

			request.done( function( response ) {
				$modal.empty();
				$modal.append( '<h1>Thank you!</h1>' );

				setTimeout( function() {
					location.reload();
				}, 1200 );

				console.log( response );
			});

			request.fail( function( response ) {
				$spinner.css( 'visibility', 'none' );
				console.log( 'Something went wrong when trying to add a tab.' );
			});
		});
	};

	return {
		init: init,
	};

})( jQuery, uhsData );

// Start the party.
jQuery( document ).ready( function() {
	userHomeScreen.init();
});
