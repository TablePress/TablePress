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
import { createPortal, createRoot } from 'react-dom';
import { addFilter } from '@wordpress/hooks';

/**
 * Initializes a React component on the page.
 *
 * @param {string}    rootId    HTML ID of the root element for the component.
 * @param {Component} Component JSX component.
 */
export const initializeReactComponent = ( rootId, Component ) => {
	if ( process.env.DEVELOP ) {
		Component = <StrictMode>{ Component }</StrictMode>;
	}

	const root = document.getElementById( rootId );
	if ( root ) {
		createRoot( root ).render( Component );
	}
};

/**
 * Initializes a React component on the page, in a React Portal, and registers its meta box.
 *
 * @param {string}    slug      Slug of the component/feature module.
 * @param {string}    screen    Slug/action of the screen.
 * @param {Component} Component JSX component.
 */
export const initializeReactComponentInPortal = ( slug, screen, Component ) => {
	addFilter(
		`tablepress.${screen}ScreenFeatures`,
		`tp/${slug}/${screen}-screen-feature`,
		( features ) => ( [ ...features, slug ] ),
	);

	addFilter(
		`tablepress.${screen}ScreenPortals`,
		`tp/${slug}/${screen}-screen-portal`,
		( Portals ) => {
			return ( props ) => (
				<>
					<Portals { ...props } />
					{
						createPortal(
							<Component { ...props } />,
							document.getElementById( `tablepress-${slug}-section` ),
						)
					}
				</>
			);
		},
	);
};
