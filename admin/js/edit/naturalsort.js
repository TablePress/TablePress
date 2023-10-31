/**
 * Definition of the "natural sorting" algorithm for the "Edit" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/**
 * Natural Sort algorithm for Javascript - Version 0.8.1 - Released under MIT license
 *
 * @author Jim Palmer (based on chunking idea from Dave Koelle)
 *
 * @see https://github.com/overset/javascript-natural-sort
 * @see https://overset.com/2008/09/01/javascript-natural-sort-algorithm-with-unicode-support/
 *
 * @param {string} a First string to compare.
 * @param {string} b Second string to compare.
 * @return {number} Integer -1, 0, or 1, depending on whether a or b are "bigger".
 */
const naturalSort = ( a, b ) => {
	const re = /(^([+\-]?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?(?=\D|\s|$))|^0x[\da-fA-F]+$|\d+)/g,
		sre = /^\s+|\s+$/g,   // Trim pre-post whitespace.
		snre = /\s+/g,        // Normalize all whitespace to single ' ' character.
		dre = /(^([\w ]+,?[\w ]+)?[\w ]+,?[\w ]+\d+:\d+(:\d+)?[\w ]?|^\d{1,4}[\/\-]\d{1,4}[\/\-]\d{1,4}|^\w+, \w+ \d+, \d{4})/,
		hre = /^0x[0-9a-f]+$/i,
		ore = /^0/,
		// Strip whitespace.
		x = a.replace( sre, '' ) || '',
		y = b.replace( sre, '' ) || '',
		// Chunk/tokenize.
		xN = x.replace( re, '\0$1\0' ).replace( /\0$/,'' ).replace( /^\0/,'' ).split( '\0' ),
		yN = y.replace( re, '\0$1\0' ).replace( /\0$/,'' ).replace( /^\0/,'' ).split( '\0' ), // eslint-disable-line @wordpress/no-unused-vars-before-return
		// Numeric, Hex, or Date detection.
		xD = parseInt( x.match( hre ), 16 ) || ( xN.length !== 1 && Date.parse( x ) ),
		yD = parseInt( y.match( hre ), 16 ) || xD && y.match( dre ) && Date.parse( y ) || null,
		normChunk = ( s, l ) => {
			// Normalize spaces; find floats not starting with '0', string or 0 if not defined (Clint Priest).
			return ( ! s.match( ore ) || l === 1 ) && parseFloat( s ) || s.replace( snre, ' ' ).replace( sre, '' ) || 0;
		};
	// First, try and sort Hex codes or Dates.
	if ( yD ) {
		if ( xD < yD ) {
			return -1;
		} else if ( xD > yD ) {
			return 1;
		}
	}
	// Natural sorting through split numeric strings and default strings.
	for ( let cLoc = 0, xNl = xN.length, yNl = yN.length, numS = Math.max( xNl, yNl ); cLoc < numS; cLoc++ ) {
		const oFxNcL = normChunk( xN[cLoc] || '', xNl );
		const oFyNcL = normChunk( yN[cLoc] || '', yNl );
		// Handle numeric vs string comparison - number < string - (Kyle Adams).
		if ( isNaN( oFxNcL ) !== isNaN( oFyNcL ) ) {
			return isNaN( oFxNcL ) ? 1 : -1;
		}
		// If unicode use locale comparison.
		if ( /[^\x00-\x80]/.test( oFxNcL + oFyNcL ) && oFxNcL.localeCompare ) {
			const comp = oFxNcL.localeCompare( oFyNcL );
			return comp / Math.abs( comp );
		}
		if ( oFxNcL < oFyNcL ) {
			return -1;
		} else if ( oFxNcL > oFyNcL ) {
			return 1;
		}
	}

	return 0;
};

export default naturalSort;
