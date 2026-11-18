/**
 * JavaScript code for the "Edit Screen" table options context.
 *
 * @package TablePress
 * @subpackage Edit Screen
 * @author Tobias Bäthge
 * @since 3.5.0
 */

/**
 * WordPress dependencies.
 */
import { createContext, useCallback, useContext, useReducer } from 'react';

const TableOptionsContext = createContext();
const TableOptionsDispatchContext = createContext();

// For debugging in React DevTools, set display names for the contexts.
// TableOptionsContext.displayName = 'TableOptionsContext';
// TableOptionsDispatchContext.displayName = 'TableOptionsDispatchContext';

const initialTableOptions = structuredClone( tp.table.options );

/**
 * Updates the screen settings state based on a patch.
 *
 * @param {Object}          state The current screen settings state.
 * @param {Object|Function} patch The patch to apply to the state.
 * @return {Object} The updated screen settings state.
 */
const tableOptionsReducer = ( state, patch ) => {
	const nextPatch = 'function' === typeof patch ? patch( state ) : patch;

	tp.table.options = { ...tp.table.options, ...nextPatch };

	// Redraw the table when certain options are changed.
	if ( nextPatch.hasOwnProperty( 'table_head' ) || nextPatch.hasOwnProperty( 'table_foot' ) ) {
		tp.editor.updateTable();
	}

	return { ...state, ...nextPatch };
};

/**
 * Returns the TableOptionsProvider component's JSX markup.
 *
 * @param {Object} props          Function parameters.
 * @param {Object} props.children Children of the component.
 * @return {Object} TableOptionsProvider component.
 */
export const TableOptionsProvider = ( { children } ) => {
	const [ state, dispatch ] = useReducer( tableOptionsReducer, initialTableOptions );

	/**
	 * Dispatches a patch to update the table options state and marks the table as having unsaved changes.
	 *
	 * This function is a wrapper around the `dispatch` function from the `useReducer` hook,
	 * which allows to update the state and to also perform additional actions.
	 * Doing this directly in the reducer function can lead to issues due to asynchronous
	 * execution and React's batching of state updates.
	 *
	 * @param {Object|Function} patch The patch to apply to the state.
	 */
	const dispatchWithExtras = useCallback( ( patch ) => {
		dispatch( patch );

		tp.helpers.unsaved_changes.set();
	}, [ dispatch ] );

	return (
		<TableOptionsContext.Provider value={ state }>
			<TableOptionsDispatchContext.Provider value={ dispatchWithExtras }>
				{ children }
			</TableOptionsDispatchContext.Provider>
		</TableOptionsContext.Provider>
	);
};

/**
 * Returns the TableOptions context.
 *
 * @return {Object} TableOptions context.
 */
export const useTableOptions = () => {
	const context = useContext( TableOptionsContext );
	if ( ! context ) {
		throw new Error( 'useTableOptions must be used within a TableOptionsProvider.' );
	}
	return context;
};

/**
 * Returns the TableOptionsDispatch context.
 *
 * @return {Function} TableOptionsDispatch context.
 */
export const useUpdateTableOptions = () => {
	const dispatch = useContext( TableOptionsDispatchContext );
	if ( ! dispatch ) {
		throw new Error( 'useUpdateTableOptions must be used within a TableOptionsProvider.' );
	}
	return dispatch;
};

// Expose context primitives to the global window object for separate modules.
window.tp = window.tp || {};
window.tp.contexts = window.tp.contexts || {};
window.tp.contexts.useTableOptions = useTableOptions;
window.tp.contexts.useUpdateTableOptions = useUpdateTableOptions;
