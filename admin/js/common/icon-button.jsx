/**
 * JavaScript code for the "IconButton" component, a custom temporary replacement for the `@wordpress/ui` package's `IconButton` component.
 * The original `IconButton` component is not compatible with WP < 7.0, due to dependencies on the `wp-theme` and `wp-private-apis` packages.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.5.0
 */

/**
 * WordPress dependencies.
 */
import { forwardRef } from 'react';
import {
	Button as LegacyButton,
} from '@wordpress/components';

/**
 * Returns the IconButton component's JSX markup.
 *
 * @param {Object}  props                       Component props.
 * @param {boolean} props.focusableWhenDisabled Whether the button should be focusable when disabled.
 * @param {Object}  props.shortcut              Keyboard shortcut for the button.
 * @param {Object}  ref                         The ref to the button.
 * @return {Object} The rendered IconButton component.
 */
export const IconButton = forwardRef( ( { focusableWhenDisabled, shortcut, ...props }, ref ) => {
	return (
		<LegacyButton
			__next40pxDefaultSize
			ref={ ref }
			accessibleWhenDisabled={ focusableWhenDisabled }
			showTooltip={ shortcut ? true : undefined }
			shortcut={
				shortcut ?
					{
						display: shortcut.displayShortcut,
						ariaLabel: shortcut.label,
					} :
					undefined
			}
			{ ...props }
		/>
	);
} );
