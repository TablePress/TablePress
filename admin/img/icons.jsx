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
import { Circle, Path, SVG } from '@wordpress/primitives';

export const TablePressIcon = (
	<SVG xmlns="http://www.w3.org/2000/svg" width="100" height="100" viewBox="-50 -50 100 100" fill="#ffffff">
		<Circle r="42" fill="#16a34a"/>
		<Path d="M0-25.854h-25.854v51.708h51.708V0H21v21h-42v-42H0Z"/>
		<Path d="M-18-18h10v10h-10zM-18-5h10V5h-10zM-5-5H5V5H-5zM-18 8h10v10h-10zM-5 8H5v10H-5zM8 8h10v10H8zM5-31h6.18v6.18H5zM19-25h6.18v6.18H19zM0-15h3.82v3.82H0zM10-20h3.82v3.82H10zM25-12h3.82v3.82H25zM8-13h10v10H8z"/>
	</SVG>
);

export const TablePressIconSimple = (
	<SVG xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="-32 -32 64 64" fill="currentColor">
		<Path d="M0-25.854h-25.854v51.708h51.708V0H21v21h-42v-42H0Z"/>
		<Path d="M-18-18h10v10h-10zM-18-5h10V5h-10zM-5-5H5V5H-5zM-18 8h10v10h-10zM-5 8H5v10H-5zM8 8h10v10H8zM5-31h6.18v6.18H5zM19-25h6.18v6.18H19zM0-15h3.82v3.82H0zM10-20h3.82v3.82H10zM25-12h3.82v3.82H25zM8-13h10v10H8z"/>
	</SVG>
);

export const cellsMerge = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
  		<Path d="M9.25 20.25V3.75m-5.5 11h5.5m5.5-5.5v-5.5M19 20.25A1.25 1.25 0 0 0 20.25 19V5A1.25 1.25 0 0 0 19 3.75H5A1.25 1.25 0 0 0 3.75 5v14A1.25 1.25 0 0 0 5 20.25h14zM3.75 9.25h16.5"/>
	</SVG>
);

export const cut = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
		<Path fill="currentColor" stroke="none" d="M11.856 13.4l4.966-8.601v1l-4.677 8.101z"/>
		<Path fill="currentColor" stroke="none" d="M12.145 13.4L7.179 4.799v1l4.677 8.101z"/>
		<Path d="M9.224 18.457L12 13.65M11.856 13.4l4.966-8.601v1l-4.677 8.101zM14.777 18.457L12 13.65M12.145 13.4L7.179 4.799v1l4.677 8.101z"/>
		<Circle cx="17.445" cy="16.916" r="3.084"/>
		<Circle cx="6.555" cy="16.916" r="3.084"/>
	</SVG>
);

export const formatting = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="-2.15 -4 24 24" overflow="visible">
		<Path d="M0 14.598L4.45 2.275l1.884.05 4.379 12.27H8.864l-1.08-3.17-4.785-.05-1.22 3.27L0 14.598zm3.469-4.683H7.13L5.3 4.668 3.469 9.915zm16.848 3.655l-.378.27c-.286.199-.934.306-.934-.736l-.019-5.66c0-1.24-.359-2.605-2.03-2.911s-2.965.108-3.54.396-1.491 1.042-1.258 1.904.772.971 1.204.971 1.168-.252 1.168-1.186c0-.791-.719-1.114-.719-1.114.126-.323 1.006-.683 1.743-.611.935.091 1.401.791 1.401 1.563v1.581c0 .629-1.33 1.097-2.462 1.42-.837.239-1.936.711-2.443 1.474-.575.862-.521 2.677.719 3.324.989.515 2.071.412 2.893-.019.576-.302.972-.766 1.312-1.239 0 0-.035 1.275.988 1.509 1.254.286 2.156-.27 2.57-.61l-.215-.326zm-3.36-1.401c-.216.449-.898 1.51-1.851 1.51s-1.438-.772-1.438-1.617.557-1.6 1.474-2.066 1.635-.755 1.814-1.024v3.197z"/>
	</SVG>
);

export const insertColumn = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
		<Path d="M19.25 4v16M4.75 4v16M15.499 4.529L12 8.03 8.5 4.531"/>
	</SVG>
);

export const insertColumnBefore = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
		<Path d="M12 4v16M1.251 4.529L4.75 8.03l3.5-3.499"/>
	</SVG>
);

export const insertColumnAfter = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
		<Path d="M12 4v16M22.749 4.529L19.25 8.03l-3.5-3.499"/>
	</SVG>
);

export const insertRow = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
  		<Path d="M4 4.75h16M4 19.25h16M4.529 8.501L8.03 12l-3.499 3.5"/>
	</SVG>
);

export const insertRowBefore = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
		<Path d="M4 16.8h16M4.529 6.051L8.03 9.55l-3.499 3.5"/>
	</SVG>
);

export const insertRowAfter = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
		<Path d="M4 7.2h16M4.529 17.949L8.03 14.45l-3.499-3.5"/>
	</SVG>
);

export const moveFirst = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
		<Path d="M14.113 6.5l-5 5.5 5 5.5m-4.5-11l-5 5.5 5 5.5M9.113 12h10.887"/>
	</SVG>
);

export const moveLast = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
		<Path d="M6.5 9.888l5.5 5 5.5-5m-11 4.5l5.5 5 5.5-5M12 14.888V4"/>
	</SVG>
);

export const moveLeftRight = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
		<Path d="M4.95 8.483H20M9.129 4.346L4.95 8.483l4.178 4.138m9.923 2.894H4M14.871 19.652l4.18-4.137-4.178-4.137"/>
	</SVG>
);

export const moveTop = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
		<Path d="M17.5 14.113l-5.5-5-5.5 5m11-4.5l-5.5-5-5.5 5M12 9.113V20"/>
	</SVG>
);

export const moveBottom = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
		<Path d="M6.5 9.888l5.5 5 5.5-5m-11 4.5l5.5 5 5.5-5M12 14.888V4"/>
	</SVG>
);

export const moveUpDown = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
		<Path d="M15.515 19.049V3.999m4.137 10.872-4.137 4.178-4.138-4.178M8.484 4.948l-.001 15.052M4.347 9.128l4.137-4.18 4.137 4.178"/>
	</SVG>
);

export const paste = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
		<Path fill="currentColor" stroke="none" d="M14.5 2.5h-5a1.25 1.25 0 0 0-1.25 1.25V7h7.5V3.75A1.25 1.25 0 0 0 14.5 2.5zM12 6.25a1.5 1.5 0 1 1 0-3 1.5 1.5 0 1 1 0 3z"/>
		<Path d="M9 4.75H5.25A1.25 1.25 0 0 0 4 6v13.5a1.25 1.25 0 0 0 1.25 1.25h13.5A1.25 1.25 0 0 0 20 19.5V6a1.25 1.25 0 0 0-1.25-1.25H15M8.25 7V3.75A1.25 1.25 0 0 1 9.5 2.5h5a1.25 1.25 0 0 1 1.25 1.25V7h-7.5z"/>
		<Circle cx="12" cy="4.75" r="2.25"/>
	</SVG>
);

export const row = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
		<Path fill="currentColor" stroke="none" d="M4.25 10.25h15.5v3.5H4.25z"/>
		<Path fill="none" d="M18.5 17.75A1.25 1.25 0 0 0 19.75 16.5V7.5A1.25 1.25 0 0 0 18.5 6.25H5.5A1.25 1.25 0 0 0 4.25 7.5v9A1.25 1.25 0 0 0 5.5 17.75h13zM4.25 10.25h15.5M4.25 13.75h15.5"/>
	</SVG>
);

export const sort = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
		<Path d="m12 3.9 3.522 6.1H8.479zm0 16.2-3.52-6.1h7.041z"/>
	</SVG>
);

export const sortAsc = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
		<Path d="m12 6.325 5.976 10.35H6.025z"/>
	</SVG>
);

export const sortDesc = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
		<Path d="M12 17.675 6.025 7.325h11.951z"/>
	</SVG>
);

export const jumpFirst = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
		<Path d="m17 15.401-5-4-5 4M17 10.599l-5-4-5 4"/>
	</SVG>
);

export const jumpLast = (
	<SVG xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" style={ { fill: 'none' } } stroke="currentColor" strokeWidth="1.5">
		<Path d="m7 8.599 5 4 5-4M7 13.401l5 4 5-4"/>
	</SVG>
);
