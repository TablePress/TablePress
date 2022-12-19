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
import { shortcode_attrs_to_string } from './_common-functions';

/**
 * Converts a textual Shortcode to a TablePress table block.
 *
 * @param {string} content The Shortcode as a text string.
 * @return {Object} TablePress table block.
 */
const convertShortcodeTextToBlock = function( content ) {
	let shortcodeAttrs = shortcode.next( tp.table.shortcode, content ).shortcode.attrs;
	shortcodeAttrs = { named: { ...shortcodeAttrs.named }, numeric: [ ...shortcodeAttrs.numeric ] }; // Use object destructuring to get a clone of the object.
	const id = shortcodeAttrs.named.id;
	delete shortcodeAttrs.named.id;
	let parameters = shortcode_attrs_to_string( shortcodeAttrs );
	parameters = parameters.replace( /=“([^”]*)”/g, '="$1"' ); // Replace curly quotation marks around a value with normal ones.
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
						delete shortcodeAttrs.named.id;
						return shortcode_attrs_to_string( shortcodeAttrs );
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
				}
				const text = `[${ tp.table.shortcode } id=${ id } ${ parameters }/]`;
				return createBlock( 'core/shortcode', { text } );
			},
		},
	],
};

export default transforms;
