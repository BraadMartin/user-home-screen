<script type="text/html" id="tmpl-uhs-message">
	<div class="uhs-message">
		<# if ( data.label ) { #>
			<h1 class="uhs-modal-label">{{ data.label }}</h1>
		<# } #>
		<# if ( data.message ) { #>
			<p>{{ data.message }}</p>
		<# } #>
	</div>
</script>