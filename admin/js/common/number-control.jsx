/**
 * JavaScript code for the "NumberControl" component, a custom number input field with increment and decrement buttons.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 3.5.0
 */

/**
 * WordPress dependencies.
 */
import { useEffect, useRef } from 'react';
import { plus as incrementIcon, reset as decrementIcon } from '@wordpress/icons';
import { __ } from '@wordpress/i18n';
import {
	// IconButton, // Due to compatibility issues with WP < 7.0, we use a custom `IconButton` component instead of the one from the `@wordpress/ui` package.
	InputControl,
	InputLayout,
	Stack,
} from '@wordpress/ui';

/**
 * Internal dependencies.
 */
import { IconButton } from './icon-button';

/**
 * Returns the number of decimal places in a given value.
 *
 * @param {number|string} val Value to check.
 * @return {number} Number of decimal places.
 */
const getDecimals = ( val ) => {
	const str = String( val );
	if ( str.includes( 'e-' ) ) {
		return parseInt( str.split( 'e-' )[ 1 ], 10 );
	}
	const parts = str.split( '.' );
	return parts.length > 1 ? parts[ 1 ].length : 0;
};

/**
 * Constrains a value to be within a specified range.
 *
 * @param {number|string} value The value to constrain.
 * @param {number}        min   The minimum allowed value.
 * @param {number}        max   The maximum allowed value.
 * @param {number}        step  The step size for alignment.
 * @return {string} The constrained value.
 */
const constrainValue = ( value, min, max, step ) => {
	const num = parseFloat( value );
	if ( Number.isNaN( num ) ) {
		return String( min );
	}

	const clamped = Math.min( max, Math.max( min, num ) );
	if ( step === 'any' ) {
		return String( clamped );
	}

	const alignMin = isFinite( min ) ? min : 0;
	const stepNum = parseFloat( step );
	const aligned = alignMin + Math.round( ( clamped - alignMin ) / stepNum ) * stepNum;

	const precision = Math.max( getDecimals( min ), getDecimals( max ), getDecimals( step ) );
	return String( Number( aligned.toFixed( precision ) ) );
};

/**
 * Returns the NumberControl component's JSX markup.
 *
 * The component is a custom number input field with increment and decrement buttons.
 *
 * @param {Object}        props          Component props.
 * @param {string|number} props.value    Current numerical value.
 * @param {Function}      props.onChange Callback function triggered on value change.
 * @param {number}        props.min      Minimum allowed value.
 * @param {number}        props.max      Maximum allowed value.
 * @param {number}        props.step     Increment and decrement step amount.
 * @param {boolean}       props.required Whether the input is required.
 * @param {boolean}       props.disabled Whether the input is disabled.
 * @param {number}        props.digits   Number of digits to display in the input field.
 * @return {Object} The rendered NumberControl component.
 */
export const NumberControl = ( {
	value = 0,
	onChange,
	min = -Infinity,
	max = Infinity,
	step = 1,
	required = false,
	disabled = false,
	digits,
	...props
} ) => {
	const numberInputRef = useRef( null );

	const numericValue = typeof value === 'number' ? value : parseFloat( value ) || 0;

	/**
	 * Handles the decrement action.
	 *
	 * When the Shift key is held, the decrement step is multiplied by 10.
	 * When the Ctrl or Meta key is held, the value is set to the minimum (if it is finite).
	 *
	 * @param {Event} event The event object.
	 */
	const handleDecrement = ( event ) => {
		const decrementStep = event.shiftKey ? step * 10 : step;
		const newValue = ( ( event.ctrlKey || event.metaKey ) && isFinite( min ) ) ? min : numericValue - decrementStep;
		const constrainedValue = constrainValue( newValue, min, max, step );
		onChange( constrainedValue );
	};

	/**
	 * Handles the increment action.
	 *
	 * When the Shift key is held, the increment step is multiplied by 10.
	 * When the Ctrl or Meta key is held, the value is set to the maximum (if it is finite).
	 *
	 * @param {Event} event The event object.
	 */
	const handleIncrement = ( event ) => {
		const incrementStep = event.shiftKey ? step * 10 : step;
		const newValue = ( ( event.ctrlKey || event.metaKey ) && isFinite( max ) ) ? max : numericValue + incrementStep;
		const constrainedValue = constrainValue( newValue, min, max, step );
		onChange( constrainedValue );
	};

	/**
	 * Handles the blur event.
	 *
	 * @param {Event} event The event object.
	 */
	const handleBlur = ( event ) => {
		const fieldValue = event.target.value.trim();

		if ( '' === fieldValue && ! required ) {
			onChange( fieldValue );
			return;
		}

		const newValue = constrainValue( fieldValue, min, max, step );
		onChange( newValue );
	};

	/**
	 * Handles the wheel event to prevent accidental value changes when scrolling.
	 *
	 * @param {Event} event The event object.
	 */
	const handleWheel = ( event ) => {
		if ( document.activeElement === event.target ) { // eslint-disable-line @wordpress/no-global-active-element
			event.preventDefault();
		}
	};

	/*
	 * Add a wheel event listener to the number input field to prevent accidental value changes when scrolling.
	 * This is done with a custom `addEventListener` call instead of the `onWheel` prop to avoid React's passive event listener behavior, which would prevent calling `event.preventDefault()`.
	 * A drawback of this approach is that scrolling the page with the mouse wheel while the number input is focused will not work.
	 */
	useEffect( () => {
		const numberInput = numberInputRef.current;
		numberInput.addEventListener( 'wheel', handleWheel, { passive: false } );

		return () => numberInput.removeEventListener( 'wheel', handleWheel );
	}, [] );

	return (
		<InputControl
			{ ...props }
			ref={ numberInputRef }
			type="number"
			disabled={ disabled }
			required={ required }
			value={ value }
			onValueChange={ onChange }
			onBlur={ handleBlur }
			onKeyDown={ ( event ) => {
				if ( event.key === 'ArrowDown' ) {
					event.preventDefault();
					handleDecrement( event );
				} else if ( event.key === 'ArrowUp' ) {
					event.preventDefault();
					handleIncrement( event );
				}
			} }
			style={ {
				width: digits ? `calc(${ parseInt( digits, 10 ) }ch + 74px)` : undefined,
				...props.style,
			} }
			suffix={
				<InputLayout.Slot padding="minimal">
					<Stack direction="row" gap="xs">
						<IconButton
							label={ __( 'Decrement', 'tablepress' ) }
							icon={ decrementIcon }
							onClick={ ( event ) => handleDecrement( event ) }
							size="small"
							variant="minimal"
							disabled={ disabled }
							focusableWhenDisabled={ false }
							tone="neutral"
						/>
						<IconButton
							label={ __( 'Increment', 'tablepress' ) }
							icon={ incrementIcon }
							onClick={ ( event ) => handleIncrement( event ) }
							size="small"
							variant="minimal"
							disabled={ disabled }
							focusableWhenDisabled={ false }
							tone="neutral"
						/>
					</Stack>
				</InputLayout.Slot>
			}
		/>
	);
}
