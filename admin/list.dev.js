/**
 *
 *
 * @since 1.0.0
 */

jQuery(document).ready( function($) {

	/**
	 * Show a popup box with the table's Shortcode
	 *
	 * @since 1.0.0
	 */
	$( '#tablepress-all-tables' ).on( 'click', '.shortcode a', function( /* event */ ) {
		prompt( tablepress_list.shortcode_popup, $(this).attr( 'title' ) );
		return false;
	} );

	/**
	 * Load a Thickbox with a table preview
	 *
	 * @since 1.0.0
	 */
	$( '#tablepress-all-tables' ).on( 'click', '.table-preview a', function( /* event */ ) {
		var width = $(window).width() - 120,
			height = $(window).height() - 120;
		if ( $( 'body.admin-bar' ).length )
			height -= 28;
		tb_show( $(this).text(), $(this).attr( 'href' ) + 'TB_iframe=true&height=' + height + '&width=' + width, false );
		return false;
	} );

} );