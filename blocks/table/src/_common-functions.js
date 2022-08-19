/**
 * Common functions for the TablePress/table block.
 *
 * @package TablePress
 * @subpackage Blocks
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/**
 * Converts a set of named and numeric Shortcode attributes to a string.
 *
 * This function is similar to @wordpress/shortcode's `string()` function,
 * but only returns the attributes string and not a full Shortcode.
 *
 * @param {Object} named_attrs   The named Shortcode attributes.
 * @param {Array}  numeric_attrs The numeric Shortcode attributes. Optional. Default empty array.
 * @return {string} The attributes as a key=value string.
 */
export const shortcode_attrs_to_string = ( named_attrs, numeric_attrs = [] ) => {
	// Convert named attributes.
	let shortcode_attrs_string = Object.entries( named_attrs ).map( ( [ attribute, value ] ) => {
		let enclose = ''; // Don't enclose values by default.

		// Use " as delimiter if value contains whitespace or is empty.
		if ( /\s/.test( value ) || '' === value ) {
			enclose = '"';
		}

		// Use ' as delimiter if value contains ".
		if ( value.includes( '"' ) ) {
			enclose = '\'';
		}

		return `${ attribute }=${ enclose }${ value }${ enclose }`;
	} ).join( ' ' );

	// Convert numeric attributes.
	numeric_attrs.forEach( ( value ) => {
		if ( /\s/.test( value ) ) {
			shortcode_attrs_string += ' "' + value + '"';
		} else {
			shortcode_attrs_string += ' ' + value;
		}
	} );

	return shortcode_attrs_string;
};

/**
 * Determines whether a checkbox should be checked, based on the option's template value and the Configuration parameter value.
 *
 * @param {string}  shortcode_attr_value The option value from the Configuration parameter.
 * @param {boolean} template_value       The option value from the table template.
 * @return {boolean} Checkbox state.
 */
export const checked_state = function( shortcode_attr_value, template_value ) {
	if ( 'undefined' === typeof shortcode_attr_value ) {
		return template_value;
	} else { // eslint-disable-line no-else-return
		return ( 'true' === shortcode_attr_value.toLowerCase() );
	}
};
