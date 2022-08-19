/**
 * JavaScript code for the TablePress table block in the block editor.
 *
 * @package TablePress
 * @subpackage Blocks
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/**
 * WordPress dependencies.
 */
import { registerBlockType } from '@wordpress/blocks';

/**
 * Internal dependencies.
 */
import transforms from './transforms';
import edit from './edit';
import save from './save';
import example from './example';

/**
 * Get the block name from the block.json.
 */
import block from '../block.json';

/**
 * Register the block.
 */
registerBlockType( block.name, {
	transforms,
	edit,
	save,
	example,
} );
