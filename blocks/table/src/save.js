/**
 * JavaScript code for the TablePress table block in the block editor.
 *
 * @package TablePress
 * @subpackage Blocks
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/**
 * WordPress dependencies
 */
import { RawHTML } from '@wordpress/element';

/**
 * The save function defines the way in which the different attributes should
 * be combined into the final markup, which is then serialized by the block
 * editor into `post_content`.
 *
 * @param {Object} params                       Function parameters.
 * @param {Object} params.attributes            Block attributes.
 * @param {string} params.attributes.id         Table ID.
 * @param {string} params.attributes.parameters Table render attributes.
 * @return {WPElement} Element to render.
 */
export default function save( { attributes: { id = '', parameters = '' } } ) {
	if ( '' === id ) {
		return '';
	}

	parameters = parameters.trim();
	if ( '' !== parameters ) {
		parameters += ' ';
	}
	return <RawHTML>{ `[${ tp.table.shortcode } id=${ id } ${ parameters }/]` }</RawHTML>;
}
