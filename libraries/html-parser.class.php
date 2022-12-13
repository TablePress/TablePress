<?php
/**
 * HTML Parsing class for TablePress, used for import of HTML files.
 *
 * @package TablePress
 * @subpackage Import
 * @author Tobias Bäthge
 * @since 2.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * HTML Parsing class
 *
 * @package TablePress
 * @subpackage Import
 * @author Tobias Bäthge
 * @since 2.0.0
 */
abstract class HTML_Parser {

	/**
	 * Parses HTML string into a two-dimensional array, maybe with options.
	 *
	 * @since 2.0.0
	 *
	 * @param string $html Data to be parsed.
	 * @return array|WP_Error Array with table data and options (current table head and foot row) on success, WP_Error on error.
	 */
	public static function parse( $html ) {
		if ( false === stripos( $html, '<table' ) || false === stripos( $html, '</table>' ) ) {
			return new WP_Error( 'table_import_html_no_table_found' );
		}

		// Prepend XML declaration, for better encoding support.
		$full_html = '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>' . $html;
		if ( function_exists( 'libxml_disable_entity_loader' ) ) {
			/*
			 * Don't expand external entities, see https://websec.io/2012/08/27/Preventing-XXE-in-PHP.html.
			 * Silence warnings as the function is deprecated in PHP 8, but can be necessary with LIBXML_NOENT being defined, see https://core.trac.wordpress.org/changeset/50714.
			 */
			@libxml_disable_entity_loader( true ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		}
		// No warnings/errors raised, but stored internally.
		libxml_use_internal_errors( true );
		$dom = new DOMDocument( '1.0', 'UTF-8' );
		// No strict checking for invalid HTML.
		$dom->strictErrorChecking = false; // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
		$dom->loadHTML( $full_html );
		if ( false === $dom ) {
			return new WP_Error( 'table_import_html_dom_load_html_failed' );
		}
		$dom_tables = $dom->getElementsByTagName( 'table' );
		if ( 0 === count( $dom_tables ) ) {
			return new WP_Error( 'table_import_html_dom_get_tables' );
		}
		libxml_clear_errors(); // Clear errors so that we only catch those inside the table in the next line.
		$table = simplexml_import_dom( $dom_tables->item( 0 ) );
		if ( false === $table ) {
			return new WP_Error( 'table_import_html_simplexml_import_dom_failed' );
		}

		$errors = libxml_get_errors();
		libxml_clear_errors();
		if ( ! empty( $errors ) ) {
			$output = '<strong>' . __( 'The imported file contains errors:', 'tablepress' ) . '</strong><br /><br />';
			foreach ( $errors as $error ) {
				switch ( $error->level ) {
					case LIBXML_ERR_WARNING:
						$output .= "Warning {$error->code}: {$error->message} in line {$error->line}, column {$error->column}<br />";
						break;
					case LIBXML_ERR_ERROR:
						$output .= "Error {$error->code}: {$error->message} in line {$error->line}, column {$error->column}<br />";
						break;
					case LIBXML_ERR_FATAL:
						$output .= "Fatal Error {$error->code}: {$error->message} in line {$error->line}, column {$error->column}<br />";
						break;
				}
			}
			wp_die( $output, 'Import Error', array( 'response' => 200, 'back_link' => true ) );
		}

		$html_table = array(
			'data'    => array(),
			'options' => array(),
		);
		if ( isset( $table->thead ) ) {
			$html_table['data'] = array_merge( $html_table['data'], self::_import_html_rows( $table->thead[0]->tr ) );
			$html_table['options']['table_head'] = true;
		}
		if ( isset( $table->tbody ) ) {
			$html_table['data'] = array_merge( $html_table['data'], self::_import_html_rows( $table->tbody[0]->tr ) );
		}
		if ( isset( $table->tr ) ) {
			$html_table['data'] = array_merge( $html_table['data'], self::_import_html_rows( $table->tr ) );
		}
		if ( isset( $table->tfoot ) ) {
			$html_table['data'] = array_merge( $html_table['data'], self::_import_html_rows( $table->tfoot[0]->tr ) );
			$html_table['options']['table_foot'] = true;
		}

		return $html_table;
	}

	/**
	 * Converts table HTML rows to an array.
	 *
	 * @since 2.0.0
	 *
	 * @param SimpleXMLElement $element XMLElement.
	 * @return array SimpleXMLElement exported to an array.
	 */
	protected static function _import_html_rows( $element ) {
		$rows = array(); // Container for the table data.
		$rowspans = array(); // Container for information about rowspans in rows that follow the currently processed row.

		$row_idx = 0;
		foreach ( $element as $row ) {
			// If all cells in a row should be merged with the cells in the row above, add the trigger word to each of them (should be very rare).
			while ( isset( $rowspans[ $row_idx ] ) && count( $rowspans[ $row_idx ] ) === count( $rows[ $row_idx - 1 ] ) ) {
				$rows[] = $rowspans[ $row_idx ];
				++$row_idx;
			}

			$new_row = array();
			$column_idx = 0;
			foreach ( $row as $cell ) {
				// If a cell in a row should be merged with the cell above it, add the trigger word to it.
				while ( isset( $rowspans[ $row_idx ][ $column_idx ] ) ) {
					$new_row[] = $rowspans[ $row_idx ][ $column_idx ];
					++$column_idx;
				}

				$cell_xml = $cell->asXml();

				// Get content between <td>...</td>, or <th>...</th>, possibly with HTML.
				if ( 1 === preg_match( '#<t[d|h].*?>(.*)</t[d|h]>#is', $cell_xml, $matches ) ) {
					/*
					 * Decode HTML entities again, as there might be some left especially in attributes of HTML tags in the cells,
					 * see https://secure.php.net/manual/en/simplexmlelement.asxml.php#107137.
					 */
					$new_row[] = html_entity_decode( $matches[1], ENT_NOQUOTES, 'UTF-8' );

					// Search for colspan and rowspan attributes in the cell's HTML tag.
					$colspan = 1;
					$rowspan = 1;
					if ( 1 === preg_match( '#<t[d|h].*colspan=["\']?(\d+)["\']?.*?>#is', $cell_xml, $matches ) ) {
						$colspan = (int) $matches[1];
					}
					if ( 1 === preg_match( '#<t[d|h].*rowspan=["\']?(\d+)["\']?.*?>#is', $cell_xml, $matches ) ) {
						$rowspan = (int) $matches[1];
					}

					// Add cells with the colspan trigger word, if merged cells across columns were found.
					for ( $i = 1; $i < $colspan; $i++ ) {
						$new_row[] = '#colspan#';
					}

					// If merged cells across rows were found, add trigger words to a temporary variable.
					for ( $i = 1; $i < $rowspan; $i++ ) {
						if ( ! isset( $rowspans[ $row_idx + $i ] ) ) {
							$rowspans[ $row_idx + $i ] = array();
						}
						$rowspans[ $row_idx + $i ][ $column_idx ] = '#rowspan#';
						for ( $j = 1; $j < $colspan; $j++ ) {
							$rowspans[ $row_idx + $i ][ $column_idx + $j ] = '#span#';
						}
					}
				} else {
					// Add an empty cell if no content could be extracted from the cell's HTML tag.
					$new_row[] = '';
				}

				++$column_idx;
			}

			// After the last cell in a row: If a cell in a row should be merged with the cell above it, add the trigger word to it.
			while ( isset( $rowspans[ $row_idx ][ $column_idx ] ) ) {
				$new_row[] = $rowspans[ $row_idx ][ $column_idx ];
				++$column_idx;
			}

			$rows[] = $new_row;
			++$row_idx;
		}

		// After the last data row: If all cells in a row should be merged with the cells in the row above, add the trigger word to each of them (should be very rare).
		while ( isset( $rowspans[ $row_idx ] ) && count( $rowspans[ $row_idx ] ) === count( $rows[ $row_idx - 1 ] ) ) {
			$rows[] = $rowspans[ $row_idx ];
			++$row_idx;
		}

		return $rows;
	}

} // class HTML_Parser
