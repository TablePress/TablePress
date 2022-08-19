/**
 * Common functions that are used in TablePress JS.
 *
 * @package TablePress
 * @subpackage Views JavaScript
 * @author Tobias Bäthge
 * @since 2.0.0
 */

/**
 * Alias for document.getElementById and document.querySelectorAll, depending on the first character of the passed selector string. Resembles jQuery.
 *
 * @param {string} selector Selector string. If it starts with #, a single ID is selected, all matching selectors otherwise.
 * @return {Element|NodeList} A single DOM Element or a DOM NodeList matching the selector.
 */
export const $ = ( selector ) => ( '#' === selector[0] ? document.getElementById( selector.slice( 1 ) ) : document.querySelectorAll( selector ) );
