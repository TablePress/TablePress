<?php
/**
 * TablePress Table Import PHPSpreadsheet Class
 *
 * @package TablePress
 * @subpackage Export/Import
 * @author Tobias Bäthge
 * @since 2.0.0
 */

// Prohibit direct script loading.
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress Table Import PHPSpreadsheet Class
 *
 * @package TablePress
 * @subpackage Export/Import
 * @author Tobias Bäthge
 * @since 2.0.0
 */
class TablePress_Import_PHPSpreadsheet extends TablePress_Import_Base {

	/**
	 * Initializes the Import class.
	 *
	 * @since 2.0.0
	 */
	public function __construct() {
		// Load PHPSpreadsheet via the Composer autoloading mechanism.
		TablePress::load_file( 'autoload.php', 'libraries' );
	}

	/**
	 * Imports a table from a file.
	 *
	 * @since 2.0.0
	 *
	 * @param array $file File to import.
	 * @return array|WP_Error Table array on success, WP_Error on error.
	 */
	public function import_table( array $file ) {
		$data = file_get_contents( $file['location'] );
		if ( false === $data ) {
			return new WP_Error( 'table_import_phpspreadsheet_data_read', '', $file['location'] );
		}

		// Remove a possible UTF-8 Byte-Order Mark (BOM).
		$bom = pack( 'CCC', 0xef, 0xbb, 0xbf );
		if ( 0 === strncmp( $data, $bom, 3 ) ) {
			$data = substr( $data, 3 );
		}

		if ( '' === $data ) {
			return new WP_Error( 'table_import_phpspreadsheet_data_empty', '', $file['location'] );
		}

		$table = $this->_maybe_import_json( $data );
		if ( false !== $table ) {
			return $table;
		}

		$table = $this->_maybe_import_html( $data );
		if ( false !== $table ) {
			return $table;
		}

		return $this->_import_phpspreadsheet( $file );
	}

	/**
	 * Tries to import a table with the JSON format.
	 *
	 * @since 2.0.0
	 *
	 * @param string $data Data to import.
	 * @return array|WP_Error|false Table array on success, WP_Error on error, false if the file is not a JSON file.
	 */
	protected function _maybe_import_json( $data ) {
		// If the first non-whitespace character is not a { or [, the file is not a supported JSON file.
		$data = ltrim( $data );
		$first_character = $data[0];
		if ( '{' !== $first_character && '[' !== $first_character ) {
			return false;
		}

		$json_table = json_decode( $data, true );

		// Check if JSON could be decoded. If not, this is probably not a JSON file.
		if ( is_null( $json_table ) ) {
			return false;
		}

		// Specifically cast to an array again.
		$json_table = (array) $json_table;

		if ( isset( $json_table['data'] ) ) {
			// JSON data contained a full export.
			$table = $json_table;
		} else {
			// JSON data contained only the data of a table, but no options.
			$table = array( 'data' => array() );
			foreach ( $json_table as $row ) {
				// Turn row into indexed arrays with numeric keys.
				$row = array_values( (array) $row );

				// Remove entries of multi-dimensional arrays.
				foreach ( $row as &$cell ) {
					if ( is_array( $cell ) ) {
						$cell = '';
					}
				}
				unset( $cell ); // Unset use-by-reference parameter of foreach loop.

				$table['data'][] = $row;
			}
		}

		$this->pad_array_to_max_cols( $table['data'] );
		return $table;
	}

	/**
	 * Tries to import a table with the HTML format.
	 *
	 * @since 2.0.0
	 *
	 * @param string $data Data to import.
	 * @return array|WP_Error|false Table array on success, WP_Error on error, false if the file is not a JSON file.
	 */
	protected function _maybe_import_html( $data ) {
		TablePress::load_file( 'html-parser.class.php', 'libraries' );
		$table = HTML_Parser::parse( $data );

		// Check if the HTML code could be parsed. If not, this is probably not an HTML file.
		if ( is_wp_error( $table ) ) {
			return false;
		}

		$this->pad_array_to_max_cols( $table['data'] );
		return $table;
	}

	/**
	 * Tries to import a table via PHPSpreadsheet.
	 *
	 * @since 2.0.0
	 *
	 * @param array $file File to import.
	 * @return array|WP_Error Table array on success, WP_Error on error.
	 */
	protected function _import_phpspreadsheet( array $file ) {
		// Rename the temporary file, as PHPSpreadsheet tries to infer the format from the file's extension.
		if ( '' !== $file['extension'] ) {
			$temp_file = pathinfo( $file['location'] );
			if ( ! isset( $temp_file['extension'] ) || $file['extension'] !== $temp_file['extension'] ) {
				$new_location = "{$temp_file['dirname']}/{$temp_file['filename']}.{$file['extension']}";
				if ( rename( $file['location'], $new_location ) ) {
					$file['location'] = $new_location;
				}
			}
		}

		try {
			// Treat all cell values as strings, except for formulas (due to recognition of quoted/escaped formulas like `'=A2`).
			\TablePress\PhpOffice\PhpSpreadsheet\Cell\Cell::setValueBinder( new \TablePress\PhpOffice\PhpSpreadsheet\Cell\StringValueBinder() );
			\TablePress\PhpOffice\PhpSpreadsheet\Cell\Cell::getValueBinder()->setFormulaConversion( false );

			/*
			 * Try to detect a reader from the file extension and MIME type.
			 * Fall back to CSV if no reader could be determined.
			 */
			try {
				$reader = \TablePress\PhpOffice\PhpSpreadsheet\IOFactory::createReaderForFile( $file['location'] );
			} catch ( \TablePress\PhpOffice\PhpSpreadsheet\Reader\Exception $exception ) {
				$reader = \TablePress\PhpOffice\PhpSpreadsheet\IOFactory::createReader( 'Csv' );
				// Append .csv to the file name, so that \TablePress\PhpOffice\PhpSpreadsheet\Reader\Csv::canRead() returns true.
				$new_location = $file['location'] . '.csv';
				if ( rename( $file['location'], $new_location ) ) {
					$file['location'] = $new_location;
				}
			}

			$class_name = get_class( $reader );
			$class_type = explode( '\\', $class_name );
			$detected_format = strtolower( array_pop( $class_type ) );

			if ( 'csv' === $detected_format ) {
				$reader->setInputEncoding( \TablePress\PhpOffice\PhpSpreadsheet\Reader\Csv::GUESS_ENCODING );
				$reader->setEscapeCharacter( ( PHP_VERSION_ID < 70400 ) ? "\x0" : '' ); // Disable the proprietary escape mechanism of PHP's fgetcsv() in PHP >= 7.4.
			}

			$reader->setIncludeCharts( false );
			$reader->setReadEmptyCells( true );

			// For non-Excel files, import only the data, but ignore formatting.
			if ( ! in_array( $detected_format, array( 'xlsx', 'xls' ), true ) ) {
				$reader->setReadDataOnly( true );
			}

			// For formats where it's supported, import only the first sheet.
			if ( in_array( $detected_format, array( 'csv', 'html', 'slk' ), true ) ) {
				$reader->setSheetIndex( 0 );
			}

			$spreadsheet = $reader->load( $file['location'] );
			$worksheet = $spreadsheet->getActiveSheet();
			$cell_collection = $worksheet->getCellCollection();
			$comments = $worksheet->getComments();

			$table = array(
				'data' => array(),
			);

			$min_col = 'A';
			$min_row = 1;
			$max_col = $worksheet->getHighestColumn();
			$max_row = $worksheet->getHighestRow();

			// Adapted from \TablePress\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet::rangeToArray().
			++$max_col; // Due to for-loop with characters for columns.
			for ( $row = $min_row; $row <= $max_row; $row++ ) {
				$row_data = array();
				for ( $col = $min_col; $col !== $max_col; $col++ ) {
					$cell_reference = $col . $row;
					if ( ! $cell_collection->has( $cell_reference ) ) {
						$row_data[] = '';
						continue;
					}

					$cell = $cell_collection->get( $cell_reference );
					$value = $cell->getValue();
					if ( is_null( $value ) ) {
						$row_data[] = '';
						continue;
					}

					if ( $value instanceof \TablePress\PhpOffice\PhpSpreadsheet\RichText\RichText ) {
						$cell_data = $this->parse_rich_text( $value );
					} else {
						$cell_data = (string) $value;
					}

					// Apply data type formatting.
					$style = $spreadsheet->getCellXfByIndex( $cell->getXfIndex() );
					$cell_data = \TablePress\PhpOffice\PhpSpreadsheet\Style\NumberFormat::toFormattedString(
						$cell_data,
						$style->getNumberFormat() ? $style->getNumberFormat()->getFormatCode() : \TablePress\PhpOffice\PhpSpreadsheet\Style\NumberFormat::FORMAT_GENERAL,
						array( $this, 'format_color' )
					);

					if ( strlen( $cell_data ) > 1 && '=' === $cell_data[0] ) {
						if ( $style->getQuotePrefix() ) {
							// Prepend a ' to quoted/escaped formulas (so that they are shown as text). This is currently not supported (at least) for the XLS format.
							$cell_data = "'{$cell_data}";
						} else {
							// Bail early, to not add inline HTML styling around formulas, as they won't work anymore then.
							$row_data[] = $cell_data;
							continue;
						}
					}

					$cell_has_hyperlink = $worksheet->hyperlinkExists( $cell_reference ) && ! $worksheet->getHyperlink( $cell_reference )->isInternal();

					$font = $style->getFont();

					if ( $font->getSuperscript() ) {
						$cell_data = "<sup>{$cell_data}</sup>";
					}
					if ( $font->getSubscript() ) {
						$cell_data = "<sub>{$cell_data}</sub>";
					}
					if ( $font->getStrikethrough() ) {
						$cell_data = "<del>{$cell_data}</del>";
					}
					if ( $font->getUnderline() !== \TablePress\PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_NONE && ! $cell_has_hyperlink ) {
						$cell_data = "<u>{$cell_data}</u>";
					}
					if ( $font->getBold() ) {
						$cell_data = "<strong>{$cell_data}</strong>";
					}
					if ( $font->getItalic() ) {
						$cell_data = "<em>{$cell_data}</em>";
					}
					$color = $font->getColor()->getRGB();
					if ( '' !== $color && '000000' !== $color && ! $cell_has_hyperlink ) {
						// Don't add the span if the color is black, as that's the default, or if it's in a hyperlink.
						$color_css = esc_attr( "color:#{$color};" );
						$cell_data = "<span style=\"{$color_css}\">{$cell_data}</span>";
					}

					// Convert Hyperlinks to HTML code.
					if ( $cell_has_hyperlink ) {
						$url = $worksheet->getHyperlink( $cell_reference )->getUrl();
						if ( '' !== $url ) {
							$title = $worksheet->getHyperlink( $cell_reference )->getTooltip();
							if ( '' !== $title ) {
								$title = ' title="' . esc_attr( $title ) . '"';
							}
							$url = esc_url( $url );
							$cell_data = "<a href=\"{$url}\"{$title}>{$cell_data}</a>";
						}
					}

					// Add comments.
					if ( isset( $comments[ $cell_reference ] ) ) {
						$sanitized_comment = esc_html( $worksheet->getComment( $cell_reference )->getText()->getPlainText() );
						if ( '' !== $sanitized_comment ) {
							$cell_data .= '<div class="comment">' . $sanitized_comment . '</div>';
						}
					}

					$row_data[] = $cell_data;
				}
				$table['data'][] = $row_data;
			}

			// Convert merged cells to trigger words.
			$merged_cells = $worksheet->getMergeCells();
			foreach ( $merged_cells as $merged_cells_range ) {
				$cells = explode( ':', $merged_cells_range );
				$first_cell = \TablePress\PhpOffice\PhpSpreadsheet\Cell\Coordinate::indexesFromString( $cells[0] );
				$last_cell = \TablePress\PhpOffice\PhpSpreadsheet\Cell\Coordinate::indexesFromString( $cells[1] );
				for ( $row_idx = $first_cell[1]; $row_idx <= $last_cell[1]; $row_idx++ ) {
					for ( $column_idx = $first_cell[0]; $column_idx <= $last_cell[0]; $column_idx++ ) {
						if ( $row_idx === $first_cell[1] && $column_idx === $first_cell[0] ) {
							continue; // Keep value of first cell.
						} elseif ( $row_idx === $first_cell[1] && $column_idx > $first_cell[0] ) {
							$table['data'][ $row_idx - 1 ][ $column_idx - 1 ] = '#colspan#';
						} elseif ( $row_idx > $first_cell[1] && $column_idx === $first_cell[0] ) {
							$table['data'][ $row_idx - 1 ][ $column_idx - 1 ] = '#rowspan#';
						} else {
							$table['data'][ $row_idx - 1 ][ $column_idx - 1 ] = '#span#';
						}
					}
				}
			}

			// Save PHP memory.
			$spreadsheet->disconnectWorksheets();
			unset( $comments, $cell_collection, $worksheet, $spreadsheet );

			return $table;
		} catch ( \TablePress\PhpOffice\PhpSpreadsheet\Reader\Exception $exception ) {
			return new WP_Error( 'table_import_phpspreadsheet_failed', '', 'Exception: ' . $exception->getMessage() );
		}
	}

	/**
	 * Parses PHPSpreadsheet RichText elements and converts formatting to HTML tags.
	 *
	 * @param RichText $value RichText element.
	 * @return string Cell value with HTML formatting.
	 */
	protected function parse_rich_text( $value ) {
		$cell_data = '';
		$elements = $value->getRichTextElements();
		foreach ( $elements as $element ) {
			$element_data = $element->getText();

			// Rich text start?
			if ( $element instanceof \TablePress\PhpOffice\PhpSpreadsheet\RichText\Run ) {
				$font = $element->getFont();

				if ( $font->getSuperscript() ) {
					$element_data = "<sup>{$element_data}</sup>";
				}
				if ( $font->getSubscript() ) {
					$element_data = "<sub>{$element_data}</sub>";
				}
				if ( $font->getStrikethrough() ) {
					$element_data = "<del>{$element_data}</del>";
				}
				if ( $font->getUnderline() !== \TablePress\PhpOffice\PhpSpreadsheet\Style\Font::UNDERLINE_NONE ) {
					$element_data = "<u>{$element_data}</u>";
				}
				if ( $font->getBold() ) {
					$element_data = "<strong>{$element_data}</strong>";
				}
				if ( $font->getItalic() ) {
					$element_data = "<em>{$element_data}</em>";
				}
				$color = $font->getColor()->getRGB();
				if ( '' !== $color ) {
					$color_css = esc_attr( "color:#{$color};" );
					$element_data = "<span style=\"{$color_css}\">{$element_data}</span>";
				}
			}

			$cell_data .= $element_data;
		}
		return $cell_data;
	}

	/**
	 * Adds color to formatted string as inline style, e.g. from conditional formatting.
	 *
	 * @param string $value       Plain formatted value without color.
	 * @param string $format_code Format code.
	 * @return string Value with color format applied.
	 */
	public function format_color( $value, $format_code ) {
		// Color information, e.g. [Red] is always at the beginning of the format code.
		$color = '';
		if ( 1 === preg_match( '/^\\[[a-zA-Z]+\\]/', $format_code, $matches ) ) {
			$color = str_replace( array( '[', ']' ), '', $matches[0] );
			$color = strtolower( $color );
		}

		if ( '' !== $color ) {
			$color = esc_attr( "color:{$color};" );
			$value = "<span style=\"{$color}\">{$value}</span>";
		}

		return $value;
	}

} // class TablePress_Import_PHPSpreadsheet
