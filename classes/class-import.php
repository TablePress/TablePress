<?php
/**
 * TablePress Table Import Class
 *
 * @package TablePress
 * @subpackage Export/Import
 * @author Tobias Bäthge
 * @since 1.0.0
 */

// Prohibit direct script loading
defined( 'ABSPATH' ) || die( 'No direct script access allowed!' );

/**
 * TablePress Table Import Class
 * @package TablePress
 * @subpackage Export/Import
 * @author Tobias Bäthge
 * @since 1.0.0
 */
class TablePress_Import {

	/**
	 * File/Data Formats that are available for import
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	public $import_formats = array();

	/**
	 * Whether ZIP archive support is available in the PHP installation on the server
	 *
	 * @since 1.0.0
	 *
	 * @var bool
	 */
	public $zip_support_available = false;

	/**
	 * Data to be imported
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $import_data;

	/**
	 * Imported table data
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $imported_table = false;

	/**
	 * Initialize the Import class
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		// initiate here, because function call not possible outside a class method
		$this->import_formats = array(
			'csv' => __( 'CSV - Character-Separated Values', 'tablepress' ),
			'html' => __( 'HTML - Hypertext Markup Language', 'tablepress' ),
			'xml' => __( 'XML - eXtended Markup Language', 'tablepress' ),
			'json' => __( 'JSON - JavaScript Object Notation', 'tablepress' )
		);

		// filter from @see unzip_file() in WordPress
		if ( class_exists( 'ZipArchive' ) && apply_filters( 'unzip_file_use_ziparchive', true ) )
			$this->zip_support_available = true;
	}

	/**
	 * Import a table
	 *
	 * @since 1.0.0
	 *
	 * @param string $format Import format
	 * @param array $data Data to import
	 * @return bool|array False on error, data array on success
	 */
	function import_table( $format, $data ) {
		$this->import_data = $data;
		switch ( $format ) {
			case 'csv':
				$this->import_csv();
				break;
			case 'html':
				$this->import_html();
				break;
			case 'xml':
				$this->import_xml();
				break;
			/*case 'wp_table':
				$this->import_wp_table();
				break;*/
			default:
				return false;
		}

		// only check this, if needed functions are available (needs PHP library "mbstring")
		if ( function_exists( 'mb_detect_encoding' ) && function_exists( 'mb_check_encoding' ) && function_exists( 'utf8_encode' )
		&& false != $this->imported_table )
			$this->fix_table_encoding();

		return $this->imported_table;
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	protected function import_csv() {
		$parseCSV = TablePress::load_class( 'parseCSV', 'parsecsv.class.php', 'libraries' );
		$parseCSV->heading = false; // means: treat first row like all others

		// different things have worked, but don't always
		// none of the following: 1 of 3
		//$parseCSV->encoding( 'ISO-8859-1', 'ISO-8859-1//IGNORE' ); // might need to play with this a little or offer an option // 1 of 3
		//$parseCSV->encoding( 'ISO-8859-1', 'UTF-8//IGNORE' ); // might need to play with this a little or offer an option // 0 of 3
		//$parseCSV->encoding( 'ISO-8859-1', 'UTF-8' ); // might need to play with this a little or offer an option // 0 of 3
		//$parseCSV->encoding( 'Windows-1252', 'UTF-8//IGNORE' ); // might need to play with this a little or offer an option // 1 of 3
		$parseCSV->load_data( $this->import_data );
		$parseCSV->auto(); // let parsecsv do its magic (determine delimiter and parse the data)

		$this->imported_table = $this->pad_array_to_max_cols( $parseCSV->data );
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	protected function import_html() {
		$simpleXML = TablePress::load_class( 'simplexml', 'simplexml.class.php', 'libraries' );

		// extract table from HTML, pattern: <table> (with eventually class, id, ...
		// . means any charactery (except newline),
		// * means in any count
		// ? means non-gready (shortest possible)
		// is at the end: i: case-insensitive, s: include newline (in .)
		if ( 1 == preg_match( '#<table.*?>.*?</table>#is', $this->import_data, $matches ) ) {
			$temp_data = $matches[0]; // if found, take match as table to import
		} else {
			$this->imported_table = false;
			return;
		}

		// most inner items have to be escaped, so we can get their contents as a string not as array elements
		$temp_data = preg_replace( '#<td(.*?)>#', '<td${1}><![CDATA[' , $temp_data );
		$temp_data = preg_replace( '#</td>#', ']]></td>' , $temp_data );
		$temp_data = preg_replace( '#<thead(.*?)>#', '<_thead${1}>' , $temp_data ); // temporaray, otherwise <thead> will be affected by replacement of <th
		$temp_data = preg_replace( '#<th(.*?)>#', '<th${1}><![CDATA[' , $temp_data );
		$temp_data = preg_replace( '#<_thead(.*?)>#', '<thead${1}>' , $temp_data ); // revert from 2 lines above
		$temp_data = preg_replace( '#</th>#', ']]></th>' , $temp_data );
		$temp_data = $simpleXML->xml_load_string( $temp_data, 'array' );

		if ( ! is_array( $temp_data ) ) {
			$this->imported_table = false;
			return;
		}

		$data = array();

		$rows = array();
		$rows = ( isset( $temp_data['thead'][0]['tr'] ) ) ? array_merge( $rows, $temp_data['thead'][0]['tr'] ) : $rows ;
		$rows = ( isset( $temp_data['tbody'][0]['tr'] ) ) ? array_merge( $rows, $temp_data['tbody'][0]['tr'] ) : $rows ;
		$rows = ( isset( $temp_data['tfoot'][0]['tr'] ) ) ? array_merge( $rows, $temp_data['tfoot'][0]['tr'] ) : $rows ;
		$rows = ( isset( $temp_data['tr'] ) ) ? array_merge( $rows, $temp_data['tr'] ) : $rows ;
		foreach ( $rows as $row ) {
			$th_cols = ( isset( $row['th'] ) ) ? $row['th'] : array() ;
			$td_cols = ( isset( $row['td'] ) ) ? $row['td'] : array() ;
			$data[] = array_merge( $th_cols, $td_cols );
		}

		$this->imported_table = $this->pad_array_to_max_cols( $data );
	}

	/**
	 *
	 *
	 * @since 1.0.0
	 */
	protected function import_xml() {
		$simpleXML = TablePress::load_class( 'simplexml', 'simplexml.class.php', 'libraries' );

		$temp_data = $simpleXML->xml_load_string( $this->import_data, 'array' );

		if ( ! is_array( $temp_data ) || empty( $temp_data['row'] ) ) {
			$this->imported_table = false;
			return;
		}

		$data = $temp_data['row'];
		foreach ( $data as $key => $value )
			$data[$key] = $value['col'];

		$this->imported_table = $this->pad_array_to_max_cols( $data );
	}

	// make sure array is rectangular with $max_cols columns in every row
	/**
	 *
	 *
	 * @since 1.0.0
	 */
	protected function pad_array_to_max_cols( $array ){
		$rows = count( $array );
		$rows = ( $rows > 0 ) ? $rows : 1;
		$max_columns = $this->count_max_columns( $array );
		$max_columns = ( $max_columns > 0 ) ? $max_columns : 1;
		// array_map wants arrays as additional parameters (so we create one with the max_columns to pad to and one with the value to use (empty string)
		$max_columns_array = array_fill( 1, $rows, $max_columns );
		$pad_values_array =	 array_fill( 1, $rows, '' );
		return array_map( 'array_pad', $array, $max_columns_array, $pad_values_array );
	}

	/**
	 * Get the biggest number of columns in the rows
	 *
	 * @since 1.0.0
	 */
	protected function count_max_columns( $array ){
		$max_columns = 0;
		if ( ! is_array( $array ) || 0 == count( $array ) )
			return $max_columns;

		foreach ( $array as $row_idx => $row ) {
			$num_columns  = count( $row );
			$max_columns = max( $num_columns, $max_columns );
		}
		return	$max_columns;
	}

	/**
	 * Fixes the encoding to UTF-8 for a cell
	 *
	 * @since 1.0.0
	 */
	protected function fix_encoding( $string ) {
		return ( 'UTF-8' == mb_detect_encoding( $string ) && mb_check_encoding( $string, 'UTF-8' ) ) ? $string : utf8_encode( $string );
	}

	/**
	 * Fixes the encoding to UTF-8 for the entire table
	 *
	 * @since 1.0.0
	 */
	protected function fix_table_encoding() {
		if ( ! is_array( $this->imported_table ) || 0 == count( $this->imported_table ) )
			return;

		foreach ( $this->imported_table as $row_idx => $row ) {
			$this->imported_table[$row_idx] = array_map( array( &$this, 'fix_encoding' ), $row );
		}
	}

} // class TablePress_Import