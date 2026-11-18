/**
 * JavaScript code for the "Jump to Row" modal on the "Edit" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.5.0
 */

/**
 * WordPress dependencies.
 */
import { useState } from 'react';
import {
	Button as LegacyButton,
	KeyboardShortcuts,
	Modal,
} from '@wordpress/components';
import {
	moveTo,
} from '@wordpress/icons';
import { __, _x } from '@wordpress/i18n';
import { displayShortcut, shortcutAriaLabel } from '@wordpress/keycodes';
import {
	Button,
	Icon,
	Stack,
	Text,
} from '@wordpress/ui';

/**
 * Internal dependencies.
 */
import { NumberControl } from '../../common/number-control';
import {
	jumpFirst,
	jumpLast,
} from '../../../img/icons';

/**
 * Jumps to the specified row in the table editor.
 *
 * @param {number} targetRowNumber
 */
const jumpToRowInEditor = ( targetRowNumber ) => {
	const targetRowIdx = targetRowNumber - 1;
	const targetRow = document.querySelector( `#table-editor tr[data-y="${ targetRowIdx }"]` );
	if ( ! targetRow ) {
		return;
	}

	targetRow.style.scrollMarginInlineStart = '200px'; // Ensure that we are scrolling to the left edge of the table editor.
	targetRow.scrollIntoView( { behavior: 'smooth', block: 'center', inline: 'start' } );
	targetRow.style.removeProperty( 'scroll-margin-inline-start' );
	tp.editor.updateSelectionFromCoords( 0, targetRowIdx, 0, targetRowIdx );
};

/**
 * Returns the Jump to Row Modal component's JSX markup.
 *
 * @param {Object}   props                     Component props.
 * @param {Function} props.closeJumpToRowModal Callback to close the Confirm Delete modal.
 * @param {number}   props.lastRowNumber       The number of the last row in the table.
 * @return {Object} Jump to Row Modal component.
 */
export const JumpToRowModal = ( { closeJumpToRowModal, lastRowNumber } ) => {
	const firstRowNumber = 1;

	const [ rowJumpToNumber, setRowJumpToNumber ] = useState( firstRowNumber );

	/**
	 * Jumps to a specific row.
	 *
	 * @param {number} rowNumber The row number to jump to.
	 * @param {Event}  event     Keyboard event.
	 */
	const jumpToRowNumber = ( rowNumber, event ) => {
		event.preventDefault();
		jumpToRowInEditor( rowNumber );
		if ( ! event.shiftKey ) {
			closeJumpToRowModal();
		}
	};

	/**
	 * Jumps to the current value in the "Jump to Row" modal.
	 *
	 * This serves as a callback with a consistent function reference for the `KeyboardShortcuts` component.
	 *
	 * @param {Event} event Keyboard event.
	 */
	const jumpToEnteredValue = ( event ) => {
		jumpToRowNumber( rowJumpToNumber, event );
	};

	/**
	 * Jumps to the first row of the table editor.
	 *
	 * This serves as a callback with a consistent function reference for the `KeyboardShortcuts` component.
	 *
	 * @param {Event} event Keyboard event.
	 */
	const jumpToFirstRow = ( event ) => {
		jumpToRowNumber( firstRowNumber, event );
	};

	/**
	 * Jumps to the last row of the table editor.
	 *
	 * This serves as a callback with a consistent function reference for the `KeyboardShortcuts` component.
	 *
	 * @param {Event} event Keyboard event.
	 */
	const jumpToLastRow = ( event ) => {
		jumpToRowNumber( lastRowNumber, event );
	};

	return (
		<Modal
			icon={ <Icon icon={ moveTo } style={ { display: 'flex', marginInlineEnd: '1em' } } /> }
			title={ __( 'Jump to row…', 'tablepress' ) }
			onRequestClose={ () => closeJumpToRowModal() }
			style={ {
				minWidth: '300px',
				width: '300px',
			} }
		>
			<Stack direction="column" gap="md">
				<Text>
					{ __( 'Enter a row number or jump directly to an edge row.', 'tablepress' ) }
				</Text>
				<Stack
					direction="row"
					gap="lg"
					align="center"
					style={ {
						width: '100%',
					} }
				>
					<div style={ { flexGrow: 1 } }>
						<NumberControl
							size="compact"
							label={ __( 'Target row', 'tablepress' ) }
							hideLabelFromVision={ true }
							title={ __( 'This field must contain a positive number.', 'tablepress' ) }
							value={ rowJumpToNumber }
							onChange={ ( newRowJumpToNumber ) => {
								newRowJumpToNumber = '' !== newRowJumpToNumber ? parseInt( newRowJumpToNumber, 10 ) : '';
								setRowJumpToNumber( newRowJumpToNumber );
							} }
							min={ firstRowNumber }
							max={ lastRowNumber }
							required={ true }
						/>
					</div>
					<Stack direction="column" gap="xs" align="center">
						<LegacyButton
							label={ __( 'Jump to first row', 'tablepress' ) }
							icon={ jumpFirst }
							onClick={ jumpToFirstRow }
							size="small"
							variant="minimal"
							shortcut={ {
								ariaLabel: shortcutAriaLabel.undefined( '↑' ),
								display: displayShortcut.undefined( '↑' ),
							} }
							tooltipPosition="top"
						>
							{ _x( 'first', 'First table row', 'tablepress' ) }
						</LegacyButton>
						<Button
							size="compact"
							onClick={ jumpToEnteredValue }
							style={ {
								width: '100%',
							} }
						>
							{ __( 'Jump', 'tablepress' ) }
						</Button>
						<LegacyButton
							label={ __( 'Jump to last row', 'tablepress' ) }
							icon={ jumpLast }
							onClick={ jumpToLastRow }
							size="small"
							variant="minimal"
							shortcut={ {
								ariaLabel: shortcutAriaLabel.undefined( '↓' ),
								display: displayShortcut.undefined( '↓' ),
							} }
							tooltipPosition="bottom"
						>
							{ _x( 'last', 'Last table row', 'tablepress' ) }
						</LegacyButton>
					</Stack>
				</Stack>
			</Stack>
			<KeyboardShortcuts
				shortcuts={ {
					'up': jumpToFirstRow,
					'shift+up': jumpToFirstRow,
					'down': jumpToLastRow,
					'shift+down': jumpToLastRow,
				} }
			/>
			<KeyboardShortcuts
				bindGlobal={ true }
				shortcuts={ {
					'enter': jumpToEnteredValue,
					'shift+enter': jumpToEnteredValue,
				} }
			/>
		</Modal>
	);
};
