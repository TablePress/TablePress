/**
 *
 *
 * @since 1.0.0
 */

jQuery(document).ready( function($) {

	/**
	 * Check, whether entered numbers for rows and columns are valid
	 *
	 * @since 1.0.0
	 */
	$( '#tablepress-page' ).on( 'submit', 'form', function( /* event */ ) {
		var num_rows = $( '#table-rows' ).val(),
			num_columns = $( '#table-columns' ).val(),
			valid_form = true;

		// remove default values from required placeholders, if no value was entered
		$( '#tablepress-page' ).find( '.form-required' ).find( '.placeholder' ).each( function() {
			if ( this.value == this.defaultValue ) {
				this.value = '';
				$(this).removeClass( 'placeholder-active' );
			}
		} );

		// WordPress validation function, checks if required fields (.form-required) are non-empty
		if ( ! validateForm( $(this) ) )
			valid_form = false;

		// custom validation functions
		if ( ! ( /^[1-9][0-9]{0,4}$/ ).test( num_rows ) ) {
			$( '#table-rows' )
			.one( 'change', function() { $(this).closest( '.form-invalid' ).removeClass( 'form-invalid' ); } )
			.focus().select()
			.closest( '.form-field' ).addClass( 'form-invalid' );
			valid_form = false;
		}
		if ( ! ( /^[1-9][0-9]{0,4}$/ ).test( num_columns ) ) {
			$( '#table-columns' )
			.one( 'change', function() { $(this).closest( '.form-invalid' ).removeClass( 'form-invalid' ); } )
			.focus().select()
			.closest( '.form-field' ).addClass( 'form-invalid' );
			valid_form = false;
		}

		if ( ! valid_form )
			return false;
		// at this point, the form is valid and will be submitted

		// remove the default values of optional fields, as we don't want to save those
		$( '#tablepress-page' ).find( '.placeholder' ).each( function() {
			if ( this.value == this.defaultValue )
				this.value = '';
		} );
	} );

} );