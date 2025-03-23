/**
 * JavaScript code for the "Edit" section integration of Snackbar Notices.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.1.0
 */

/**
 * WordPress dependencies.
 */
import { SnackbarList } from '@wordpress/components';
import { useDispatch, useSelect } from '@wordpress/data';
import { store as noticesStore } from '@wordpress/notices';

const Notifications = () => {
	const { removeNotice } = useDispatch( noticesStore );
	const notices = useSelect(
		( select ) => select( noticesStore ).getNotices(),
		[]
	);
	const snackbarNotices = notices.filter( ( { type } ) => type === 'snackbar' );

	if ( snackbarNotices.length === 0 ) {
		return null;
	}

	return (
		<SnackbarList
			notices={ snackbarNotices }
			onRemove={ removeNotice }
		/>
	);
};

export { Notifications };
