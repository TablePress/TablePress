/**
 * JavaScript code for the "Edit Screen" table meta context.
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

const TableMetaContext = createContext();
const TableMetaDispatchContext = createContext();

// For debugging in React DevTools, set display names for the contexts.
// TableMetaContext.displayName = 'TableMetaContext';
// TableMetaDispatchContext.displayName = 'TableMetaDispatchContext';

const initialTableMeta = structuredClone( tp.table.meta );

/**
 * Updates the screen settings state based on a patch.
 *
 * @param {Object}          state The current screen settings state.
 * @param {Object|Function} patch The patch to apply to the state.
 * @return {Object} The updated screen settings state.
 */
const tableMetaReducer = ( state, patch ) => {
	const nextPatch = 'function' === typeof patch ? patch( state ) : patch;
	return { ...state, ...nextPatch };
};

/**
 * Returns the TableMetaProvider component's JSX markup.
 *
 * @param {Object} props          Function parameters.
 * @param {Object} props.children Children of the component.
 * @return {Object} TableMetaProvider component.
 */
export const TableMetaProvider = ( { children } ) => {
	const [ state, dispatch ] = useReducer( tableMetaReducer, initialTableMeta );

	/**
	 * Dispatches a patch to update the table meta state and marks the table as having unsaved changes.
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
		<TableMetaContext.Provider value={ state }>
			<TableMetaDispatchContext.Provider value={ dispatchWithExtras }>
				{ children }
			</TableMetaDispatchContext.Provider>
		</TableMetaContext.Provider>
	);
};

/**
 * Returns the TableMeta context.
 *
 * @return {Object} TableMeta context.
 */
export const useTableMeta = () => {
	const context = useContext( TableMetaContext );
	if ( ! context ) {
		throw new Error( 'useTableMeta must be used within a TableMetaProvider.' );
	}
	return context;
};

/**
 * Returns the TableMetaDispatch context.
 *
 * @return {Function} TableMetaDispatch context.
 */
export const useUpdateTableMeta = () => {
	const dispatch = useContext( TableMetaDispatchContext );
	if ( ! dispatch ) {
		throw new Error( 'useUpdateTableMeta must be used within a TableMetaProvider.' );
	}
	return dispatch;
};
