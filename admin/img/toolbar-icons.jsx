/**
 * JavaScript code for the icons in the toolbar on the "Edit" screen.
 *
 * @package TablePress
 * @subpackage Images
 * @author Tobias Bäthge
 * @since 3.4.0
 */

/**
 * WordPress dependencies.
 */
import { SVG, Path } from '@wordpress/primitives';

export const cellsMerge = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-3 -3 24 24" overflow="visible" fill="none" stroke="currentColor" strokeWidth="1.5" strokeMiterlimit="10">
		<Path d="M6.25 17.25V.75M.75 2v14m0-4.25h5.5m0-5.5h11" fill="none"/>
		<Path d="M.75 6.25h5.5m5.5 0V.75M17.25 2v14" fill="none"/>
		<Path d="M16 17.25A1.25 1.25 0 0 0 17.25 16V2A1.25 1.25 0 0 0 16 .75H2A1.25 1.25 0 0 0 .75 2v14A1.25 1.25 0 0 0 2 17.25h14z" fill="none"/>
	</SVG>
);

export const cut = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-2.722 -2 24 24" overflow="visible">
		<Path d="M3.833 18.75A3.84 3.84 0 0 1 0 14.917a3.84 3.84 0 0 1 3.833-3.834 3.84 3.84 0 0 1 3.833 3.834 3.84 3.84 0 0 1-3.833 3.833zm0-6.167c-1.287 0-2.333 1.047-2.333 2.334s1.047 2.333 2.333 2.333 2.333-1.047 2.333-2.333-1.046-2.334-2.333-2.334z"/>
		<Path d="M8.629 11.276l1.299.75-2.776 4.807-1.299-.75zm2.261 3.641a3.84 3.84 0 0 1 3.834-3.834 3.84 3.84 0 0 1 3.833 3.834 3.84 3.84 0 0 1-3.833 3.833 3.84 3.84 0 0 1-3.834-3.833zm1.5 0c0 1.286 1.047 2.333 2.334 2.333s2.333-1.047 2.333-2.333a2.34 2.34 0 0 0-2.333-2.334 2.34 2.34 0 0 0-2.334 2.334z"/>
		<Path d="M12.703 16.083l-1.299.75-2.776-4.807 1.299-.75z"/>
		<Path d="M14.85 0v4l-4.705 8.15-1.733-1z"/>
		<Path d="M3.707 0v4l4.705 8.15 1.733-1z"/>
	</SVG>
);

export const formatting = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-2.15 -4 24 24" overflow="visible">
		<Path d="M0 14.598L4.45 2.275l1.884.05 4.379 12.27H8.864l-1.08-3.17-4.785-.05-1.22 3.27L0 14.598zm3.469-4.683H7.13L5.3 4.668 3.469 9.915zm16.848 3.655l-.378.27c-.286.199-.934.306-.934-.736l-.019-5.66c0-1.24-.359-2.605-2.03-2.911s-2.965.108-3.54.396-1.491 1.042-1.258 1.904.772.971 1.204.971 1.168-.252 1.168-1.186c0-.791-.719-1.114-.719-1.114.126-.323 1.006-.683 1.743-.611.935.091 1.401.791 1.401 1.563v1.581c0 .629-1.33 1.097-2.462 1.42-.837.239-1.936.711-2.443 1.474-.575.862-.521 2.677.719 3.324.989.515 2.071.412 2.893-.019.576-.302.972-.766 1.312-1.239 0 0-.035 1.275.988 1.509 1.254.286 2.156-.27 2.57-.61l-.215-.326zm-3.36-1.401c-.216.449-.898 1.51-1.851 1.51s-1.438-.772-1.438-1.617.557-1.6 1.474-2.066 1.635-.755 1.814-1.024v3.197z"/>
	</SVG>
);

export const insertColumn = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-4 -4 24 24" overflow="visible" fill="currentColor">
		<Path d="M14.5 0H16v16h-1.5zM0 0h1.5v16H0zm12.03 1.061l-3.5 3.5-.53.53-.53-.53-3.5-3.5L5.03 0 8 2.97 10.97 0z"/>
	</SVG>
);

export const insertColumnBefore = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-0.65 -4 24 24" overflow="visible" fill="currentColor">
		<Path d="M10.6 0h1.5v16h-1.5zM7 0L4 3 1 0 0 1l4 4 4-4z"/>
	</SVG>
);

export const insertColumnAfter = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-4 -4 24 24" overflow="visible" fill="currentColor">
		<Path d="M7.25 0h1.5v16h-1.5zm4.1 1l4 4 4-4-1-1-3 3-3-3z"/>
	</SVG>
);

export const insertRow = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-4 -4 24 24" overflow="visible" fill="currentColor">
		<Path d="M0 0h16v1.5H0zm0 14.5h16V16H0zM1.061 3.97l3.5 3.5.53.53-.53.53-3.5 3.5L0 10.97 2.97 8 0 5.03z"/>
	</SVG>
);

export const insertRowBefore = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-4 -4 24 24" overflow="visible" fill="currentColor">
		<Path d="M0 12.05h16v1.5H0zm1-2.6l4-4-4-4-1 1 3 3-3 3z"/>
	</SVG>
);

export const insertRowAfter = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-4 -4 24 24" overflow="visible" fill="currentColor">
		<Path d="M0 2.45h16v1.5H0zm0 5.1l3 3-3 3 1 1 4-4-4-4z"/>
	</SVG>
);

export const moveFirst = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-3.599 -4 24 24" overflow="visible" fill="none" stroke="currentColor">
		<Path d="M10.514 2.5l-5 5.5 5 5.5m-4.5-11l-5 5.5 5 5.5" strokeWidth="1.5" fill="none"/>
		<Path strokeWidth="1.6" d="M5.514 8h10.887" fill="none"/>
	</SVG>
);

export const moveLast = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-4 -4 24 24" overflow="visible" fill="none" stroke="currentColor">
		<Path d="M5.888 13.5l5-5.5-5-5.5m4.5 11l5-5.5-5-5.5" strokeWidth="1.5" fill="none"/>
		<Path strokeWidth="1.6" d="M10.888 8H0" fill="none"/>
	</SVG>
);

export const moveLeftRight = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-3.9 -3.843 24 24" overflow="visible" fill="currentColor">
		<Path d="M4.725 9.281l1.008-1.008L2.899 5.44h13.2v-1.6h-13.2l2.833-2.833L4.725 0 0 4.641l4.725 4.64zm6.75-2.248L10.467 8.04l2.833 2.833H.1v1.6h13.2l-2.833 2.834 1.008 1.008 4.725-4.641-4.725-4.641z"/>
	</SVG>
);

export const moveTop = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-4 -3.599 24 24" overflow="visible" fill="none" stroke="currentColor">
		<Path d="M13.5 10.514l-5.5-5-5.5 5m11-4.5l-5.5-5-5.5 5" strokeWidth="1.5" fill="none"/>
		<Path strokeWidth="1.6" d="M8 5.514v10.887" fill="none"/>
	</SVG>
);

export const moveBottom = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-4 -4 24 24" overflow="visible" fill="none" stroke="currentColor">
		<Path d="M2.5 5.888l5.5 5 5.5-5m-11 4.5l5.5 5 5.5-5" strokeWidth="1.5" fill="none"/>
		<Path strokeWidth="1.6" d="M8 10.888V0" fill="none"/>
	</SVG>
);

export const moveUpDown = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-3.843 -3.9 24 24" overflow="visible" fill="currentColor">
		<Path d="M4.641 0L0 4.725l1.008 1.008L3.841 2.9v13.2h1.6V2.9l2.833 2.833 1.008-1.008L4.641 0zm7.033 16.199l4.641-4.725-1.008-1.008-2.834 2.834V.1h-1.6v13.2L8.04 10.467l-1.007 1.008 4.641 4.724z"/>
	</SVG>
);

export const paste = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-3.25 -1.75 24 24" overflow="visible">
		<Path d="M11.75 3.75h3.75a.5.5 0 0 1 .5.5v13.5a.5.5 0 0 1-.5.5H2a.5.5 0 0 1-.5-.5V4.25a.5.5 0 0 1 .5-.5h3.75v-1.5H2a2 2 0 0 0-2 2v13.5a2 2 0 0 0 2 2h13.5a2 2 0 0 0 2-2V4.25a2 2 0 0 0-2-2h-3.75v1.5z"/>
		<Path d="M11.25 0h-5a2 2 0 0 0-2 2v4h9V2a2 2 0 0 0-2-2zm-2.5 4.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 1 1 0 3z"/>
	</SVG>
);

export const row = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-3.5 -5.5 24 24" overflow="visible">
		<Path d="M15 0H2C.9 0 0 .9 0 2v9c0 1.1.9 2 2 2h13c1.1 0 2-.9 2-2V2c0-1.1-.9-2-2-2zm.5 11a.47.47 0 0 1-.5.5H2a.47.47 0 0 1-.5-.5V9h14v2zm0-7h-14V2a.47.47 0 0 1 .5-.5h13a.47.47 0 0 1 .5.5v2z"/>
	</SVG>
);

export const sort = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-4 -2.4 24 24" overflow="visible">
		<Path fill="none" stroke="currentColor" strokeWidth="1.5" strokeMiterlimit="10" d="M8 1.5l3.521 6.1H4.479zm0 16.2l-3.521-6.1h7.042z"/>
	</SVG>
);

export const sortAsc = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-4 -4 24 24" overflow="visible">
		<Path fill="none" stroke="currentColor" strokeWidth="1.5" strokeMiterlimit="10" d="M8 2.325l5.976 10.35H2.024z"/>
	</SVG>
);

export const sortDesc = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-4 -4 24 24" overflow="visible">
		<Path fill="none" stroke="currentColor" strokeWidth="1.5" strokeMiterlimit="10" d="M8 13.675L2.024 3.325h11.952z"/>
	</SVG>
);
