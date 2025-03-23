/**
 * JavaScript code for the TablePress table block icon in the block editor.
 *
 * @package TablePress
 * @subpackage Blocks
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/**
 * WordPress dependencies.
 */
import { SVG, Path } from '@wordpress/primitives';

const TablePressTableIcon = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-32 -32 64 64" fill="#50575e">
		<Path d="M0-25.854h-25.854v51.708h51.708V0H21v21h-42v-42H0Z" />
		<Path d="M-18-18h10v10h-10zM-18-5h10V5h-10zM-5-5H5V5H-5zM-18 8h10v10h-10zM-5 8H5v10H-5zM8 8h10v10H8zM5-31h6.18v6.18H5zM19-25h6.18v6.18H19zM0-15h3.82v3.82H0zM10-20h3.82v3.82H10zM25-12h3.82v3.82H25zM8-13h10v10H8z" />
	</SVG>
);

export default TablePressTableIcon;
