/**
 *
 *
 * @since 1.0.0
 */

jQuery(document).ready( function($) {

  	/**
	 * Show select box for table to replace only if needed
	 *
	 * @since 1.0.0
	 */
    $( '#row-import-add_replace' ).on( 'change', 'input', function() {
        $( '#tables-import-replace-table' ).prop( 'disabled', 'replace' != $(this).val() );
    } )
    .find( 'input:checked' ).change();

  	/**
	 * Show only the import source field that was selected with the radio button
	 *
	 * @since 1.0.0
	 */
    $( '#row-import-source' ).on( 'change', 'input', function() {
        $( '#row-import-source-file-upload, #row-import-source-url, #row-import-source-server, #row-import-source-form-field' ).hide();
        $( '#row-import-source-' + $(this).val() ).show();
    } )
    .find( 'input:checked' ).change();

} );