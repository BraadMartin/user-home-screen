<script type="text/html" id="tmpl-uhs-widget-edit">
	<div class="uhs-widget-edit">
		<h1 class="uhs-modal-label">{{ data.label }}</h1>
		<# print( data.typeSelect ); #>
		<form id="uhs-modal-widget-fields"></form>
		<div class="uhs-modal-bottom">
			<a id="uhs-save-widget-button" class="button button-primary">{{ data.addButton }}</a>
			<span id="uhs-save-widget-spinner" class="spinner"></span>
		</div>
	</div>
</script>