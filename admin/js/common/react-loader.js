/**
 * Common functions for loading React components in TablePress JS.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 2.2.0
 */

/**
 * WordPress dependencies.
 */
import { StrictMode } from 'react';
import { createRoot, render } from 'react-dom'; // eslint-disable-line react/no-deprecated

/**
 * Initializes a React component on the page.
 *
 * @param {string}    rootId    HTML ID of the root element for the component.
 * @param {Component} component JSX of the component.
 */
export const initializeReactComponent = ( rootId, component ) => {
	if ( process.env.DEVELOP ) {
		component = <StrictMode>{ component }</StrictMode>;
	}

	const root = document.getElementById( rootId );
	if ( root ) {
		// Compatibility check for React 17 and 18.
		if ( 'function' === typeof createRoot ) {
			// React 18 (WP 6.2 and newer): Use createRoot().
			createRoot( root ).render( component );
		} else {
			// React 17 (WP 6.1 and older): Use render().
			render( component, root );
		}
	}
};
