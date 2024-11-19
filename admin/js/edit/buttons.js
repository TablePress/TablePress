/**
 * JavaScript code for the "Edit" section integration of the "Save Changes" and "Preview" buttons.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/**
 * WordPress dependencies.
 */
import {
	Button,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { __, _x, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../common/react-loader';

const modifierKey = ( window?.navigator?.platform?.includes( 'Mac' ) ) ?
	_x( '⌘', 'keyboard shortcut modifier key on a Mac keyboard', 'tablepress' ) :
	_x( 'Ctrl+', 'keyboard shortcut modifier key on a non-Mac keyboard', 'tablepress' );

const Section = () => {
	return (
		<VStack
			style={ {
				margin: '1.5rem 0',
			} }
		>
			<HStack	alignment="left">
				{
					( tp.screen_options.currentUserCanPreviewTable ) && (
						<Button
							variant="secondary"
							href={ tp.screen_options.previewUrl }
							className="button-preview"
							text={ __( 'Preview', 'tablepress' ) }
							title={ sprintf( __( 'Keyboard Shortcut: %s', 'tablepress' ), sprintf( _x( '%1$sP', 'keyboard shortcut for Preview', 'tablepress' ), modifierKey ) ) }
							onClick={ ( event ) => ( tp.callbacks.table_preview.process( event ) ) }
						/>
					)
				}
				<Button
					variant="primary"
					className="button-save-changes"
					text={ __( 'Save Changes', 'tablepress' ) }
					title={ sprintf( __( 'Keyboard Shortcut: %s', 'tablepress' ), sprintf( _x( '%1$sS', 'keyboard shortcut for Save Changes', 'tablepress' ), modifierKey ) ) }
					onClick={ ( event ) => ( tp.callbacks.save_changes.process( event ) ) }
				/>
			</HStack>
		</VStack>
	);
};

initializeReactComponentInPortal(
	'tablepress_edit-buttons-1',
	'edit',
	Section,
);

initializeReactComponentInPortal(
	'tablepress_edit-buttons-2',
	'edit',
	Section,
);
