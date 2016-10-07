<script type="text/html" id="tmpl-uhs-field-select">
	<div class="uhs-field-select uhs-field">
		<h3 class="uhs-field-label">{{ data.label }}</h3>
		<div class="uhs-field-input-wrap">
			<select class="{{{ data.classes }}}" name="{{{ data.key }}}" data-placeholder="{{{ data.placeholder }}}">
				<# _.each( data.values, function( value, key, list ) { #>
				<option value="{{{ key }}}">{{ value }}</option>
				<# }); #>
			</select>
		</div>
	</div>
</script>