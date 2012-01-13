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
			num_columns = $( '#table-columns' ).val();

		if ( ! ( /^[1-9][0-9]{0,4}$/ ).test( num_rows ) ) {
			alert( tablepress_strings.number_rows_invalid );
			$( '#table-rows' ).focus().select();
			return false;
		}

		if ( ! ( /^[1-9][0-9]{0,4}$/ ).test( num_columns ) ) {
			alert( tablepress_strings.number_columns_invalid );
			$( '#table-columns' ).focus().select();
			return false;
		}
	} );

} );