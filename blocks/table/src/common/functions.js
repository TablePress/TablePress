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
 * @param {Object} shortcodeAttrs The named and numeric Shortcode attributes.
 * @return {string} The attributes as a key=value string.
 */
export const shortcodeAttrsToString = ( shortcodeAttrs ) => {
	// Convert named attributes.
	let shortcodeAttrsString = Object.entries( shortcodeAttrs.named ).map( ( [ attribute, value ] ) => {
		let enclose = ''; // Don't enclose values by default.

		// Remove curly quotation marks around a value.
		value = value.replace( /“([^”]*)”/g, '$1' );

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
	shortcodeAttrs.numeric.forEach( ( value ) => {
		if ( /\s/.test( value ) ) {
			shortcodeAttrsString += ' "' + value + '"';
		} else {
			shortcodeAttrsString += ' ' + value;
		}
	} );

	return shortcodeAttrsString;
};
