/**
 * User Home Screen JS.
 */

var userHomeScreen = ( function( $, data ) {

	/**
	 * Store key DOM references.
	 */
	var $wrap      = $();
	var $navTabs   = $();
	var $addWidget = $();

	/**
	 * Modal config options.
	 */
	var modalConfig = {
		variant: 'user-home-screen-modal',
	};

	/**
	 * Initialize.
	 */
	var init = function() {

		console.log( data );

		// Setup key DOM references.
		$wrap      = $( '#user-home-screen-wrap' );
		$navTabs   = $( 'h2.nav-tab-wrapper .nav-tab' );
		$addWidget = $( '.user-home-screen-add-widget' );

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

		// When the Add Widget button is clicked, open the Add Widget modal.
		$addWidget.on( 'click', function() {
			openAddWidgetModal();
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

		var $spinner = $( '#uhs-spinner' );
		var $save    = $( '#uhs-save-widget' );

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
			var widgetData = $widgetFields.serializeArray();

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

	return {
		init: init,
	};

})( jQuery, uhsData );

// Start the party.
jQuery( document ).ready( function() {
	userHomeScreen.init();
});