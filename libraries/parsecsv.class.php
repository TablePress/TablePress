<?php
/**
 * CSV Parsing Class for WP-Table Reloaded, used for import and export
 *
 * @package WP-Table Reloaded
 * @subpackage Classes
 * @since 1.2
 */

class parseCSV {

/*
	Class: parseCSV v0.4.3 beta
	http://code.google.com/p/parsecsv-for-php/

	Fully conforms to the specifications lined out on wikipedia:
	 - http://en.wikipedia.org/wiki/Comma-separated_values

	Based on the concept of Ming Hong Ng's CsvFileParser class:
	 - http://minghong.blogspot.com/2006/07/csv-parser-for-php.html

	Copyright (c) 2007 Jim Myhrberg (jim@zydev.info).

	Permission is hereby granted, free of charge, to any person obtaining a copy
	of this software and associated documentation files (the "Software"), to deal
	in the Software without restriction, including without limitation the rights
	to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
	copies of the Software, and to permit persons to whom the Software is
	furnished to do so, subject to the following conditions:

	The above copyright notice and this permission notice shall be included in
	all copies or substantial portions of the Software.

	THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
	IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
	FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
	AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
	LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
	OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
	THE SOFTWARE.

	Code Examples
	----------------
	# general usage
	$csv = new parseCSV('data.csv');
	print_r($csv->data);
	----------------
	# tab delimited, and encoding conversion
	$csv = new parseCSV();
	$csv->encoding('UTF-16', 'UTF-8');
	$csv->delimiter = "\t";
	$csv->parse('data.tsv');
	print_r($csv->data);
	----------------
	# auto-detect delimiter character
	$csv = new parseCSV();
	$csv->auto('data.csv');
	print_r($csv->data);
	----------------
	# modify data in a csv file
	$csv = new parseCSV();
	$csv->sort_by = 'id';
	$csv->parse('data.csv');
	# "4" is the value of the "id" column of the CSV row
	$csv->data[4] = array('firstname' => 'John', 'lastname' => 'Doe', 'email' => 'john@doe.com');
	$csv->save();
	----------------
	# add row/entry to end of CSV file
	#  - only recommended when you know the extact sctructure of the file
	$csv = new parseCSV();
	$csv->save('data.csv', array('1986', 'Home', 'Nowhere', ''), true);
	----------------
	# convert 2D array to csv data and send headers
	# to browser to treat output as a file and download it
	$csv = new parseCSV();
	$csv->output (true, 'movies.csv', $array);
	----------------

*/

	/**
	 * Configuration
	 * - set these options with $object->var_name = 'value';
	 */

	# use first line/entry as field names
	var $heading = true;

	# override field names
	var $fields = array();

	# sort entries by this field
	var $sort_by = null;
	var $sort_reverse = false;

	# sort behavior passed to ksort/krsort functions
	# regular = SORT_REGULAR
	# numeric = SORT_NUMERIC
	# string  = SORT_STRING
	var $sort_type = null;

	# delimiter (semicolon) and enclosure (double quote)
	var $delimiter = ';';
	var $enclosure = '"';

	# basic SQL-like conditions for row matching
	var $conditions = null;

	# number of rows to ignore from beginning of data
	var $offset = null;

	# limits the number of returned rows to specified amount
	var $limit = null;

	# number of rows to analyze when attempting to auto-detect delimiter
	var $auto_depth = 15;

	# characters to ignore when attempting to auto-detect delimiter
	var $auto_non_chars = "a-zA-Z0-9\n\r";

	# preferred delimiter characters, only used when all filtering method
	# returns multiple possible delimiters (happens very rarely)
	var $auto_preferred = ",;\t.:|";

	# character encoding options
	var $convert_encoding = false;
	var $input_encoding = 'ISO-8859-1';
	var $output_encoding = 'ISO-8859-1';

	# used by unparse(), save(), and output() functions
	var $linefeed = "\r\n";

	# only used by output() function
	var $output_delimiter = ';';
	var $output_filename = 'data.csv';

	# keep raw file data in memory after successful parsing (useful for debugging)
	var $keep_file_data = false;

	/**
	 * Internal variables
	 */

	# current file
	var $file;

	# loaded file contents
	var $file_data;

	# error while parsing input data
	#  0 = No errors found. Everything should be fine :)
	#  1 = Hopefully correctable syntax error was found.
	#  2 = Enclosure character (double quote by default)
	#      was found in non-enclosed field. This means
	#      the file is either corrupt, or does not
	#      standard CSV formatting. Please validate
	#      the parsed data yourself.
	var $error = 0;

	# detailed error info
	var $error_info = array();

	# array of field values in data parsed
	var $titles = array();

	# two dimentional array of CSV data
	var $data = array();


	/**
	 * Constructor
	 * @param   input   CSV file or string
	 * @return  nothing
	 */
	function parseCSV ($input = null, $offset = null, $limit = null, $conditions = null) {
		if ( $offset !== null ) $this->offset = $offset;
		if ( $limit !== null ) $this->limit = $limit;
		if ( count($conditions) > 0 ) $this->conditions = $conditions;
		if ( !empty($input) ) $this->parse($input);
	}


	// ==============================================
	// ----- [ Main Functions ] ---------------------
	// ==============================================

	/**
	 * Parse CSV file or string
	 * @param   input   CSV file or string
	 * @return  nothing
	 */
	function parse ($input = null, $offset = null, $limit = null, $conditions = null) {
		if ( $input === null ) $input = $this->file;
		if ( !empty($input) ) {
			if ( $offset !== null ) $this->offset = $offset;
			if ( $limit !== null ) $this->limit = $limit;
			if ( count($conditions) > 0 ) $this->conditions = $conditions;
			if ( is_readable($input) ) {
				$this->data = $this->parse_file($input);
			} else {
				$this->file_data = &$input;
				$this->data = $this->parse_string();
			}
			if ( $this->data === false ) return false;
		}
		return true;
	}

	/**
	 * Convert character encoding
	 * @param   input    input character encoding, uses default if left blank
	 * @param   output   output character encoding, uses default if left blank
	 * @return  nothing
	 */
	function encoding ($input = null, $output = null) {
		$this->convert_encoding = true;
		if ( $input !== null ) $this->input_encoding = $input;
		if ( $output !== null ) $this->output_encoding = $output;
	}

	/**
	 * Auto-Detect Delimiter: Find delimiter by analyzing a specific number of
	 * rows to determine most probable delimiter character
	 * @param   file           local CSV file
	 * @param   parse          true/false parse file directly
	 * @param   search_depth   number of rows to analyze
	 * @param   preferred      preferred delimiter characters
	 * @param   enclosure      enclosure character, default is double quote (").
	 * @return  delimiter character
	 */
	function auto ($file = null, $parse = true, $search_depth = null, $preferred = null, $enclosure = null) {

		if ( $file === null ) $file = $this->file;
		if ( empty($search_depth) ) $search_depth = $this->auto_depth;
		if ( $enclosure === null ) $enclosure = $this->enclosure;

		if ( $preferred === null ) $preferred = $this->auto_preferred;

		if ( empty($this->file_data) ) {
			if ( $this->_check_data($file) ) {
				$data = &$this->file_data;
			} else return false;
		} else {
			$data = &$this->file_data;
		}

		$chars = array();
		$strlen = strlen($data);
		$enclosed = false;
		$n = 1;
		$to_end = true;

		// walk specific depth finding posssible delimiter characters
		for ( $i=0; $i < $strlen; $i++ ) {
			$ch = $data{$i};
			$nch = ( isset($data{$i+1}) ) ? $data{$i+1} : false ;
			$pch = ( isset($data{$i-1}) ) ? $data{$i-1} : false ;

			// open and closing quotes
			if ( $ch == $enclosure ) {
				if ( !$enclosed || $nch != $enclosure ) {
					$enclosed = ( $enclosed ) ? false : true ;
				} elseif ( $enclosed ) {
					$i++;
				}

			// end of row
			} elseif ( ($ch == "\n" && $pch != "\r" || $ch == "\r") && !$enclosed ) {
				if ( $n >= $search_depth ) {
					$strlen = 0;
					$to_end = false;
				} else {
					$n++;
				}

			// count character
			} elseif (!$enclosed) {
				if ( !preg_match('/['.preg_quote($this->auto_non_chars, '/').']/i', $ch) ) {
					if ( !isset($chars[$ch][$n]) ) {
						$chars[$ch][$n] = 1;
					} else {
						$chars[$ch][$n]++;
					}
				}
			}
		}

		// filtering
		$depth = ( $to_end ) ? $n-1 : $n ;
		$filtered = array();
		foreach ( $chars as $char => $value ) {
			if ( $match = $this->_check_count($char, $value, $depth, $preferred) ) {
				$filtered[$match] = $char;
			}
		}

		// capture most probable delimiter
		ksort($filtered);

		$this->delimiter = reset($filtered);

		// parse data
		if ( $parse ) $this->data = $this->parse_string();

		return $this->delimiter;

	}


	// ==============================================
	// ----- [ Core Functions ] ---------------------
	// ==============================================

	/**
	 * Read file to string and call parse_string()
	 * @param   file   local CSV file
	 * @return  2D array with CSV data, or false on failure
	 */
	function parse_file ($file = null) {
		if ( $file === null ) $file = $this->file;
		if ( empty($this->file_data) ) $this->load_data($file);
		return ( !empty($this->file_data) ) ? $this->parse_string() : false ;
	}

	/**
	 * Parse CSV strings to arrays
	 * @param   data   CSV string
	 * @return  2D array with CSV data, or false on failure
	 */
	function parse_string ($data = null) {
		if ( empty($data) ) {
			if ( $this->_check_data() ) {
				$data = &$this->file_data;
			} else return false;
		}

		$white_spaces = str_replace($this->delimiter, '', " \t\x0B\0");

		$rows = array();
		$row = array();
		$row_count = 0;
		$current = '';
		$head = ( !empty($this->fields) ) ? $this->fields : array() ;
		$col = 0;
		$enclosed = false;
		$was_enclosed = false;
		$strlen = strlen($data);

		// walk through each character
		for ( $i=0; $i < $strlen; $i++ ) {
			$ch = $data{$i};
			$nch = ( isset($data{$i+1}) ) ? $data{$i+1} : false ;
			$pch = ( isset($data{$i-1}) ) ? $data{$i-1} : false ;

			// open/close quotes, and inline quotes
			if ( $ch == $this->enclosure ) {
				if ( !$enclosed ) {
					if ( ltrim($current, $white_spaces) == '' ) {
						$enclosed = true;
						$was_enclosed = true;
					} else {
						$this->error = 2;
						$error_row = count($rows) + 1;
						$error_col = $col + 1;
						if ( !isset($this->error_info[$error_row.'-'.$error_col]) ) {
							$this->error_info[$error_row.'-'.$error_col] = array(
								'type' => 2,
								'info' => 'Syntax error found on row '.$error_row.'. Non-enclosed fields can not contain double-quotes.',
								'row' => $error_row,
								'field' => $error_col,
								'field_name' => (!empty($head[$col]) ) ? $head[$col] : null,
							);
						}
						$current .= $ch;
					}
				} elseif ($nch == $this->enclosure) {
					$current .= $ch;
					$i++;
				} elseif ( $nch != $this->delimiter && $nch != "\r" && $nch != "\n" ) {
					for ( $x=($i+1); isset($data{$x}) && ltrim($data{$x}, $white_spaces) == ''; $x++ ) {}
					if ( $data{$x} == $this->delimiter ) {
						$enclosed = false;
						$i = $x;
					} else {
						if ( $this->error < 1 ) {
							$this->error = 1;
						}
						$error_row = count($rows) + 1;
						$error_col = $col + 1;
						if ( !isset($this->error_info[$error_row.'-'.$error_col]) ) {
							$this->error_info[$error_row.'-'.$error_col] = array(
								'type' => 1,
								'info' =>
									'Syntax error found on row '.(count($rows) + 1).'. '.
									'A single double-quote was found within an enclosed string. '.
									'Enclosed double-quotes must be escaped with a second double-quote.',
								'row' => count($rows) + 1,
								'field' => $col + 1,
								'field_name' => (!empty($head[$col]) ) ? $head[$col] : null,
							);
						}
						$current .= $ch;
						$enclosed = false;
					}
				} else {
					$enclosed = false;
				}

			// end of field/row
			} elseif ( ($ch == $this->delimiter || $ch == "\n" || $ch == "\r") && !$enclosed ) {
				$key = ( !empty($head[$col]) ) ? $head[$col] : $col ;
				$row[$key] = ( $was_enclosed ) ? $current : trim($current) ;
				$current = '';
				$was_enclosed = false;
				$col++;

				// end of row
				if ( $ch == "\n" || $ch == "\r" ) {
					$row = array();
					$col = 0;
					$row_count++;
					if ( $this->sort_by === null && $this->limit !== null && count($rows) == $this->limit ) {
						$i = $strlen;
					}
					if ( $ch == "\r" && $nch == "\n" ) $i++;
				}

			// append character to current field
			} else {
				$current .= $ch;
			}
		}
		$this->titles = $head;
		if ( !empty($this->sort_by) ) {
			$sort_type = SORT_REGULAR;
			if ( $this->sort_type == 'numeric' ) {
				$sort_type = SORT_NUMERIC;
			} elseif ( $this->sort_type == 'string' ) {
				$sort_type = SORT_STRING;
			}
			( $this->sort_reverse ) ? krsort($rows, $sort_type) : ksort($rows, $sort_type) ;
			if ( $this->offset !== null || $this->limit !== null ) {
				$rows = array_slice($rows, ($this->offset === null ? 0 : $this->offset) , $this->limit, true);
			}
		}
		if ( !$this->keep_file_data ) {
			$this->file_data = null;
		}
		return $rows;
	}

	/**
	 * Load local file or string
	 * @param   input   local CSV file
	 * @return  true or false
	 */
	function load_data ($input = null) {
		$data = null;
		$file = null;
		if ( $input === null ) {
			$file = $this->file;
    // don't needed for us, as we only pass data not file names
		//} elseif ( file_exists($input) ) {
		//	$file = $input;
		} else {
			$data = $input;
		}
		if ( !empty($data) || $data = $this->_rfile($file) ) {
			if ( $this->file != $file ) $this->file = $file;
			if ( preg_match('/\.php$/i', $file) && preg_match('/<\?.*?\?>(.*)/ims', $data, $strip) ) {
				$data = ltrim($strip[1]);
			}
      // added @ to suppress error messages if iconv is missing
			if ( $this->convert_encoding ) $data = @iconv($this->input_encoding, $this->output_encoding, $data);
			if ( substr($data, -1) != "\n" ) $data .= "\n";
			$this->file_data = &$data;
			return true;
		}
		return false;
	}


	// ==============================================
	// ----- [ Internal Functions ] -----------------
	// ==============================================

	/**
	 * Check file data
	 * @param   file   local filename
	 * @return  true or false
	 */
	function _check_data ($file = null) {
		if ( empty($this->file_data) ) {
			if ( $file === null ) $file = $this->file;
			return $this->load_data($file);
		}
		return true;
	}


	/**
	 * Check if passed info might be delimiter
	 *  - only used by find_delimiter()
	 * @return  special string used for delimiter selection, or false
	 */
	function _check_count ($char, $array, $depth, $preferred) {
		if ( $depth == count($array) ) {
			$first = null;
			$equal = null;
			$almost = false;
			foreach ( $array as $key => $value ) {
				if ( $first == null ) {
					$first = $value;
				} elseif ( $value == $first && $equal !== false) {
					$equal = true;
				} elseif ( $value == $first+1 && $equal !== false ) {
					$equal = true;
					$almost = true;
				} else {
					$equal = false;
				}
			}

			if ( $equal ) {
				$match = ( $almost ) ? 2 : 1 ;
				$pref = strpos($preferred, $char);
				$pref = ( $pref !== false ) ? str_pad($pref, 3, '0', STR_PAD_LEFT) : '999' ;
				return $pref.$match.'.'.(99999 - str_pad($first, 5, '0', STR_PAD_LEFT) );
			} else return false;
		}
	}

}