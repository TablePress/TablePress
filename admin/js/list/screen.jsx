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
import { useEffect, useState } from 'react';

/**
 * Internal dependencies.
 */
import { ShortcodeModal } from './shortcode-modal';
import { ConfirmDeleteModal } from '../common/modals';
import { TablePreview } from '../common/table-preview';
import { NoticeUI } from '../common/notice-ui';

/**
 * Returns the "List of Tables Screen" component's JSX markup.
 *
 * @return {Object} List of Tables Screen component.
 */
const Screen = () => {
	const [ preview, setPreview ] = useState( {
		isOpen: false,
		title: '',
		url: '',
	} );
	const [ confirmDeleteModal, setConfirmDeleteModal ] = useState( {
		isOpen: false,
		title: '',
		url: '',
	} );
	const [ shortcodeModal, setShortcodeModal ] = useState( {
		isOpen: false,
		shortcode: '',
	} );

	useEffect( () => {
		const handleClick = ( event ) => {
			if ( ! event.target ) {
				return;
			}

			/**
			 * Show a Modal with the Shortcode.
			 */
			if ( event.target.matches( '.shortcode a' ) ) {
				setShortcodeModal( {
					isOpen: true,
					shortcode: event.target.title,
				} );
				event.preventDefault();
				return;
			}

			/**
			 * Show a Confirm Delete Modal.
			 */
			if ( event.target.matches( '.delete a' ) ) {
				setConfirmDeleteModal( {
					isOpen: true,
					url: event.target.href,
					title: event.target.title,
				} );
				event.preventDefault();
				return;
			}

			// Open the Preview Modal if the link is clicked while no modifier key (for opening in a new tab/window) is pressed.
			if ( event.target.matches( '.table-preview a' ) && ! ( event.ctrlKey || event.metaKey || event.shiftKey ) ) {
				setPreview( {
					isOpen: true,
					url: event.target.href,
					title: event.target.title,
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
	}, [] );

	return (
		<>
			{ shortcodeModal.isOpen && (
				<ShortcodeModal
					shortcode={ shortcodeModal.shortcode }
					onRequestClose={ () => setShortcodeModal( ( state ) => ( { ...state, isOpen: false } ) ) }
				/>
			) }
			{ confirmDeleteModal.isOpen && (
				<ConfirmDeleteModal
					closeConfirmDeleteModal={ () => setConfirmDeleteModal( ( state ) => ( { ...state, isOpen: false } ) ) }
					deleteUrl={ confirmDeleteModal.url }
					title={ confirmDeleteModal.title }
				/>
			) }
			{ preview.isOpen && (
				<TablePreview
					onRequestClose={ () => setPreview( ( state ) => ( { ...state, isOpen: false } ) ) }
					title={ preview.title }
					url={ preview.url }
				/>
			) }
			<NoticeUI noticeType="snackbar" />
		</>
	);
};

export default Screen;
