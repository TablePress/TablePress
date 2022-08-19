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
					shortcode: ( { named: { id, ...shortcode_named_attrs }, numeric: [ ...shortcode_numeric_attrs ] } ) => { // eslint-disable-line no-unused-vars
						return shortcode_attrs_to_string( shortcode_named_attrs, shortcode_numeric_attrs );
					},
				},
			},
		},

		// Detect table Shortcodes that are typed into the block editor.
		{
			type: 'enter',
			regExp: shortcode.regexp( tp.table.shortcode ),
			transform: ( { content } ) => {
				const { named: { id = '', ...shortcode_named_attrs }, numeric: [ ...shortcode_numeric_attrs ] } = shortcode.next( tp.table.shortcode, content ).shortcode.attrs;
				const parameters = shortcode_attrs_to_string( shortcode_named_attrs, shortcode_numeric_attrs );
				return createBlock( block.name, { id, parameters } );
			},
		},

		// Add conversion option from "Shortcode" to "TablePress table" block.
		{
			type: 'block',
			blocks: [ 'core/shortcode' ],
			transform: ( { text: content } ) => {
				const { named: { id = '', ...shortcode_named_attrs }, numeric: [ ...shortcode_numeric_attrs ] } = shortcode.next( tp.table.shortcode, content ).shortcode.attrs;
				const parameters = shortcode_attrs_to_string( shortcode_named_attrs, shortcode_numeric_attrs );
				return createBlock( block.name, { id, parameters } );
			},
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
