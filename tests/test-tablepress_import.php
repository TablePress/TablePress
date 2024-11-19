<?php
/**
 * Tests for the TablePress_Import class.
 *
 * @package TablePress
 * @subpackage Unit Tests
 * @since 2.0.0
 */

/**
 * Tests for the TablePress_Import class.
 *
 * @package TablePress
 * @subpackage Unit Tests
 * @since 2.0.0
 */
class TablePress_Test_TablePress_Import extends TablePress_TestCase {

	/**
	 * Instance of the TablePress_Import class.
	 *
	 * @since 2.0.0
	 */
	protected \TablePress_Import $importer;

	/**
	 * Loads the TablePress_Import class PHP file once for all tests.
	 *
	 * @since 2.0.0
	 */
	#[\Override]
	public static function set_up_before_class(): void {
		TablePress_TestCase::set_up_before_class();
		require_once TABLEPRESS_ABSPATH . 'classes/class-import.php';
	}

	/**
	 * Sets up an instance of the TablePress_Import class before every test.
	 *
	 * @since 2.0.0
	 */
	#[\Override]
	public function set_up(): void {
		parent::set_up();
		$this->importer = new TablePress_Import();
	}

	/**
	 * Teststhat TablePress_Import class is loaded.
	 *
	 * @since 2.0.0
	 */
	public function test_tablepress_import_class_loaded(): void {
		$this->assertTrue( class_exists( 'TablePress_Import', false ) );
	}

	/**
	 * Tests that a proper instance of the TablePress_Import class was created.
	 *
	 * @since 2.0.0
	 */
	public function test_tablepress_import_instance(): void {
		$this->assertInstanceOf( 'TablePress_Import', $this->importer );
	}

	/**
	 * Tests import with incomplete configuration.
	 *
	 * @since 2.0.0
	 */
	public function test_table_import_incomplete_configuration(): void {
		$import_config = array(
			'source'         => 'server',
			'server'         => '...',
			'type'           => 'add',
			'existing_table' => '',
			'legacy_import'  => true,
		);
		$import = $this->importer->run( $import_config );

		$this->assertInstanceOf( 'WP_Error', $import );
	}

	/**
	 * Provides test data for the XLSX import from server tests for the PHPSpreadsheet import class.
	 *
	 * @since 2.0.0
	 *
	 * @return array<int, array<int, string>> Test data.
	 */
	public function data_table_import_server_xlsx_phpspreadsheet(): array {
		return array(
			array( 'test-table.xlsx' ),
		);
	}

	/**
	 * Tests import of XLSX files from the server, using the PHPSpreadsheet import class.
	 *
	 * @dataProvider data_table_import_server_xlsx_phpspreadsheet
	 *
	 * @since 2.0.0
	 *
	 * @param string $file File name to import and compare to the expected table data.
	 */
	public function test_table_import_server_xlsx_phpspreadsheet( string $file ): void {
		$import_config = array(
			'source'         => 'server',
			'server'         => TABLEPRESS_TESTS_DATA_DIR . 'import/phpspreadsheet/' . $file,
			'type'           => 'add',
			'existing_table' => '',
			'legacy_import'  => false, // Use PHPSpreadsheet.
		);
		$import = $this->importer->run( $import_config );
		$imported_table_data = $import['tables'][0]['data'];

		$expected_table_data = array(
			array( 'First column', 'Middle column', 'Description', 'Last column' ),
			array( 'Text', 'Text', 'Simple word', 'Text' ),
			array( 'Two words', 'Two words', 'Multiple words', 'Two words' ),
			array( 'This is a sentence.', 'This is a sentence.', 'Sentence', 'This is a sentence.' ),
			array( '"Text"', '"Text"', 'Quotation marks around word', '"Text"' ),
			array( 'Text " Text', 'Text " Text', 'Quotation mark inside text', 'Text " Text' ),
			array( 'Text \\" Text', 'Text \\" Text', 'Escaped quotation mark inside text', 'Text \\" Text' ),
			array( '\\Text\\', '\\Text\\', 'Backslash around text', '\\Text\\' ),
			array( 'Text \\ Text', 'Text \\ Text', 'Backslash inside text', 'Text \\ Text' ),
			array( 'Text \\\\ Text', 'Text \\\\ Text', 'Escaped backslash inside text', 'Text \\\\ Text' ),
			array( 'Text,Text', 'Text,Text', 'Text with a comma', 'Text,Text' ),
			array( 'Text;Text', 'Text;Text', 'Text with a semicolon', 'Text;Text' ),
			array( 'Text	Text', 'Text	Text', 'Text with a tabular', 'Text	Text' ),
			array( "Two\nwords", "Two\nwords", 'Multiple words with line break', "Two\nwords" ),
			array( '', '', 'Empty cell', '' ),
			array( "\n", "\n", 'Single line break', "\n" ),
			array( '   Text   ', '   Text   ', 'Three spaces before and after text', '   Text   ' ),
			array( '123', '123', 'Integer', '123' ),
			array( '1230', '1230', 'Integer with trailing zero', '1230' ),
			array( '0123', '0123', 'Integer with leading zero', '0123' ),
			array( '   123   ', '   123   ', 'Three spaces before and after an integer', '   123   ' ),
			array( '123.5', '123.5', 'Float with comma and period', '123.5' ),
			array( '0123.5', '0123.5', 'Float with leading zero', '0123.5' ),
			array( '123.50', '123.50', 'Float with trailing zero', '123.50' ),
			array( '0123.50', '0123.50', 'Float with leading and trailing zero', '0123.50' ),
			array( '123,5', '123,5', 'Float with comma', '123,5' ),
			array( '0123,5', '0123,5', 'Float with comma with leading zero', '0123,5' ),
			array( '123,50', '123,50', 'Float with comma with trailing zero', '123,50' ),
			array( '1,234.50', '1,234.50', 'Float with thousands delimiter', '1,234.50' ),
			array( '$1,234.50', '$1,234.50', 'Currency value', '$1,234.50' ),
			array( '   123.50   ', '   123.50   ', 'Three spaces before and after a float', '   123.50   ' ),
			array( '07.06.2012', '07.06.2012', 'Date in dd.mm.yyyy format', '07.06.2012' ),
			array( '06/07/2012', '06/07/2012', 'Date in mm/dd/yyyy format', '06/07/2012' ),
			array( '<strong>Bold text</strong>', '<strong>Bold text</strong>', 'Simple HTML code', '<strong>Bold text</strong>' ),
			array( 'This is <strong>bold</strong> text.', 'This is <strong>bold</strong> text.', 'Simple HTML code inside text', 'This is <strong>bold</strong> text.' ),
			array( 'This is <strong>bold and <em>italics</em> and <u>underlined</u> text</strong>.', 'This is <strong>bold and <em>italics</em> and <u>underlined</u> text</strong>.', 'Nested HTML code', 'This is <strong>bold and <em>italics</em> and <u>underlined</u> text</strong>.' ),
			array( '<a href="https://tablepress.org/">https://tablepress.org/</a>', '<a href="https://tablepress.org/">https://tablepress.org/</a>', 'Link to tablepress.org', '<a href="https://tablepress.org/">https://tablepress.org/</a>' ),
			array( '<a href="https://tablepress.org/" title="Click here">Webseite</a>', '<a href="https://tablepress.org/" title="Click here">Webseite</a>', 'Link to tablepress.org with text and title', '<a href="https://tablepress.org/" title="Click here">Webseite</a>' ),
			array( '=2+4', '=2+4', 'Simple formula', '=2+4' ),
			array( '\'=A16+A17', '\'=A16+A17', 'Escaped formula (as text)', '\'=A16+A17' ),
			array( '=Result is {A17+A18}.', '=Result is {A17+A18}.', 'Formula in text', '=Result is {A17+A18}.' ),
			array( 'true', 'true', 'Text that resembles a boolean', 'true' ),
			array( 'FALSE', 'FALSE', 'Text that resembles a boolean, in uppercase', 'FALSE' ),
			array( '1', '1', 'Number that resembles a boolean', '1' ),
			array( '0', '0', 'Number that resembles a boolean', '0' ),
			array( '😀', '😀', 'Emoji', '😀' ),
			array( '✓', '✓', 'Checkmark symbol', '✓' ),
			array( '&#10003;', '&#10003;', 'Entity for checkmark symbol', '&#10003;' ),
			array( '', '', '', '' ),
			array( '', '', 'One row above is empty.', '' ),
			array( '3.141', '', 'Below are some rows with formatting', '' ),
			array( 'Merged/combined cells', '#colspan#', '#colspan#', '' ),
			array( '#rowspan#', '#span#', '#span#', '' ),
			array( '=SUM(A50:A51)', '<sup>Test superscript</sup>', '<sub>Test subscript</sub>', '' ),
			array( 'Merged/combined cells', '<del>strikethrough</del>', '<u>underline</u>', '' ),
			array( '#rowspan#', '<strong>bold</strong>', '<em>italics</em>', '' ),
			array( '<span style="color:#FF0000;">red text</span>', 'yellow background', 'right aligned', '' ),
			array( "Cell comment<div class=\"comment\">Microsoft Office User:\nComment text</div>", 'CO<strong><sub>2</sub></strong> subscript bold', 'center aligned', '' ),
			array( '<span style="color:#FF0000;">red text <span style="color:#FFFF00;">yellow text</span></span>', 'H<sub>2</sub>O', 'left aligned', '' ),
			array( '', 'H<sub>2</sub>O <span style="color:#00FA00;">green</span>', '<span style="color:#0432FF;">H<span style="color:#0432FF;"><sub>2</sub></span><span style="color:#0432FF;">O </span><span style="color:#FF2600;">gr</span>e<span style="color:#FF2600;">en</span></span>', '' ),
			array( '1', '2', 'Styled formula does not get inline HTML after import', '=A61+B61' ),
			array( '3.022', '-0.162', 'Floats that can cause floating point precision errors with the General format 1', '0.579' ),
			array( '-3.022', '-0.051', 'Floats that can cause floating point precision errors with the General format 2', '0.094' ),
		);

		$this->assertSame( $expected_table_data, $imported_table_data );
	}

	/**
	 * Provides test data for the XLS import from server tests for the PHPSpreadsheet import class.
	 *
	 * @since 2.0.0
	 *
	 * @return array<int, array<int, string>> Test data.
	 */
	public function data_table_import_server_xls_phpspreadsheet(): array {
		return array(
			array( 'test-table.xls' ),
		);
	}

	/**
	 * Tests import of XLS files from the server, using the PHPSpreadsheet import class.
	 *
	 * @dataProvider data_table_import_server_xls_phpspreadsheet
	 *
	 * @since 2.0.0
	 *
	 * @param string $file File name to import and compare to the expected table data.
	 */
	public function test_table_import_server_xls_phpspreadsheet( string $file ): void {
		$import_config = array(
			'source'         => 'server',
			'server'         => TABLEPRESS_TESTS_DATA_DIR . 'import/phpspreadsheet/' . $file,
			'type'           => 'add',
			'existing_table' => '',
			'legacy_import'  => false, // Use PHPSpreadsheet.
		);
		$import = $this->importer->run( $import_config );
		$imported_table_data = $import['tables'][0]['data'];

		$expected_table_data = array(
			array( 'First column', 'Middle column', 'Description', 'Last column' ),
			array( 'Text', 'Text', 'Simple word', 'Text' ),
			array( 'Two words', 'Two words', 'Multiple words', 'Two words' ),
			array( 'This is a sentence.', 'This is a sentence.', 'Sentence', 'This is a sentence.' ),
			array( '"Text"', '"Text"', 'Quotation marks around word', '"Text"' ),
			array( 'Text " Text', 'Text " Text', 'Quotation mark inside text', 'Text " Text' ),
			array( 'Text \\" Text', 'Text \\" Text', 'Escaped quotation mark inside text', 'Text \\" Text' ),
			array( '\\Text\\', '\\Text\\', 'Backslash around text', '\\Text\\' ),
			array( 'Text \\ Text', 'Text \\ Text', 'Backslash inside text', 'Text \\ Text' ),
			array( 'Text \\\\ Text', 'Text \\\\ Text', 'Escaped backslash inside text', 'Text \\\\ Text' ),
			array( 'Text,Text', 'Text,Text', 'Text with a comma', 'Text,Text' ),
			array( 'Text;Text', 'Text;Text', 'Text with a semicolon', 'Text;Text' ),
			array( 'Text	Text', 'Text	Text', 'Text with a tabular', 'Text	Text' ),
			array( "Two\nwords", "Two\nwords", 'Multiple words with line break', "Two\nwords" ),
			array( '', '', 'Empty cell', '' ),
			array( "\n", "\n", 'Single line break', "\n" ),
			array( '   Text   ', '   Text   ', 'Three spaces before and after text', '   Text   ' ),
			array( '123', '123', 'Integer', '123' ),
			array( '1230', '1230', 'Integer with trailing zero', '1230' ),
			array( '0123', '0123', 'Integer with leading zero', '0123' ),
			array( '   123   ', '   123   ', 'Three spaces before and after an integer', '   123   ' ),
			array( '123.5', '123.5', 'Float', '123.5' ),
			array( '0123.5', '0123.5', 'Float with leading zero', '0123.5' ),
			array( '123.50', '123.50', 'Float with trailing zero', '123.50' ),
			array( '0123.50', '0123.50', 'Float with leading and trailing zero', '0123.50' ),
			array( '123,5', '123,5', 'Float with comma', '123,5' ),
			array( '0123,5', '0123,5', 'Float with comma with leading zero', '0123,5' ),
			array( '123,50', '123,50', 'Float with comma with trailing zero', '123,50' ),
			array( '1,234.50', '1,234.50', 'Float with thousands delimiter', '1,234.50' ),
			array( '$1,234.50', '$1,234.50', 'Currency value', '$1,234.50' ),
			array( '   123.50   ', '   123.50   ', 'Three spaces before and after a float', '   123.50   ' ),
			array( '07.06.2012', '07.06.2012', 'Date in dd.mm.yyyy format', '07.06.2012' ),
			array( '06/07/2012', '06/07/2012', 'Date in mm/dd/yyyy format', '06/07/2012' ),
			array( '<strong>Bold text</strong>', '<strong>Bold text</strong>', 'Simple HTML code', '<strong>Bold text</strong>' ),
			array( 'This is <strong>bold</strong> text.', 'This is <strong>bold</strong> text.', 'Simple HTML code inside text', 'This is <strong>bold</strong> text.' ),
			array( 'This is <strong>bold and <em>italics</em> and <u>underlined</u> text</strong>.', 'This is <strong>bold and <em>italics</em> and <u>underlined</u> text</strong>.', 'Nested HTML code', 'This is <strong>bold and <em>italics</em> and <u>underlined</u> text</strong>.' ),
			array( '<a href="https://tablepress.org/">https://tablepress.org/</a>', '<a href="https://tablepress.org/">https://tablepress.org/</a>', 'Link to tablepress.org', '<a href="https://tablepress.org/">https://tablepress.org/</a>' ),
			array( '<a href="https://tablepress.org/">Webseite</a>', '<a href="https://tablepress.org/">Webseite</a>', 'Link to tablepress.org with text and title', '<a href="https://tablepress.org/">Webseite</a>' ), // Tooltips/ttiles like title="Click here" are not working with XLS.
			array( '=2+4', '=2+4', 'Simple formula', '=2+4' ),
			array( '=A16+A17', '=A16+A17', 'Escaped formula (as text)', '=A16+A17' ), // Escaped formulas (with a ' at the beginning) are not working with XLS.
			array( '=Result is {A17+A18}.', '=Result is {A17+A18}.', 'Formula in text', '=Result is {A17+A18}.' ),
			array( 'true', 'true', 'Text that resembles a boolean', 'true' ),
			array( 'FALSE', 'FALSE', 'Text that resembles a boolean, in uppercase', 'FALSE' ),
			array( '1', '1', 'Number that resembles a boolean', '1' ),
			array( '0', '0', 'Number that resembles a boolean', '0' ),
			array( '😀', '😀', 'Emoji', '😀' ),
			array( '✓', '✓', 'Checkmark symbol', '✓' ),
			array( '&#10003;', '&#10003;', 'Entity for checkmark symbol', '&#10003;' ),
			array( '', '', '', '' ),
			array( '', '', 'One row above is empty.', '' ),
			array( '3.141', '', 'Below are some rows with formatting', '' ),
			array( 'Merged/combined cells', '#colspan#', '#colspan#', '' ),
			array( '#rowspan#', '#span#', '#span#', '' ),
			array( '=SUM(A50:A51)', '<sup>Test superscript</sup>', '<sub>Test subscript</sub>', '' ),
			array( 'Merged/combined cells', '<del>strikethrough</del>', '<u>underline</u>', '' ),
			array( '#rowspan#', '<strong>bold</strong>', '<em>italics</em>', '' ),
			array( '<span style="color:#FF0000;">red text</span>', 'yellow background', 'right aligned', '' ),
			array( "Cell comment<div class=\"comment\">Microsoft Office User:\nComment text</div>", 'not working in XLS', 'center aligned', '' ), // Coloring with sub-/superscripts is not working properly in XLS.
			array( 'not working in XLS', 'not working in XLS', 'left aligned', '' ), // Coloring with sub-/superscripts is not working properly in XLS.
			array( '', 'not working in XLS', 'not working in XLS', '' ), // Coloring with sub-/superscripts is not working properly in XLS.
		);

		$this->assertSame( $expected_table_data, $imported_table_data );
	}

	/**
	 * Provides test data for the CSV, JSON, and HTML import from server tests for the PHPSpreadsheet import class.
	 *
	 * @since 2.0.0
	 *
	 * @return array<int, array<int, string>> Test data.
	 */
	public function data_table_import_server_csv_json_html_phpspreadsheet(): array {
		return array(
			array( 'test-table-comma.csv' ),
			array( 'test-table-comma.csv.zip' ),
			array( 'test-table-comma-crlf.csv' ),
			array( 'test-table-semicolon.csv' ),
			array( 'test-table-tabulator.csv' ),
			array( 'test-table.html' ),
			array( 'test-table-full.json' ),
			array( 'test-table-data-only.json' ),
		);
	}

	/**
	 * Tests import of CSV, JSON, and HTML files from the server, using the PHPSpreadsheet import class.
	 *
	 * @dataProvider data_table_import_server_csv_json_html_phpspreadsheet
	 *
	 * @since 2.0.0
	 *
	 * @param string $file File name to import and compare to the expected table data.
	 */
	public function test_table_import_server_csv_json_html_phpspreadsheet( string $file ): void {
		$import_config = array(
			'source'         => 'server',
			'server'         => TABLEPRESS_TESTS_DATA_DIR . 'import/phpspreadsheet/' . $file,
			'type'           => 'add',
			'existing_table' => '',
			'legacy_import'  => false, // Use PHPSpreadsheet.
		);
		$import = $this->importer->run( $import_config );
		$imported_table_data = $import['tables'][0]['data'];

		// In comparison to the expected table data from `test_table_import_server_csv_json_html_legacy()`, this does have three rows with unescaped spaces before and after text, integer, and float.
		$expected_table_data = array(
			array( 'First column', 'Middle column', 'Description', 'Last column' ),
			array( 'Text', 'Text', 'Simple word', 'Text' ),
			array( 'Two words', 'Two words', 'Multiple words', 'Two words' ),
			array( 'This is a sentence.', 'This is a sentence.', 'Sentence', 'This is a sentence.' ),
			array( '"Text"', '"Text"', 'Quotation marks around word', '"Text"' ),
			array( 'Text " Text', 'Text " Text', 'Quotation mark inside text', 'Text " Text' ),
			array( 'Text \\" Text', 'Text \\" Text', 'Escaped quotation mark inside text', 'Text \\" Text' ),
			array( '\\Text\\', '\\Text\\', 'Backslash around text', '\\Text\\' ),
			array( 'Text \\ Text', 'Text \\ Text', 'Backslash inside text', 'Text \\ Text' ),
			array( 'Text \\\\ Text', 'Text \\\\ Text', 'Escaped backslash inside text', 'Text \\\\ Text' ),
			array( 'Text,Text', 'Text,Text', 'Text with a comma', 'Text,Text' ),
			array( 'Text;Text', 'Text;Text', 'Text with a semicolon', 'Text;Text' ),
			array( 'Text	Text', 'Text	Text', 'Text with a tabular', 'Text	Text' ),
			array( "Two\nwords", "Two\nwords", 'Multiple words with line break', "Two\nwords" ),
			array( '', '', 'Empty cell', '' ),
			array( '', '', 'Empty cell (unescaped)', '' ),
			array( "\n", "\n", 'Single line break', "\n" ),
			array( '   Text   ', '   Text   ', 'Three spaces before and after text', '   Text   ' ),
			array( '   Text   ', '   Text   ', 'Three spaces before and after text (unescaped)', '   Text   ' ),
			array( '123', '123', 'Integer', '123' ),
			array( '1230', '1230', 'Integer with trailing zero', '1230' ),
			array( '0123', '0123', 'Integer with leading zero', '0123' ),
			array( '0123', '0123', 'Integer with leading zero (unescaped)', '0123' ),
			array( '   123   ', '   123   ', 'Three spaces before and after an integer', '   123   ' ),
			array( '   123   ', '   123   ', 'Three spaces before and after an integer (unescaped)', '   123   ' ),
			array( '123.5', '123.5', 'Float', '123.5' ),
			array( '0123.5', '0123.5', 'Float with leading zero', '0123.5' ),
			array( '123.50', '123.50', 'Float with trailing zero', '123.50' ),
			array( '0123.50', '0123.50', 'Float with leading and trailing zero', '0123.50' ),
			array( '123,5', '123,5', 'Float with comma', '123,5' ),
			array( '0123,5', '0123,5', 'Float with comma with leading zero', '0123,5' ),
			array( '123,50', '123,50', 'Float with comma with trailing zero', '123,50' ),
			array( '1,234.50', '1,234.50', 'Float with comma as thousands delimiter', '1,234.50' ),
			array( '$1,234.50', '$1,234.50', 'Currency value', '$1,234.50' ),
			array( '   123.50   ', '   123.50   ', 'Three spaces before and after a float', '   123.50   ' ),
			array( '   123.50   ', '   123.50   ', 'Three spaces before and after a float (unescaped)', '   123.50   ' ),
			array( '07.06.2012', '07.06.2012', 'Date in dd.mm.yyyy format', '07.06.2012' ),
			array( '06/07/2012', '06/07/2012', 'Date in mm/dd/yyyy format', '06/07/2012' ),
			array( '<strong>Bold text</strong>', '<strong>Bold text</strong>', 'Simple HTML code', '<strong>Bold text</strong>' ),
			array( 'This is <strong>bold</strong> text.', 'This is <strong>bold</strong> text.', 'Simple HTML code inside text', 'This is <strong>bold</strong> text.' ),
			array( 'This is <strong>bold and <em>italics</em> and <u>underlined</u> text</strong>.', 'This is <strong>bold and <em>italics</em> and <u>underlined</u> text</strong>.', 'Nested HTML code', 'This is <strong>bold and <em>italics</em> and <u>underlined</u> text</strong>.' ),
			array( '<a href="https://tablepress.org/">tablepress.org</a>', '<a href="https://tablepress.org/">tablepress.org</a>', 'HTML link to tablepress.org', '<a href="https://tablepress.org/">tablepress.org</a>' ),
			array( '<a href="https://tablepress.org/" target="_blank" rel="noopener">tablepress.org</a>', '<a href="https://tablepress.org/" target="_blank" rel="noopener">tablepress.org</a>', 'HTML link to tablepress.org, with target _blank', '<a href="https://tablepress.org/" target="_blank" rel="noopener">tablepress.org</a>' ),
			array( '=A17+A18', '=A17+A18', 'Simple formula', '=A17+A18' ),
			array( '\'=A16+A17', '\'=A16+A17', 'Escaped formula (as text)', '\'=A16+A17' ),
			array( '=Result is {A17+A18}.', '=Result is {A17+A18}.', 'Formula in text', '=Result is {A17+A18}.' ),
			array( 'true', 'true', 'Text that resembles a boolean', 'true' ),
			array( 'FALSE', 'FALSE', 'Text that resembles a boolean, in uppercase', 'FALSE' ),
			array( '1', '1', 'Number that resembles a boolean', '1' ),
			array( '0', '0', 'Number that resembles a boolean', '0' ),
			array( '😀', '😀', 'Emoji', '😀' ),
			array( '✓', '✓', 'Checkmark symbol', '✓' ),
			array( '&#10003;', '&#10003;', 'Entity for checkmark symbol', '&#10003;' ),
			array( '', '', '', '' ),
			array( '', '', '', '' ),
			array( '', '', 'Two rows above are empty (escaped and unescaped).', '' ),
		);

		$this->assertSame( $expected_table_data, $imported_table_data );
	}

	/**
	 * Provides test data for the CSV (UTF-8 with BOM) with HTML content import from server tests for the PHPSpreadsheet import class.
	 *
	 * @since 2.4.4
	 *
	 * @return array<int, array<int, string>> Test data.
	 */
	public function data_table_import_server_csv_bom_phpspreadsheet(): array {
		return array(
			array( 'test-table-comma-utf8-bom.csv' ),
			array( 'test-table-comma-crlf-utf8-bom.csv' ),
		);
	}

	/**
	 * Tests import of CSV (UTF-8 with BOM) files with HTML content from the server, using the PHPSpreadsheet import class.
	 *
	 * @dataProvider data_table_import_server_csv_bom_phpspreadsheet
	 *
	 * @since 2.4.4
	 *
	 * @param string $file File name to import and compare to the expected table data.
	 */
	public function test_table_import_server_csv_bom_phpspreadsheet( string $file ): void {
		$import_config = array(
			'source'         => 'server',
			'server'         => TABLEPRESS_TESTS_DATA_DIR . 'import/phpspreadsheet/' . $file,
			'type'           => 'add',
			'existing_table' => '',
			'legacy_import'  => false, // Use PHPSpreadsheet.
		);
		$import = $this->importer->run( $import_config );
		$imported_table_data = $import['tables'][0]['data'];

		// In comparison to the expected table data from `test_table_import_server_csv_json_html_legacy()`, this does have three rows with unescaped spaces before and after text, integer, and float.
		$expected_table_data = array(
			array( 'First column', 'Middle column', 'Description', 'Last column' ),
			array( '<a href="https://example.com/">Text</a>', '<a href="https://example.com/">Text</a>', 'HTML with URL', '<a href="https://example.com/">Text</a>' ),
		);

		$this->assertSame( $expected_table_data, $imported_table_data );
	}

	/**
	 * Provide test data for the CSV, JSON, and HTML import from server tests for the legacy import class.
	 *
	 * @since 2.0.0
	 *
	 * @return array<int, array<int, string>> Test data.
	 */
	public function data_table_import_server_csv_json_html_legacy(): array {
		return array(
			array( 'test-table-comma.csv' ),
			array( 'test-table-comma.csv.zip' ),
			array( 'test-table-comma-crlf.csv' ),
			array( 'test-table-semicolon.csv' ),
			array( 'test-table-tabulator.csv' ),
			array( 'test-table.html' ),
			array( 'test-table-full.json' ),
			array( 'test-table-data-only.json' ),
		);
	}

	/**
	 * Test import of CSV, JSON, and HTML files from the server, using the legacy import class.
	 *
	 * @dataProvider data_table_import_server_csv_json_html_legacy
	 *
	 * @since 2.0.0
	 *
	 * @param string $file File name to import and compare to the expected table data.
	 */
	public function test_table_import_server_csv_json_html_legacy( string $file ): void {
		$import_config = array(
			'source'         => 'server',
			'server'         => TABLEPRESS_TESTS_DATA_DIR . 'import/legacy/' . $file,
			'type'           => 'add',
			'existing_table' => '',
			'legacy_import'  => true, // Explicitly use the legacy import class.
		);
		$import = $this->importer->run( $import_config );
		$imported_table_data = $import['tables'][0]['data'];

		// In comparison to the expected table data from `test_table_import_server_csv_json_html_phpspreadsheet()`, this does not have three rows with unescaped spaces before and after text, integer, and float.
		$expected_table_data = array(
			array( 'First column', 'Middle column', 'Description', 'Last column' ),
			array( 'Text', 'Text', 'Simple word', 'Text' ),
			array( 'Two words', 'Two words', 'Multiple words', 'Two words' ),
			array( 'This is a sentence.', 'This is a sentence.', 'Sentence', 'This is a sentence.' ),
			array( '"Text"', '"Text"', 'Quotation marks around word', '"Text"' ),
			array( 'Text " Text', 'Text " Text', 'Quotation mark inside text', 'Text " Text' ),
			array( 'Text \\" Text', 'Text \\" Text', 'Escaped quotation mark inside text', 'Text \\" Text' ),
			array( '\\Text\\', '\\Text\\', 'Backslash around text', '\\Text\\' ),
			array( 'Text \\ Text', 'Text \\ Text', 'Backslash inside text', 'Text \\ Text' ),
			array( 'Text \\\\ Text', 'Text \\\\ Text', 'Escaped backslash inside text', 'Text \\\\ Text' ),
			array( 'Text,Text', 'Text,Text', 'Text with a comma', 'Text,Text' ),
			array( 'Text;Text', 'Text;Text', 'Text with a semicolon', 'Text;Text' ),
			array( 'Text	Text', 'Text	Text', 'Text with a tabular', 'Text	Text' ),
			array( "Two\nwords", "Two\nwords", 'Multiple words with line break', "Two\nwords" ),
			array( '', '', 'Empty cell', '' ),
			array( '', '', 'Empty cell (unescaped)', '' ),
			array( "\n", "\n", 'Single line break', "\n" ),
			array( '   Text   ', '   Text   ', 'Three spaces before and after text', '   Text   ' ),
			array( '123', '123', 'Integer', '123' ),
			array( '1230', '1230', 'Integer with trailing zero', '1230' ),
			array( '0123', '0123', 'Integer with leading zero', '0123' ),
			array( '0123', '0123', 'Integer with leading zero (unescaped)', '0123' ),
			array( '   123   ', '   123   ', 'Three spaces before and after an integer', '   123   ' ),
			array( '123.5', '123.5', 'Float', '123.5' ),
			array( '0123.5', '0123.5', 'Float with leading zero', '0123.5' ),
			array( '123.50', '123.50', 'Float with trailing zero', '123.50' ),
			array( '0123.50', '0123.50', 'Float with leading and trailing zero', '0123.50' ),
			array( '123,5', '123,5', 'Float with comma', '123,5' ),
			array( '0123,5', '0123,5', 'Float with comma with leading zero', '0123,5' ),
			array( '123,50', '123,50', 'Float with comma with trailing zero', '123,50' ),
			array( '1,234.50', '1,234.50', 'Float with comma as thousands delimiter', '1,234.50' ),
			array( '$1,234.50', '$1,234.50', 'Currency value', '$1,234.50' ),
			array( '   123.50   ', '   123.50   ', 'Three spaces before and after a float', '   123.50   ' ),
			array( '07.06.2012', '07.06.2012', 'Date in dd.mm.yyyy format', '07.06.2012' ),
			array( '06/07/2012', '06/07/2012', 'Date in mm/dd/yyyy format', '06/07/2012' ),
			array( '<strong>Bold text</strong>', '<strong>Bold text</strong>', 'Simple HTML code', '<strong>Bold text</strong>' ),
			array( 'This is <strong>bold</strong> text.', 'This is <strong>bold</strong> text.', 'Simple HTML code inside text', 'This is <strong>bold</strong> text.' ),
			array( 'This is <strong>bold and <em>italics</em> and <u>underlined</u> text</strong>.', 'This is <strong>bold and <em>italics</em> and <u>underlined</u> text</strong>.', 'Nested HTML code', 'This is <strong>bold and <em>italics</em> and <u>underlined</u> text</strong>.' ),
			array( '<a href="https://tablepress.org/">tablepress.org</a>', '<a href="https://tablepress.org/">tablepress.org</a>', 'HTML link to tablepress.org', '<a href="https://tablepress.org/">tablepress.org</a>' ),
			array( '<a href="https://tablepress.org/" target="_blank" rel="noopener">tablepress.org</a>', '<a href="https://tablepress.org/" target="_blank" rel="noopener">tablepress.org</a>', 'HTML link to tablepress.org, with target _blank', '<a href="https://tablepress.org/" target="_blank" rel="noopener">tablepress.org</a>' ),
			array( '=A17+A18', '=A17+A18', 'Simple formula', '=A17+A18' ),
			array( '\'=A16+A17', '\'=A16+A17', 'Escaped formula (as text)', '\'=A16+A17' ),
			array( '=Result is {A17+A18}.', '=Result is {A17+A18}.', 'Formula in text', '=Result is {A17+A18}.' ),
			array( 'true', 'true', 'Text that resembles a boolean', 'true' ),
			array( 'FALSE', 'FALSE', 'Text that resembles a boolean, in uppercase', 'FALSE' ),
			array( '1', '1', 'Number that resembles a boolean', '1' ),
			array( '0', '0', 'Number that resembles a boolean', '0' ),
			array( '😀', '😀', 'Emoji', '😀' ),
			array( '✓', '✓', 'Checkmark symbol', '✓' ),
			array( '&#10003;', '&#10003;', 'Entity for checkmark symbol', '&#10003;' ),
			array( '', '', '', '' ),
			array( '', '', '', '' ),
			array( '', '', 'Two rows above are empty (escaped and unescaped).', '' ),
		);

		$this->assertSame( $expected_table_data, $imported_table_data );
	}

} // class TablePress_Test_TablePress_Import
