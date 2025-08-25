/**
 * JavaScript code for the "Edit" section integration of the "Table Preview".
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.1.0
 */

/**
 * WordPress dependencies.
 */
import {
	Icon,
	Modal,
} from '@wordpress/components';
import { TablePressIcon } from '../../img/tablepress-icon';
import { __, sprintf } from '@wordpress/i18n';

/**
 * Internal dependencies.
 */
import { initializeReactComponentInPortal } from '../common/react-loader';

const Section = ( { screenData, updateScreenData, tableMeta } ) => {
	// Bail early if the current user can't preview the table or the preview is not open.
	if ( ! tp.screenOptions.currentUserCanPreviewTable || ! screenData.previewIsOpen ) {
		return <></>;
	}

	// Get the table name, and use "(no name)" if the table has no name.
	let tableName = tableMeta.name;
	if ( '' === tableName.trim() ) {
		tableName = __( '(no name)', 'tablepress' );
	}

	/* translators: %1$s: Table name, %2$s: Table ID */
	const title = sprintf( __( 'Preview of table “%1$s” (ID %2$s)', 'tablepress' ), tableName, tableMeta.id );

	return (
		<Modal
			icon={ <Icon icon={ TablePressIcon } size="36" style={ { display: 'flex', marginRight: '1rem' } } /> }
			title={ title }
			className="table-preview-modal"
			onRequestClose={ () => updateScreenData( { previewIsOpen: false, previewSrcDoc: '' } ) }
			isFullScreen={ true } // Using size="full" is only possible in WP 6.5+.
		>
			<iframe
				title={ title }
				src={ '' === screenData.previewSrcDoc ? screenData.previewUrl : undefined }
				srcDoc={ '' !== screenData.previewSrcDoc ? screenData.previewSrcDoc : undefined }
			/>
		</Modal>
	);
};

initializeReactComponentInPortal(
	'table-preview',
	'edit',
	Section,
);
