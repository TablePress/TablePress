/**
 * JavaScript code for the "Import" screen
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 1.0.0
 */

jQuery( document ).ready( function( $ ) {

	'use strict';

	/**
	 * File extension of the file that is imported.
	 */
	var extension = 'csv';

	/**
	 * Show select box for table to replace only if needed.
	 *
	 * @since 1.0.0
	 */
	$( '#row-import-type' ).on( 'change', 'input', function() {
		var import_type = $( this ).val();
		$( '#tables-import-existing-table' ).prop( 'disabled', ( ( 'replace' !== import_type && 'append' !== import_type ) || 'zip' === extension ) );
	} )
	.find( 'input:checked' ).change();

	/**
	 * Show only the import source field that was selected with the radio button.
	 *
	 * @since 1.0.0
	 */
	$( '#row-import-source' ).on( 'change', 'input', function() {
		$( '#row-import-source-file-upload, #row-import-source-url, #row-import-source-server, #row-import-source-form-field' ).hide();
		$( '#row-import-source-' + $(this).val() ).show();
	} )
	.find( 'input:checked' ).change();

	/**
	 * Select correct value in import format dropdown on file select.
	 *
	 * @since 1.0.0
	 */
	$( '#tables-import-file-upload, #tables-import-url, #tables-import-server' ).on( 'change', function( event ) {
		var path = $(this).val(),
			import_type = $( '#row-import-type' ).find( 'input:checked' ).val(),
			filename_start,
			extension_start,
			filename = path;

		// Default extension: CSV for file upload and server, HTML for URL.
		if ( 'tables-import-url' === event.target.id ) {
			extension = 'html';
		}
		// Determine filename from full path.
		filename_start = path.lastIndexOf( '\\' );
		if ( -1 !== filename_start ) { // Windows-based path
			filename = path.substr( filename_start + 1 );
		} else {
			filename_start = path.lastIndexOf( '/' );
			if ( -1 !== filename_start ) { // Windows-based path
				filename = path.substr( filename_start + 1 );
			}
		}
		// Determine extension from filename.
		extension_start = filename.lastIndexOf( '.' );
		if ( -1 !== extension_start ) {
			extension = filename.substr( extension_start + 1 ).toLowerCase();
		}

		// Allow .htm for HTML as well.
		if ( 'htm' === extension ) {
			extension = 'html';
		}

		$( '#tables-import-existing-table' ).prop( 'disabled', ( ( 'replace' !== import_type && 'append' !== import_type ) || 'zip' === extension ) );

		// Don't change the format for ZIP archives.
		if ( 'zip' === extension ) {
			return;
		}

		$( '#tables-import-format' ).val( extension );
	} );

	/**
	 * Check, whether inputs are valid
	 *
	 * @since 1.0.0
	 */
	$( '#tablepress-page' ).find( 'form' ).on( 'submit.tablepress', function( /* event */ ) {
		var import_source = $( '#row-import-source' ).find( 'input:checked' ).val(),
			selected_import_source_field = $( '#tables-import-' + import_source ).get(0),
			valid_form = true,
			import_type = $( '#row-import-type' ).find( 'input:checked' ).val();

		// Te value of the selected import source field must be set/changed from the default.
		if ( selected_import_source_field.defaultValue === selected_import_source_field.value ) {
			$( selected_import_source_field )
				.addClass( 'invalid' )
				.one( 'change', function() { $(this).removeClass( 'invalid' ); } )
				.focus().select();
			valid_form = false;
		}

		// If replace or append is selected, a table must be selected - except for ZIP files.
		if ( ( 'replace' === import_type || 'append' === import_type ) && 'zip' !== extension ) {
			if ( '' === $( '#tables-import-existing-table' ).val() ) {
				$( '#row-import-type' )
					.one( 'change', 'input', function() { $( '#tables-import-existing-table' ).removeClass( 'invalid' ); } );
				$( '#tables-import-existing-table' )
					.addClass( 'invalid' )
					.one( 'change', function() { $(this).removeClass( 'invalid' ); } )
					.focus().select();
				valid_form = false;
			}
		}

		// Don't submit the form if it's not valid.
		if ( ! valid_form ) {
			return false;
		}
	} );

} );
