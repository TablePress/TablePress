<?php
/**
 * Frontend Template Tag functions, only available when the Frontend Controller is loaded.
 *
 * @package TablePress
 * @subpackage Frontend Template Tag functions
 * @author Tobias Bäthge
 * @since 1.0.0
 */

/**
 * Provides template tag functionality for the "table" Shortcode, to be used anywhere in the template, returns the table HTML.
 *
 * @since 1.0.0
 *
 * @param string|array<string, mixed> $table_query Query-string-like list or array of parameters for Shortcode "table" rendering.
 * @return string HTML of the rendered table.
 */
function tablepress_get_table( /* string|array */ $table_query ): string {
	if ( is_array( $table_query ) ) {
		$atts = $table_query;
	} else {
		parse_str( (string) $table_query, $atts );
	}
	return TablePress::$controller->shortcode_table( $atts ); // @phpstan-ignore argument.type
}

/**
 * Provides template tag functionality for the "table" Shortcode, to be used anywhere in the template, echoes the table HTML.
 *
 * @since 1.0.0
 *
 * @see tablepress_get_table()
 *
 * @param string|array<string, mixed> $table_query Query-string-like list or array of parameters for Shortcode "table" rendering.
 */
function tablepress_print_table( /* string|array */ $table_query ): void {
	echo tablepress_get_table( $table_query );
}

/**
 * Provides template tag functionality for the "table-info" Shortcode, to be used anywhere in the template, returns the info.
 *
 * @since 1.0.0
 *
 * @param string|array<string, mixed> $table_query Query-string-like list or array of parameters for Shortcode "table-info" rendering.
 * @return string Desired table information.
 */
function tablepress_get_table_info( /* string|array */ $table_query ): string {
	if ( is_array( $table_query ) ) {
		$atts = $table_query;
	} else {
		parse_str( (string) $table_query, $atts );
	}
	return TablePress::$controller->shortcode_table_info( $atts ); // @phpstan-ignore argument.type
}

/**
 * Provides template tag functionality for the "table-info" Shortcode, to be used anywhere in the template, echoes the info.
 *
 * @since 1.0.0
 *
 * @see tablepress_get_table_info()
 *
 * @param string|array<string, mixed> $table_query Query-string-like list or array of parameters for Shortcode "table-info" rendering.
 */
function tablepress_print_table_info( /* string|array */ $table_query ): void {
	echo tablepress_get_table_info( $table_query );
}
