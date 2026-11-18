/**
 * JavaScript code for the "Edit Screen" component.
 *
 * @package TablePress
 * @subpackage Edit Screen
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/**
 * WordPress dependencies.
 */
import {
	withFilters,
} from '@wordpress/components';
import domReady from '@wordpress/dom-ready';
import { applyFilters } from '@wordpress/hooks';

/**
 * Internal dependencies.
 */
import { GlobalElements } from './global-elements';

/*
 * Allow other scripts to register their UI components to be rendered on the Edit Screen.
 * Portals allow to render components outside of the normal React tree, in separate DOM nodes.
 */
let features = [];
let Portals = () => <></>;
domReady( () => {
	features = applyFilters( 'tablepress.editScreenFeatures', [] );
	Portals = withFilters( 'tablepress.editScreenPortals' )( () => <></> );
} );

/**
 * Returns the "Edit Screen" component's JSX markup.
 *
 * @return {Object} Edit Screen component.
 */
const Screen = () => {
	return (
		<>
			<Portals
				features={ features }
			/>
			<GlobalElements />
		</>
	);
};

export default Screen;
