/**
 * JavaScript code for the "Edit Screen" settings context.
 *
 * @package TablePress
 * @subpackage Edit Screen
 * @author Tobias Bäthge
 * @since 3.5.0
 */

/**
 * WordPress dependencies.
 */
import { createContext, useContext, useReducer } from 'react';

const ScreenSettingsContext = createContext();
const ScreenSettingsDispatchContext = createContext();

// For debugging in React DevTools, set display names for the contexts.
// ScreenSettingsContext.displayName = 'ScreenSettingsContext';
// ScreenSettingsDispatchContext.displayName = 'ScreenSettingsDispatchContext';

const initialScreenSettings = {
	copyUrl: tp.screenOptions.copyUrl,
	deleteUrl: tp.screenOptions.deleteUrl,
	exportUrl: tp.screenOptions.exportUrl,
	fullscreenIsActive: false,
	isSaving: false,
	previewIsLoading: false,
	previewIsOpen: false,
	previewSrcDoc: '',
	previewUrl: tp.screenOptions.previewUrl,
};

/**
 * Updates the screen settings state based on a patch.
 *
 * @param {Object}          state The current screen settings state.
 * @param {Object|Function} patch The patch to apply to the state.
 * @return {Object} The updated screen settings state.
 */
const screenSettingsReducer = ( state, patch ) => {
	const nextPatch = 'function' === typeof patch ? patch( state ) : patch;
	return { ...state, ...nextPatch };
};

/**
 * Returns the ScreenSettingsProvider component's JSX markup.
 *
 * @param {Object} props          Function parameters.
 * @param {Object} props.children Children of the component.
 * @return {Object} ScreenSettingsProvider component.
 */
export const ScreenSettingsProvider = ( { children } ) => {
	const [ state, dispatch ] = useReducer( screenSettingsReducer, initialScreenSettings );

	return (
		<ScreenSettingsContext.Provider value={ state }>
			<ScreenSettingsDispatchContext.Provider value={ dispatch }>
				{ children }
			</ScreenSettingsDispatchContext.Provider>
		</ScreenSettingsContext.Provider>
	);
};

/**
 * Returns the ScreenSettings context.
 *
 * @return {Object} ScreenSettings context.
 */
export const useScreenSettings = () => {
	const context = useContext( ScreenSettingsContext );
	if ( ! context ) {
		throw new Error( 'useScreenSettings must be used within a ScreenSettingsProvider.' );
	}
	return context;
};

/**
 * Returns the ScreenSettingsDispatch context.
 *
 * @return {Function} ScreenSettingsDispatch context.
 */
export const useUpdateScreenSettings = () => {
	const dispatch = useContext( ScreenSettingsDispatchContext );
	if ( ! dispatch ) {
		throw new Error( 'useUpdateScreenSettings must be used within a ScreenSettingsProvider.' );
	}
	return dispatch;
};
