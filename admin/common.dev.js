jQuery(document).ready( function($) {

	// tablepress_common object will contain all localized strings and options that influence JavaScript

	/*
	 * Enable toggle/order functionality for post meta boxes
	 */
	postboxes.add_postbox_toggles( pagenow );

	/*
	 * AJAX functionality
	 */

	/*
	 *
	 */
	function tablepress_ajax_link_success( link, action, result ) {
		if ( '1' != result )
			return false;

		switch ( action ) {
			case 'tablepress_hide_message':
				$( link ).closest( 'div' ).remove();
				break;
			default:
				return false;
		}
	}

	/*
	 *
	 */
	$( '#tablepress-page' ).on( 'click', '.ajax-link', function() {
		var link = this,
			link_action = link.className.replace(/^.*ajax-link /, '');
		$.post( ajaxurl,
				{
					action: link_action,
					item: link.id.replace( new RegExp( '^.*' + link_action + '-' ), '' ),
					_ajax_nonce : link.href.replace(/^.*wpnonce=/, '')
				},
				function( result ) {
					tablepress_ajax_link_success( link, link_action, result );
				}
		);
		return false;
	} );

} );