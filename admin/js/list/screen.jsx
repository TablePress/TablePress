/**
 * JavaScript code for the "List of Tables Screen" component.
 *
 * @package TablePress
 * @subpackage List of Tables Screen
 * @author Tobias Bäthge
 * @since 3.1.0
 */

/**
 * WordPress dependencies.
 */
import { useCallback, useEffect, useRef, useState } from 'react';
import {
	Button,
	Icon,
	__experimentalHStack as HStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalInputControl as InputControl, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	__experimentalInputControlSuffixWrapper as InputControlSuffixWrapper, // eslint-disable-line @wordpress/no-unsafe-wp-apis
	Modal,
	__experimentalVStack as VStack, // eslint-disable-line @wordpress/no-unsafe-wp-apis
} from '@wordpress/components';
import { useCopyToClipboard } from '@wordpress/compose';
import { useDispatch } from '@wordpress/data';
import { copySmall, external } from '@wordpress/icons';
import { __, _n } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies.
 */
import { TablePressIcon, TablePressIconSimple } from '../../img/tablepress-icon';
import { Notifications } from '../common/notifications';

/**
 * Returns the Table Preview component's JSX markup.
 *
 * @param {Object}   props                Component props.
 * @param {string}   props.title          Title of the preview.
 * @param {string}   props.url            URL of the preview.
 * @param {Function} props.onRequestClose Callback to close the preview.
 * @return {Object} Table Preview component.
 */
const TablePreview = ( { title, url, onRequestClose } ) => {
	return (
		<Modal
			icon={ <Icon icon={ TablePressIcon } size="36" style={ { display: 'flex', marginRight: '1rem' } } /> }
			title={ title }
			className="table-preview-modal"
			onRequestClose={ onRequestClose }
			isFullScreen={ true } // Using size="full" is only possible in WP 6.5+.
			headerActions={
				<Button
					icon={ external }
					size="compact"
					label={ __( 'Open the preview in a new tab', 'tablepress' ) }
					href={ url }
					target="_blank"
				/>
			}
		>
			<iframe
				title={ title }
				src={ url }
			/>
		</Modal>
	);
};

/**
 * Returns the Shortcode Modal component's JSX markup.
 *
 * @param {Object}   props                Component props.
 * @param {Object}   props.shortcode      Table Shortcode.
 * @param {Function} props.onRequestClose Callback to close the Shortcode Modal.
 * @return {Object} Shortcode Modal component.
 */
const ShortcodeModal = ( { shortcode, onRequestClose } ) => {
	const { createSuccessNotice } = useDispatch( noticesStore );
	const copyShortcodeButtonRef = useCopyToClipboard( shortcode, () => {
		onRequestClose();
		createSuccessNotice(
			__( 'Copied Shortcode to clipboard.', 'tablepress' ),
			{
				type: 'snackbar',
				icon: <Icon icon={ TablePressIconSimple } />,
			}
		);
	} );

	return (
		<Modal
			icon={ <Icon icon={ TablePressIcon } size="36" style={ { display: 'flex', marginRight: '1rem' } } /> }
			title={ __( 'Table Shortcode', 'tablepress' ) }
			onRequestClose={ onRequestClose }
		>
			<VStack>
				<span>
					{ __( 'To embed this table into a post or page, use this Shortcode:', 'tablepress' ) }
				</span>
				<InputControl
					__next40pxDefaultSize
					type="text"
					id="table-information-shortcode"
					value={ shortcode }
					onFocus={ ( event ) => event.target.select() }
					readOnly={ true }
					suffix={
						<InputControlSuffixWrapper variant="control">
							<Button
								icon={ copySmall }
								ref={ copyShortcodeButtonRef }
								size="small"
								label={ __( 'Copy Shortcode to clipboard', 'tablepress' ) }
							/>
						</InputControlSuffixWrapper>
					}
				/>
			</VStack>
		</Modal>
	);
};

/**
 * Returns the Confirm Delete Modal component's JSX markup.
 *
 * @param {Object}   props                         Component props.
 * @param {string}   props.title                   Title of the modal.
 * @param {string}   props.deleteUrl               URL to delete the table.
 * @param {Function} props.closeConfirmDeleteModal Callback to close the Confirm Delete modal.
 * @return {Object} Confirm Delete Modal component.
 */
const ConfirmDeleteModal = ( { title, deleteUrl, closeConfirmDeleteModal } ) => {
	const cancelButtonRef = useRef();
	const deleteButtonRef = useRef();

	const handleEnter = useCallback(
		( event ) => {
			// Avoid triggering the action when a button is focused, as this can cause a double submission.
			const isCancelOrDeleteButton =
				event.target === cancelButtonRef.current ||
				event.target === deleteButtonRef.current;

			if ( ! isCancelOrDeleteButton && 'Enter' === event.key ) {
				deleteButtonRef.current.click();
			}
		},
		[],
	);

	return (
		<Modal
			className="has-size-medium" // Using size="medium" is only possible in WP 6.5+.
			icon={ <Icon icon={ TablePressIcon } size="36" style={ { display: 'flex', marginRight: '1rem' } } /> }
			title={ title }
			isDismissible={ false }
			onKeyDown={ handleEnter }
			onRequestClose={ closeConfirmDeleteModal }
		>
			<VStack spacing={ 8 }>
				<span>
					{ _n( 'Do you really want to delete this table?', 'Do you really want to delete these tables?', 1, 'tablepress' ) }
					<br />
					{ __( 'Deleting a table is permanent and can not be undone!', 'tablepress' ) }
				</span>
				<HStack alignment="right">
					<Button
						ref={ cancelButtonRef }
						variant="tertiary"
						text={ __( 'Cancel', 'tablepress' ) }
						onClick={ closeConfirmDeleteModal }
					/>
					<Button
						ref={ deleteButtonRef }
						variant="primary"
						isDestructive={ true }
						href={ deleteUrl }
						text={ __( 'Delete', 'tablepress' ) }
						onClick={ closeConfirmDeleteModal }
					/>
				</HStack>
			</VStack>
		</Modal>
	);
};

/**
 * Returns the "List of Tables Screen" component's JSX markup.
 *
 * @return {Object} List of Tables Screen component.
 */
const Screen = () => {
	const [ screenData, setScreenData ] = useState( {
		previewIsOpen: false,
		previewUrl: '',
		previewTitle: '',
		shortcode: '',
		shortcodeModalIsOpen: false,
	} );

	const updateScreenData = ( updatedScreenData ) => {
		// Use an updater function to ensure that the current state is used when updating screen data.
		setScreenData( ( currentScreenData ) => ( {
			...currentScreenData,
			...updatedScreenData,
		} ) );
	};

	useEffect( () => {
		const handleClick = ( event ) => {
			if ( ! event.target ) {
				return;
			}

			// Open the Preview Modal if the link is clicked while no modifier key is pressed.
			if ( event.target.matches( '.table-preview a' ) && ! ( event.ctrlKey || event.metaKey || event.shiftKey ) ) {
				updateScreenData( {
					previewIsOpen: true,
					previewUrl: event.target.href,
					previewTitle: event.target.title,
				} );
				event.preventDefault();
				return;
			}

			/**
			 * Show a Modal with the Shortcode.
			 */
			if ( event.target.matches( '.shortcode a' ) ) {
				updateScreenData( {
					shortcode: event.target.title,
					shortcodeModalIsOpen: true,
				} );
				event.preventDefault();
				return;
			}

			/**
			 * Show a Confirm Delete Modal.
			 */
			if ( event.target.matches( '.delete a' ) ) {
				updateScreenData( {
					confirmDeleteUrl: event.target.href,
					confirmDeleteTitle: event.target.title,
					confirmDeleteModalIsOpen: true,
				} );
				event.preventDefault();
				return;
			}
		};

		document.querySelector( '.tablepress-all-tables' ).addEventListener( 'click', handleClick );

		// Clean-up function for the effect.
		return () => {
			document.querySelector( '.tablepress-all-tables' ).removeEventListener( 'click', handleClick );
		};
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps -- This should only run on the initial render, so no dependencies are needed.

	return (
		<>
			{ screenData.previewIsOpen && (
				<TablePreview
					title={ screenData.previewTitle }
					url={ screenData.previewUrl }
					onRequestClose={ () => updateScreenData( { previewIsOpen: false } ) }
				/>
			) }
			{ screenData.shortcodeModalIsOpen && (
				<ShortcodeModal
					shortcode={ screenData.shortcode }
					onRequestClose={ () => updateScreenData( { shortcodeModalIsOpen: false } ) }
				/>
			) }
			{ screenData.confirmDeleteModalIsOpen && (
				<ConfirmDeleteModal
					title={ screenData.confirmDeleteTitle }
					deleteUrl={ screenData.confirmDeleteUrl }
					closeConfirmDeleteModal={ () => updateScreenData( { confirmDeleteModalIsOpen: false } ) }
				/>
			) }
			<Notifications />
		</>
	);
};

export default Screen;
