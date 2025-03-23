/**
 * JavaScript code for the "Edit Screen" component.
 *
 * @package TablePress
 * @subpackage Edit Screen
 * @author Tobias Bäthge
 * @since 3.0.0
 */

/**
 * WordPress dependencies.
 */
import { useEffect, useState } from 'react';
import {
	withFilters,
} from '@wordpress/components';
import { applyFilters } from '@wordpress/hooks';

/*
 * Allow other scripts to register their UI components to be rendered on the Edit Screen.
 * Portals allow to render components outside of the normal React tree, in separate DOM nodes.
 */
const features = applyFilters( 'tablepress.editScreenFeatures', [] );
const Portals = withFilters( 'tablepress.editScreenPortals' )( () => <></> );

/**
 * Returns the "Edit Screen" component's JSX markup.
 *
 * @return {Object} Edit Screen component.
 */
const Screen = () => {
	const [ screenData, setScreenData ] = useState( {
		copyUrl: tp.screenOptions.copyUrl,
		deleteUrl: tp.screenOptions.deleteUrl,
		exportUrl: tp.screenOptions.exportUrl,
		isSaving: false,
		previewIsLoading: false,
		previewIsOpen: false,
		previewSrcDoc: '',
		previewUrl: tp.screenOptions.previewUrl,
		triggerPreview: false,
		triggerSaveChanges: false,
	} );
	const [ tableOptions, setTableOptions ] = useState( () => ( { ...tp.table.options } ) );
	const [ tableMeta, setTableMeta ] = useState( () => ( { ...tp.table.meta } ) );

	// Turn off "Enable Visitor Features" if the table has merged cells.
	useEffect( () => {
		if ( tableOptions.use_datatables && tp.helpers.editor.has_merged_body_cells() ) {
			updateTableOptions( { use_datatables: false } );
		}
	}, [] ); // eslint-disable-line react-hooks/exhaustive-deps -- This should only run on the initial render, so no dependencies are needed.

	const updateScreenData = ( updatedScreenData ) => {
		// Use an updater function to ensure that the current state is used when updating screen data.
		setScreenData( ( currentScreenData ) => ( {
			...currentScreenData,
			...updatedScreenData,
		} ) );
	};

	const updateTableOptions = ( updatedTableOptions ) => {
		// Use an updater function to ensure that the current state is used when updating table options.
		setTableOptions( ( currentTableOptions ) => ( {
			...currentTableOptions,
			...updatedTableOptions,
		} ) );

		tp.table.options = { ...tp.table.options, ...updatedTableOptions };
		tp.helpers.unsaved_changes.set();

		// Redraw the table when certain options are changed.
		if ( [ 'table_head', 'table_foot' ].some( ( optionName ) => ( Object.keys( updatedTableOptions ).includes( optionName ) ) ) ) {
			tp.editor.updateTable();
		}
	};

	const updateTableMeta = ( updatedTableMeta ) => {
		// Use an updater function to ensure that the current state is used when updating table meta.
		setTableMeta( ( currentTableMeta ) => ( {
			...currentTableMeta,
			...updatedTableMeta,
		} ) );

		tp.table.meta = { ...tp.table.meta, ...updatedTableMeta };
		tp.helpers.unsaved_changes.set();
	};

	return (
		<Portals
			screenData={ screenData }
			updateScreenData={ updateScreenData }
			tableMeta={ tableMeta }
			updateTableMeta={ updateTableMeta }
			tableOptions={ tableOptions }
			updateTableOptions={ updateTableOptions }
			features={ features }
		/>
	);
};

export default Screen;
