/**
 * JavaScript code for the "Import" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 1.0.0
 */

/**
 * Internal dependencies.
 */
import { initializeReactComponent } from './common/react-loader';
import Screen from './import/screen';

initializeReactComponent(
	'tablepress-import-screen',
	<Screen />
);
