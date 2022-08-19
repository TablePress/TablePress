/**
 * JavaScript code for the TablePress table block in the block editor.
 *
 * @package TablePress
 * @subpackage Blocks
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/* globals tp */

let example = null;

const table_ids = Object.keys( tp.tables );

if ( table_ids.length ) {
	const random_table_id = table_ids[ Math.floor( Math.random() * table_ids.length ) ];
	example = {
		attributes: {
			id: random_table_id,
			parameters: '',
		},
	};
}

export default example;
