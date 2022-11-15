<?php

/**
 * Tests for the TablePress_Export class.
 * @package TablePress
 * @subpackage Unit Tests
 * @since 2.0.0
 */
class TablePress_Test_TablePress_Export extends TablePress_TestCase {

	/**
	 * Instance of the TablePress_Export class.
	 *
	 * @since 2.0.0
	 * @var TablePress_Export
	 */
	protected $exporter;

	/**
	 * Load the TablePress_Export class PHP file once for all tests.
	 *
	 * @since 2.0.0
	 */
	public static function set_up_before_class() {
		TablePress_TestCase::set_up_before_class();
		require_once TABLEPRESS_ABSPATH . 'classes/class-export.php';
	}

	/**
	 * Set up an instance of the TablePress_Export class before every test.
	 *
	 * @since 2.0.0
	 */
	public function set_up() {
		parent::set_up();
		$this->exporter = new TablePress_Export();
	}

	/**
	 * Test that TablePress_Export class is loaded.
	 *
	 * @since 2.0.0
	 */
	public function test_tablepress_export_class_loaded() {
		$this->assertTrue( class_exists( 'TablePress_Export', false ) );
	}

	/**
	 * Test that a proper instance of the TablePress_Export class was created.
	 *
	 * @since 2.0.0
	 */
	public function test_tablepress_export_instance() {
		$this->assertInstanceOf( 'TablePress_Export', $this->exporter );
	}

	/**
	 * Provide test data for the export.
	 *
	 * @since 2.0.0
	 *
	 * @return array Test data.
	 */
	public function data_table_export() {
		$export_table = array(
			'id'            => false,
			'name'          => '',
			'description'   => '',
			'data'          => array(
				array( 'Text', '  with whitespace  ', "and\nline break" ),
				array( '123', '123.456', '123,456' ),
				array( 'a semicolon: (;)', 'a comma: (,)', 'a tab: (	)' ),
				array( '=3+4', "=5+cmd|' /C notepad'!'A1'", '@IMPORTXML(...)' ),
				array( 'HTML: <em>an empty cell follows:</em>', '', '<a href="https://example.com/">Link</a>' ),
			),
			'last_modified' => '2022-11-14 14:00:00',
			'author'        => '1',
			'options'       => array(
				'last_editor'                 => '1',
				'table_head'                  => true,
				'table_foot'                  => false,
				'alternating_row_colors'      => true,
				'row_hover'                   => true,
				'print_name'                  => false,
				'print_name_position'         => 'above',
				'print_description'           => false,
				'print_description_position'  => 'below',
				'extra_css_classes'           => '',
				'use_datatables'              => true,
				'datatables_sort'             => true,
				'datatables_filter'           => true,
				'datatables_paginate'         => true,
				'datatables_lengthchange'     => true,
				'datatables_paginate_entries' => 10,
				'datatables_info'             => true,
				'datatables_scrollx'          => false,
				'datatables_custom_commands'  => '',
			),
			'visibility'    => array(
				'rows'    => array( 1, 1, 1, 1, 1 ),
				'columns' => array( 1, 1, 1 ),
			),
		);

		$expected_data = array();
		$expected_data['csv-semicolon'] = <<<DATA
Text;"  with whitespace  ";"and\nline break"
123;123.456;123,456
"a semicolon: (;)";a comma: (,);a tab: (	)
=3+4;'=5+cmd|' /C notepad'!'A1';'@IMPORTXML(...)
HTML: <em>an empty cell follows:</em>;;"<a href=""https://example.com/"">Link</a>"

DATA;
		$expected_data['csv-comma'] = <<<DATA
Text,"  with whitespace  ","and\nline break"
123,123.456,"123,456"
a semicolon: (;),"a comma: (,)",a tab: (	)
=3+4,'=5+cmd|' /C notepad'!'A1','@IMPORTXML(...)
HTML: <em>an empty cell follows:</em>,,"<a href=""https://example.com/"">Link</a>"

DATA;
		$expected_data['csv-tab'] = <<<DATA
Text	"  with whitespace  "	"and\nline break"
123	123.456	123,456
a semicolon: (;)	a comma: (,)	"a tab: (	)"
=3+4	'=5+cmd|' /C notepad'!'A1'	'@IMPORTXML(...)
HTML: <em>an empty cell follows:</em>		"<a href=""https://example.com/"">Link</a>"

DATA;
		$expected_data['html'] = <<<DATA
<table>
	<thead>
		<tr>
			<th>Text</th>
			<th>  with whitespace  </th>
			<th>and\nline break</th>
		</tr>
	</thead>
	<tbody>
		<tr>
			<td>123</td>
			<td>123.456</td>
			<td>123,456</td>
		</tr>
		<tr>
			<td>a semicolon: (;)</td>
			<td>a comma: (,)</td>
			<td>a tab: (	)</td>
		</tr>
		<tr>
			<td>=3+4</td>
			<td>=5+cmd|' /C notepad'!'A1'</td>
			<td>@IMPORTXML(...)</td>
		</tr>
		<tr>
			<td>HTML: <em>an empty cell follows:</em></td>
			<td></td>
			<td><a href="https://example.com/">Link</a></td>
		</tr>
	</tbody>
</table>

DATA;
		$expected_data['json'] = <<<'DATA'
{"id":false,"name":"","description":"","data":[["Text","  with whitespace  ","and\nline break"],["123","123.456","123,456"],["a semicolon: (;)","a comma: (,)","a tab: (\t)"],["=3+4","=5+cmd|' /C notepad'!'A1'","@IMPORTXML(...)"],["HTML: <em>an empty cell follows:</em>","","<a href=\"https://example.com/\">Link</a>"]],"last_modified":"2022-11-14 14:00:00","author":"1","options":{"last_editor":"1","table_head":true,"table_foot":false,"alternating_row_colors":true,"row_hover":true,"print_name":false,"print_name_position":"above","print_description":false,"print_description_position":"below","extra_css_classes":"","use_datatables":true,"datatables_sort":true,"datatables_filter":true,"datatables_paginate":true,"datatables_lengthchange":true,"datatables_paginate_entries":10,"datatables_info":true,"datatables_scrollx":false,"datatables_custom_commands":""},"visibility":{"rows":[1,1,1,1,1],"columns":[1,1,1]}}
DATA;

		return array(
			'CSV with semicolon' => array(
				'table'         => $export_table,
				'export_format' => 'csv',
				'csv_delimiter' => ';',
				'expected_data' => $expected_data['csv-semicolon'],
			),
			'CSV with comma'     => array(
				'table'         => $export_table,
				'export_format' => 'csv',
				'csv_delimiter' => ',',
				'expected_data' => $expected_data['csv-comma'],
			),
			'CSV with tab'       => array(
				'table'         => $export_table,
				'export_format' => 'csv',
				'csv_delimiter' => 'tab',
				'expected_data' => $expected_data['csv-tab'],
			),
			'HTML'               => array(
				'table'         => $export_table,
				'export_format' => 'html',
				'csv_delimiter' => '',
				'expected_data' => $expected_data['html'],
			),
			'JSON'               => array(
				'table'         => $export_table,
				'export_format' => 'json',
				'csv_delimiter' => '',
				'expected_data' => $expected_data['json'],
			),
		);
	}

	/**
	 * Test export of tables to available export formats.
	 *
	 * @dataProvider data_table_export
	 *
	 * @since 2.0.0
	 *
	 * @param string $file File name to import and compare to the expected table data.
	 */
	public function test_table_export( $table, $export_format, $csv_delimiter, $expected_data ) {
		$exported_table_data = $this->exporter->export_table( $table, $export_format, $csv_delimiter );

		$this->assertSame( $expected_data, $exported_table_data );
	}

} // class TablePress_Test_TablePress_Export
