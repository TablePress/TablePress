/**
 * Definition of the "natural sorting" algorithm for the "Edit" screen.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/*
 * Natural Sort algorithm for Javascript - Version 0.8.1 - Released under MIT license
 * Author: Jim Palmer (based on chunking idea from Dave Koelle)
 * See: https://github.com/overset/javascript-natural-sort and https://overset.com/2008/09/01/javascript-natural-sort-algorithm-with-unicode-support/
 */
export default function naturalSort( a, b ) {
	const re = /(^([+\-]?\d+(?:\.\d*)?(?:[eE][+\-]?\d+)?(?=\D|\s|$))|^0x[\da-fA-F]+$|\d+)/g,
		sre = /^\s+|\s+$/g,   // trim pre-post whitespace
		snre = /\s+/g,        // normalize all whitespace to single ' ' character
		dre = /(^([\w ]+,?[\w ]+)?[\w ]+,?[\w ]+\d+:\d+(:\d+)?[\w ]?|^\d{1,4}[\/\-]\d{1,4}[\/\-]\d{1,4}|^\w+, \w+ \d+, \d{4})/,
		hre = /^0x[0-9a-f]+$/i,
		ore = /^0/,
		// strip whitespace
		x = a.replace(sre, '') || '',
		y = b.replace(sre, '') || '',
		// chunk/tokenize
		xN = x.replace(re, '\0$1\0').replace(/\0$/,'').replace(/^\0/,'').split('\0'),
		yN = y.replace(re, '\0$1\0').replace(/\0$/,'').replace(/^\0/,'').split('\0'), // eslint-disable-line @wordpress/no-unused-vars-before-return
		// numeric, hex or date detection
		xD = parseInt(x.match(hre), 16) || (xN.length !== 1 && Date.parse(x)),
		yD = parseInt(y.match(hre), 16) || xD && y.match(dre) && Date.parse(y) || null,
		normChunk = function(s, l) {
			// normalize spaces; find floats not starting with '0', string or 0 if not defined (Clint Priest)
			return (!s.match(ore) || l === 1) && parseFloat(s) || s.replace(snre, ' ').replace(sre, '') || 0;
		};
	// first try and sort Hex codes or Dates
	if (yD) {
		if (xD < yD) { return -1; }
		else if (xD > yD) { return 1; }
	}
	// natural sorting through split numeric strings and default strings
	for(let cLoc = 0, xNl = xN.length, yNl = yN.length, numS = Math.max(xNl, yNl); cLoc < numS; cLoc++) {
		const oFxNcL = normChunk(xN[cLoc] || '', xNl);
		const oFyNcL = normChunk(yN[cLoc] || '', yNl);
		// handle numeric vs string comparison - number < string - (Kyle Adams)
		if (isNaN(oFxNcL) !== isNaN(oFyNcL)) {
			return isNaN(oFxNcL) ? 1 : -1;
		}
		// if unicode use locale comparison
		if (/[^\x00-\x80]/.test(oFxNcL + oFyNcL) && oFxNcL.localeCompare) {
			const comp = oFxNcL.localeCompare(oFyNcL);
			return comp / Math.abs(comp);
		}
		if (oFxNcL < oFyNcL) { return -1; }
		else if (oFxNcL > oFyNcL) { return 1; }
	}
}
