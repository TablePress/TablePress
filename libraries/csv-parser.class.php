<?php
/**
 * CSV Parsing class for TablePress, used for import of CSV files
 *
 * @package TablePress
 * @subpackage Import
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * CSV Parsing class
 * @package TablePress
 * @subpackage Import
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class CSV_Parser {

	// enclosure (double quote)
	protected $enclosure = '"';

	// number of rows to analyze when attempting to auto-detect delimiter
	protected $delimiter_search_max_lines = 15;
	// characters to ignore when attempting to auto-detect delimiter
	protected $non_delimiter_chars = "a-zA-Z0-9\n\r";
	// preferred delimiter characters, only used when all filtering method
	// returns multiple possible delimiters (happens very rarely)
	protected $preferred_delimiter_chars = ";,\t";
	// data to import
	protected $import_data;

	// error while parsing input data
	//	0 = No errors found. Everything should be fine :)
	//	1 = Hopefully correctable syntax error was found.
	//	2 = Enclosure character (double quote by default) was found in non-enclosed field.
	//		This means the file is either corrupt, or does not standard CSV formatting.
	//		Please validate the parsed data yourself.
	public $error = 0;

	// detailed error info
	public $error_info = array();

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// intentionally left blank
	}

	/**
	 * Load data that shall be parsed
	 *
	 * @since 1.0.0
	 *
	 * @param string $data Data to be parsed
	 */
	public function load_data( $data ) {
		// check for mandatory trailing line break
		if ( substr( $data, -1 ) != "\n" ) {
			$data .= "\n";
		}
		$this->import_data = &$data;
	}

	/**
	 * Detect the CSV delimiter, by analyzing some rows to determine most probable delimiter character
	 *
	 * @since 1.0.0
	 *
	 * @return string Most probable delimiter character
	 */
	public function find_delimiter() {
		$data = &$this->import_data;

		$delimiter_count = array();
		$enclosed = false;
		$current_line = 0;

		// walk through each character in the CSV string (up to $this->delimiter_search_max_lines)
		// and search potential delimiter characters
		$data_length = strlen( $data );
		for ( $i = 0; $i < $data_length; $i++ ) {
			$prev_char = ( $i-1 >= 0 ) ? $data[$i-1] : '';
			$curr_char = $data[$i];
			$next_char = ( $i+1 < $data_length ) ? $data[$i+1] : '';

			if ( $curr_char == $this->enclosure ) {
				// open and closing quotes
				if ( ! $enclosed || $next_char != $this->enclosure ) {
					$enclosed = ! $enclosed; // flip bool
				} elseif ( $enclosed ) {
					$i++; // skip next character
				}
			} elseif ( ( "\n" == $curr_char && "\r" != $prev_char || "\r" == $curr_char ) && ! $enclosed ) {
				// reached end of a line
				$current_line++;
				if ( $current_line >= $this->delimiter_search_max_lines ) {
					break;
				}
			} elseif ( ! $enclosed ) {
				// at this point $curr_char seems to be used as a delimiter, as it is not enclosed
				// count $curr_char if it is not in the non_delimiter_chars list
				if ( 0 === preg_match( '#[' . $this->non_delimiter_chars . ']#i', $curr_char ) ) {
					if ( ! isset( $delimiter_count[$curr_char][$current_line] ) ) {
						$delimiter_count[$curr_char][$current_line] = 0; // init empty
					}
					$delimiter_count[$curr_char][$current_line]++;
				}
			}
		}

		// find most probable delimiter, by sorting their counts
		$potential_delimiters = array();
		foreach ( $delimiter_count as $char => $line_counts ) {
			$is_possible_delimiter = $this->_check_delimiter_count( $char, $line_counts, $current_line );
			if ( false !== $is_possible_delimiter ) {
				$potential_delimiters[$is_possible_delimiter] = $char;
			}
		}
		ksort( $potential_delimiters );
		// return first array element, as that has the highest count
		return array_shift( $potential_delimiters );
	}

	/**
	 * Check if passed character can be a delimiter, by checking counts in each line
	 *
	 * @since 1.0.0
	 *
	 * @param string|char $char Character to check
	 * @param array $line_counts
	 * @param int $number_lines
	 * @return bool|string False if delimiter is not possible, string to be used as a sort key if character could be a delimiter
	 */
	protected function _check_delimiter_count( $char, array $line_counts, $number_lines ) {
		// was potential delimiter found in every line?
		if ( count( $line_counts ) != $number_lines ) {
			return false;
		}

		// check if count in every line is the same (or one higher for "almost")
		$first = null;
		$equal = null;
		$almost = false;
		foreach ( $line_counts as $line => $count ) {
			if ( null == $first ) {
				$first = $count;
			} elseif ( $count == $first && false !== $equal ) {
				$equal = true;
			} elseif ( $count == $first + 1 && false !== $equal ) {
				$equal = true;
				$almost = true;
			} else {
				$equal = false;
			}
		}
		// check equality only if more than one row
		if ( $number_lines > 1 && ! $equal ) {
			return false;
		}

		// at this point, count is equal in all lines, determine a string to sort priority
		$match = ( $almost ) ? 2 : 1 ;
		$pref = strpos( $this->preferred_delimiter_chars, $char );
		$pref = ( false !== $pref ) ? str_pad( $pref, 3, '0', STR_PAD_LEFT ) : '999';
		return $pref . $match . '.' . ( 99999 - str_pad( $first, 5, '0', STR_PAD_LEFT ) );
	}

	/**
	 * Parse CSV string into 2D array
	 *
	 * @since 1.0.0
	 *
	 * @param string $delimiter Delimiter character for the CSV parsing
	 * @return array 2D array with the data from the CSV string
	 */
	public function parse( $delimiter ) {
		$data = &$this->import_data;

		$white_spaces = str_replace( $delimiter, '', " \t\x0B\0" ); // filter delimiter from the list, if it is a white-space character

		$rows = array(); // complete rows
		$row = array(); // row that is currently built
		$column = 0; // current column index
		$cell_content = ''; // content of the currently processed cell
		$enclosed = false;
		$was_enclosed = false; // to determine if cell content will be trimmed of white-space (only for enclosed cells)

		// walk through each character in the CSV string
		$data_length = strlen( $data );
		for ( $i = 0; $i < $data_length; $i++ ) {
			$curr_char = $data[$i];
			$next_char = ( $i+1 < $data_length ) ? $data[$i+1] : '';

			if ( $curr_char == $this->enclosure ) {
				// open/close quotes, and inline quotes
				if ( ! $enclosed ) {
					if ( '' == ltrim( $cell_content, $white_spaces ) ) {
						$enclosed = true;
						$was_enclosed = true;
					} else {
						$this->error = 2;
						$error_line = count( $rows ) + 1;
						$error_column = $column + 1;
						if ( ! isset( $this->error_info[ $error_line.'-'.$error_column ] ) ) {
							$this->error_info[ $error_line.'-'.$error_column ] = array(
								'type' => 2,
								'info' => "Syntax error found in line {$error_line}. Non-enclosed fields can not contain double-quotes.",
								'line' => $error_line,
								'column' => $error_column
							);
						}
						$cell_content .= $curr_char;
					}
				} elseif ( $next_char == $this->enclosure ) {
					// enclosure character within enclosed cell (" encoded as "")
					$cell_content .= $curr_char;
					$i++; // skip next character
				} elseif ( $next_char != $delimiter && "\r" != $next_char && "\n" != $next_char ) {
					// for-loop (instead of while-loop) that skips white-space
					for ( $x = ( $i+1 ); isset( $data[$x] ) && '' == ltrim( $data[$x], $white_spaces ); $x++ ) {}
					if ( $data[$x] == $delimiter ) {
						$enclosed = false;
						$i = $x;
					} else {
						if ( $this->error < 1 ) {
							$this->error = 1;
						}
						$error_line = count( $rows ) + 1;
						$error_column = $column + 1;
						if ( ! isset( $this->error_info[ $error_line.'-'.$error_column ] ) ) {
							$this->error_info[ $error_line.'-'.$error_column ] = array(
								'type' => 1,
								'info' => "Syntax error found in line {$error_line}. A single double-quote was found within an enclosed string. Enclosed double-quotes must be escaped with a second double-quote.",
								'line' => $error_line,
								'column' => $error_column
							);
						}
						$cell_content .= $curr_char;
						$enclosed = false;
					}
				} else {
					// the " was the closing one for the cell
					$enclosed = false;
				}
			} elseif ( ( $curr_char == $delimiter || "\n" == $curr_char || "\r" == $curr_char ) && ! $enclosed ) {
				// end of cell (by $delimiter), or end of line (by line break, and not enclosed!)

				$row[$column] = ( $was_enclosed ) ? $cell_content : trim( $cell_content );
				$cell_content = '';
				$was_enclosed = false;
				$column++;

				// end of line
				if ( "\n" == $curr_char || "\r" == $curr_char ) {
					// append completed row
					$rows[] = $row;
					$row = array();
					$column = 0;
					if ( "\r" == $curr_char && "\n" == $next_char ) {
						$i++; // skip next character in \r\n line breaks
					}
				}
			} else {
				// append character to current cell
				$cell_content .= $curr_char;
			}
		}

		return $rows;
	}

} // class CSV_Parser
