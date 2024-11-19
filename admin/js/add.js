/**
 * JavaScript code for the "Add New" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/**
 * Internal dependencies.
 */
import { initializeReactComponent } from './common/react-loader';
import Screen from './add/screen';

initializeReactComponent(
	'tablepress-add-screen',
	<Screen />
);
