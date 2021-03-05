/**
 * JavaScript code for the "Add New" screen
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 1.0.0
 */

/* global validateForm */

jQuery( function( $ ) {

	'use strict';

	/**
	 * Check, whether entered numbers for rows and columns are valid
	 *
	 * @since 1.0.0
	 */
	$( '#tablepress-page' ).find( 'form' ).on( 'submit', function( /* event */ ) {
		var valid_form = true;

		// WordPress validation function, checks if required fields (.form-required) are non-empty
		if ( ! validateForm( $(this) ) ) {
			valid_form = false;
		}

		// validate numerical values (.form-field-numbers-only): only 1 < x < 9...9 (up to maxlength) are allowed
		$( '#tablepress-page' ).find( '.form-field-numbers-only' ).find( 'input' ).each( function() {
			var $field = $(this),
				maxlength = parseInt( $field.attr( 'maxlength' ), 10 ),
				regexp_number;

			if ( ! isNaN( maxlength ) ) {
				maxlength += -1; // first number is handled already in RegExp
			} else {
				maxlength = '';
			}

			regexp_number = new RegExp( '^[1-9][0-9]{0,' + maxlength + '}$' );
			if ( regexp_number.test( $field.val() ) ) {
				return; // field is valid
			}

			$field
				.one( 'change', function() { $(this).closest( '.form-invalid' ).removeClass( 'form-invalid' ); } )
				.trigger( 'focus' ).trigger( 'select' )
				.closest( '.form-field' ).addClass( 'form-invalid' );
			valid_form = false;
		} );

		// Don't submit the form if it's not valid.
		if ( ! valid_form ) {
			return false;
		}
	} );

} );
