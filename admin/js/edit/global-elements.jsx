/**
 * JavaScript code for global elements on the "Edit" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.5.0
 */

/**
 * WordPress dependencies.
 */
import { useEffect, useState } from 'react';
import {
	KeyboardShortcuts,
} from '@wordpress/components';
import { useDispatch } from '@wordpress/data';
import { __, sprintf } from '@wordpress/i18n';
import { store as noticesStore } from '@wordpress/notices';

/**
 * Internal dependencies.
 */
import { saveTableChanges, showPreview } from './callbacks';
import { NoticeUI } from '../common/notice-ui';
import { TablePreview } from '../common/table-preview';
import { useScreenSettings, useUpdateScreenSettings } from './context/screen-settings';
import { useTableMeta, useUpdateTableMeta } from './context/table-meta';
import { useTableOptions } from './context/table-options';

/**
 * Global elements on the "Edit" screen, such as keyboard shortcuts, notifications, and the table preview modal.
 *
 * @return {Object} GlobalElements component.
 */
export const GlobalElements = () => {
	const noticesStoreDispatch = useDispatch( noticesStore );

	const { previewIsOpen, previewSrcDoc, previewUrl } = useScreenSettings();
	const updateScreenSettings = useUpdateScreenSettings();
	const tableMeta = useTableMeta();
	const updateTableMeta = useUpdateTableMeta();
	const tableOptions = useTableOptions();
	const [ triggerSaveChanges, setTriggerSaveChanges ] = useState( false );
	const [ triggerPreview, setTriggerPreview ] = useState( false );

	const saveChangesCallback = ( event ) => {
		event.preventDefault();
		// Blur the focussed element to make sure that all `change` and `blur` events are triggered.
		document.activeElement.blur(); // eslint-disable-line @wordpress/no-global-active-element
		// Don't save changes directly, but trigger a save on the next render.
		setTriggerSaveChanges( true );
	};

	/*
	 * Save changes when the `triggerSaveChanges` state is set.
	 * This ensures that the save function receives the latest data, after all `change` and `blur` events were triggered.
	 */
	useEffect( () => {
		if ( triggerSaveChanges ) {
			setTriggerSaveChanges( false );
			saveTableChanges( { noticesStoreDispatch, tableMeta, tableOptions, updateScreenSettings, updateTableMeta } );
		}
	}, [ triggerSaveChanges, noticesStoreDispatch, tableMeta, tableOptions, updateScreenSettings, updateTableMeta ] ); // `updateScreenSettings` and `updateTableMeta` have a stable identity and are only included to satisfy eslint.

	const shortcuts = {
		'mod+s': saveChangesCallback,
	};

	/*
	* Trigger the preview when the `triggerPreview` state is set.
	* This ensures that the preview function receives the latest data, after all `change` and `blur` events were triggered.
	*/
	useEffect( () => {
		if ( tp.screenOptions.currentUserCanPreviewTable && triggerPreview ) {
			setTriggerPreview( false );
			showPreview( { noticesStoreDispatch, tableMeta, tableOptions, updateScreenSettings } );
		}
	}, [ triggerPreview, noticesStoreDispatch, tableMeta, tableOptions, updateScreenSettings ] ); // `updateScreenSettings` has a stable identity and are only included to satisfy eslint.

	// Add a keyboard shortcut for showing the preview.
	if ( tp.screenOptions.currentUserCanPreviewTable ) {
		const showPreviewCallback = ( event ) => {
			event.preventDefault();
			// Blur the focussed element to make sure that all `change` and `blur` events are triggered.
			document.activeElement.blur(); // eslint-disable-line @wordpress/no-global-active-element
			// Don't load the preview directly, but trigger it on the next render.
			setTriggerPreview( true );
		};

		shortcuts[ 'mod+p' ] = showPreviewCallback;
	}

	// Get the table name, and use "(no name)" if the table has no name.
	let tablePreview = <></>;
	if ( tp.screenOptions.currentUserCanPreviewTable && previewIsOpen ) {
		let tableName = tableMeta.name.trim();
		if ( '' === tableName ) {
			tableName = __( '(no name)', 'tablepress' );
		}

		/* translators: %1$s: Table name, %2$s: Table ID */
		const title = sprintf( __( 'Preview of table “%1$s” (ID %2$s)', 'tablepress' ), tableName, tableMeta.id );

		tablePreview = (
			<TablePreview
				onRequestClose={ () => updateScreenSettings( { previewIsOpen: false, previewSrcDoc: '' } ) }
				srcDoc={ previewSrcDoc }
				title={ title }
				url={ previewUrl }
			/>
		);
	}

	return (
		<>
			<KeyboardShortcuts
				bindGlobal={ true }
				shortcuts={ shortcuts }
			/>
			<NoticeUI noticeType="snackbar" />
			{ tablePreview }
		</>
	);
};
