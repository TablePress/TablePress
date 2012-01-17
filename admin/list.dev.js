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
	$( '#tablepress-all-tables' ).on( 'click', '.shortcode a', function( /* event */ ) {
		prompt( tablepress_list.shortcode_popup, $(this).attr( 'title' ) );
		return false;
	} );

} );