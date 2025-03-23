/**
 * JavaScript code for the "Edit" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/**
 * Load the default "Edit" screen sections.
 */
import './edit/buttons';
import './edit/table-information';
import './edit/table-manipulation';
import './edit/table-options';
import './edit/datatables-features';
import './edit/table-preview';
import './edit/other-actions';

/**
 * Load the non-React "Edit" screen JavaScript code, mostly for the table data editor.
 */
import './edit/editor';
import './edit/keyboard-shortcuts';
import './edit/screen-options';

/**
 * Internal dependencies.
 */
import { initializeReactComponent } from './common/react-loader';
import Screen from './edit/screen';

initializeReactComponent(
	'tablepress-edit-screen',
	<Screen />,
);
