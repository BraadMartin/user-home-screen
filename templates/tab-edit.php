<script type="text/html" id="tmpl-uhs-tab-edit">
	<div class="uhs-tab-edit">
		<h1 class="uhs-modal-label">{{ data.label }}</h1>
		<form id="uhs-modal-tab-fields">
			<# print( data.nameTheTab ); #>
		</form>
		<div class="uhs-modal-bottom">
			<a id="uhs-save-tab-button" class="button button-primary">{{ data.addButton }}</a>
			<span id="uhs-save-tab-spinner" class="spinner"></span>
		</div>
	</div>
</script>