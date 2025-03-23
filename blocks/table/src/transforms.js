/**
 * JavaScript code for the TablePress table block in the block editor.
 *
 * @package TablePress
 * @subpackage Blocks
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/* globals tp */

/**
 * WordPress dependencies.
 */
import { createBlock } from '@wordpress/blocks';
import shortcode from '@wordpress/shortcode';

/**
 * Get the block name from the block.json.
 */
import block from '../block.json';

/**
 * Internal dependencies.
 */
import { shortcodeAttrsToString } from './common/functions';

/**
 * Converts a set of parsed Shortcode attributes to a parameters string.
 *
 * @param {Object} shortcodeAttrs The Shortcode attributes.
 * @return {string} The parameters string.
 */
const convertShortcodeAttrsToParametersString = ( shortcodeAttrs ) => {
	// Remove the `id` attribute from the Shortcode parameters, as it is handled separately.
	delete shortcodeAttrs.named.id;

	let parameters = shortcodeAttrsToString( shortcodeAttrs );

	// Replace curly quotation marks around a value with normal ones.
	parameters = parameters.replace( /=“([^”]*)”/g, '="$1"' );

	// Decode HTML entities like `&amp;`, `&lt;`, `&gt;`, `&lsqb;`, and `&rsqb;` that were encoded in the Shortcode.
	parameters = parameters.replaceAll( '&amp;', '&' ).replaceAll( '&lt;', '<' ).replaceAll( '&gt;', '>' ).replaceAll( '&lsqb;', '[' ).replaceAll( '&rsqb;', ']' );

	return parameters;
};

/**
 * Converts a textual Shortcode to a TablePress table block.
 *
 * @param {string} content The Shortcode as a text string.
 * @return {Object} TablePress table block.
 */
const convertShortcodeTextToBlock = ( content ) => {
	let shortcodeAttrs = shortcode.next( tp.table.shortcode, content ).shortcode.attrs;
	shortcodeAttrs = { named: { ...shortcodeAttrs.named }, numeric: [ ...shortcodeAttrs.numeric ] }; // Use object destructuring to get a clone of the object.
	const id = shortcodeAttrs.named.id;
	const parameters = convertShortcodeAttrsToParametersString( shortcodeAttrs );
	return createBlock( block.name, { id, parameters } );
};

const transforms = {
	from: [
		// Detect table Shortcodes that are pasted into the block editor.
		{
			type: 'shortcode',
			tag: tp.table.shortcode,
			attributes: {
				id: {
					type: 'string',
					shortcode: ( { named: { id = '' } } ) => {
						return id;
					},
				},
				parameters: {
					type: 'string',
					shortcode: ( shortcodeAttrs ) => {
						shortcodeAttrs = { named: { ...shortcodeAttrs.named }, numeric: [ ...shortcodeAttrs.numeric ] }; // Use object destructuring to get a clone of the object.
						return convertShortcodeAttrsToParametersString( shortcodeAttrs );
					},
				},
			},
		},

		// Detect table Shortcodes that are typed into the block editor.
		{
			type: 'enter',
			regExp: shortcode.regexp( tp.table.shortcode ),
			transform: ( { content } ) => convertShortcodeTextToBlock( content ),
		},

		// Add conversion option from "Shortcode" to "TablePress table" block.
		{
			type: 'block',
			blocks: [ 'core/shortcode' ],
			transform: ( { text: content } ) => convertShortcodeTextToBlock( content ),
			isMatch: ( { text } ) => {
				return ( undefined !== shortcode.next( tp.table.shortcode, text ) );
			},
			isMultiBlock: false,
		},
	],

	to: [
		// Add conversion option from "TablePress table" to "Shortcode" block.
		{
			type: 'block',
			blocks: [ 'core/shortcode' ],
			transform: ( { id, parameters } ) => {
				parameters = parameters.trim();
				if ( '' !== parameters ) {
					parameters += ' ';
					// Encode characters that cause problems in Shortcodes, e.g. due to sanitization, like `&`, `<`, `>`, `[`, and `]` to HTML entities.
					parameters = parameters.replaceAll( '&', '&amp;' ).replaceAll( '<', '&lt;' ).replaceAll( '>', '&gt;' ).replaceAll( '[', '&lsqb;' ).replaceAll( ']', '&rsqb;' );
				}
				const text = `[${ tp.table.shortcode } id=${ id } ${ parameters }/]`;
				return createBlock( 'core/shortcode', { text } );
			},
		},
	],
};

export default transforms;
