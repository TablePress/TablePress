/**
 *
 *
 * @since 1.0.0
 */

jQuery(document).ready( function($) {

	// tablepress_common object will contain all localized strings and options that influence JavaScript

	/**
	 * Enable toggle/order functionality for post meta boxes
	 * For TablePress, pagenow has the form "tablepress_{$action}"
	 *
	 * @since 1.0.0
	 */
	postboxes.add_postbox_toggles( pagenow );

	/**
	 * AJAX functionality
	 */

	/**
	 * Process links with a class "ajax-link" with AJAX
	 *
	 * @since 1.0.0
	 */
	$( '#tablepress-page' ).on( 'click', '.ajax-link', function() {
		var link = this,
			action = link.className.replace(/^.*ajax-link /, '');
		$.get(
			ajaxurl,
			link.href.split('?')['1'], /* query string of the link */
			function( result ) {
				if ( '1' != result )
					return;
		
				switch ( action ) {
					case 'hide_message':
						$( link ).closest( 'div' ).remove();
						break;
				}
			}
		);
		return false;
	} );

} );