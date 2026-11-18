/**
 * JavaScript code for the "Shortcode Modal" component on the "List of Tables Screen".
 *
 * @package TablePress
 * @subpackage List of Tables Screen
 * @author Tobias Bäthge
 * @since 3.5.0
 */

/**
 * WordPress dependencies.
 */
import {
	Modal,
} from '@wordpress/components';
import { useCopyToClipboard } from '@wordpress/compose';
import { useDispatch } from '@wordpress/data';
import { copy } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';
import {
	Icon,
	// IconButton, // Due to compatibility issues with WP < 7.0, we use a custom `IconButton` component instead of the one from the `@wordpress/ui` package.
	InputControl,
	InputLayout,
	Stack,
	Text,
} from '@wordpress/ui';

/**
 * Internal dependencies.
 */
import { TablePressIcon } from '../../img/icons';
import { IconButton } from '../common/icon-button';
import { showNotice } from '../common/functions';

/**
 * Returns the Shortcode Modal component's JSX markup.
 *
 * @param {Object}   props                Component props.
 * @param {Object}   props.shortcode      Table Shortcode.
 * @param {Function} props.onRequestClose Callback to close the Shortcode Modal.
 * @return {Object} Shortcode Modal component.
 */
export const ShortcodeModal = ( { shortcode, onRequestClose } ) => {
	const noticesStoreDispatch = useDispatch( noticesStore );

	const copyShortcodeButtonRef = useCopyToClipboard( shortcode, () => {
		onRequestClose();
		showNotice(
			{
				status: 'success',
				type: 'snackbar',
				content: __( 'Copied Shortcode to clipboard.', 'tablepress' ),
			},
			noticesStoreDispatch
		);
	} );

	return (
		<Modal
			icon={ <Icon icon={ TablePressIcon } size="36" style={ { display: 'flex', marginInlineEnd: '1em' } } /> }
			title={ __( 'Table Shortcode', 'tablepress' ) }
			onRequestClose={ onRequestClose }
		>
			<Stack direction="column" gap="sm">
				<Text>
					{ __( 'To embed this table into a post or page, use this Shortcode:', 'tablepress' ) }
				</Text>
				<InputControl
					label={ __( 'Table Shortcode', 'tablepress' ) }
					hideLabelFromVision={ true }
					id="table-information-shortcode"
					value={ shortcode }
					onFocus={ ( event ) => event.target.select() }
					readOnly={ true }
					suffix={
						<InputLayout.Slot padding="minimal">
							<IconButton
								label={ __( 'Copy Shortcode to clipboard', 'tablepress' ) }
								icon={ copy }
								ref={ copyShortcodeButtonRef }
								size="small"
								variant="minimal"
								tone="neutral"
							/>
						</InputLayout.Slot>
					}
				/>
			</Stack>
		</Modal>
	);
};
