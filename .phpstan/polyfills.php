<?php
/**
 * Functions that are not available in older PHP versions, but that WordPress offers as polyfills.
 */

function mb_substr( $string, $start, $length = null, $encoding = null ) {}
function mb_strlen( $string, $encoding = null ) {}
function array_is_list( $arr ) {}
function str_contains( $haystack, $needle ) {}
function str_starts_with( $haystack, $needle ) {}
function str_ends_with( $haystack, $needle ) {}

/* Once WordPress 6.8 is the minimum required version, these functions are available in the WordPress core polyfills.
function array_find( array $array, callable $callback ) {}
function array_find_key( array $array, callable $callback ) {}
function array_any( array $array, callable $callback ): bool {}
function array_all( array $array, callable $callback ): bool {}
*/

/* Once WordPress 6.9 is the minimum required version, these functions are available in the WordPress core polyfills.
function array_first( array $array ) {}
function array_last( array $array ) {}
*/

/* Once WordPress 7.1 is the minimum required version, these functions are available in the WordPress core polyfills.
function clamp( $value, $min, $max ) {}
*/
