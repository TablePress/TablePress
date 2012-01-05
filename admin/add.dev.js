jQuery(document).ready( function($) {

	/**
	 * remove/add title to value on focus/blur of text fields "Table Name" and "Table Description" on "Add new Table" screen
	 */
	$( '#tablepress_add-form-table' ).find( '#table_name, #table_description' )
	.focus( function() {
		if ( $(this).attr( 'defaultValue' ) == $(this).val() )
			$(this).val( '' );
	} )
	.blur( function() {
		if ( '' == $(this).val() )
			$(this).val( $(this).attr( 'defaultValue' ) );
	} );
	
} );