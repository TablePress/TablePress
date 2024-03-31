/**
 * JavaScript code for the "Export" screen.
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
import Screen from './export/screen';

initializeReactComponent(
	'tablepress-export-screen',
	<Screen />
);
