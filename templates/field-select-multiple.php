<script type="text/html" id="tmpl-uhs-field-select-multiple">
	<div class="uhs-field-select-multiple uhs-field">
		<h3 class="uhs-field-label">{{ data.label }}</h3>
		<div class="uhs-field-input-wrap">
			<select class="{{{ data.classes }}}" name="{{{ data.key }}}[]" data-placeholder="{{{ data.placeholder }}}" multiple>
				<# _.each( data.values, function( value, key, list ) { #>
				<option value="{{{ key }}}">{{ value }}</option>
				<# }); #>
			</select>
		</div>
	</div>
</script>