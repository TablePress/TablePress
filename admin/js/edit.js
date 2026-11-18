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
import './edit/header-bar';
import './edit/table-information';
import './edit/toolbar';
import './edit/table-options';
import './edit/datatables-features';

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
import { ScreenSettingsProvider } from './edit/context/screen-settings';
import { TableMetaProvider } from './edit/context/table-meta';
import { TableOptionsProvider } from './edit/context/table-options';

initializeReactComponent(
	'tablepress-edit-screen',
	<TableOptionsProvider>
		<TableMetaProvider>
			<ScreenSettingsProvider>
				<Screen />
			</ScreenSettingsProvider>
		</TableMetaProvider>
	</TableOptionsProvider>,
);
