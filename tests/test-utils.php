<?php
/**
 * Tests for basic TablePress utility functions.
 *
 * @package TablePress
 * @subpackage Unit Tests
 * @since 1.1.0
 */

declare(strict_types=1);

/**
 * Tests for basic TablePress utility functions.
 *
 * @package TablePress
 * @subpackage Unit Tests
 * @since 1.1.0
 */
class TablePress_Test_TablePress_Utils extends TablePress_TestCase {

	/**
	 * Tests that the TablePress Controller object was initialized.
	 *
	 * @since 1.4.0
	 */
	public function test_controller_was_set_up(): void {
		$this->assertTrue( is_object( TablePress::$controller ) );
	}

	/**
	 * Tests that names of nonces are generated properly.
	 *
	 * @since 1.1.0
	 */
	public function test_nonce(): void {
		$this->assertSame( 'tablepress_foo_bar', TablePress::nonce( 'foo', 'bar' ) );
		$this->assertSame( 'tablepress_foo', TablePress::nonce( 'foo' ) );
		$this->assertSame( 'tablepress_foo', TablePress::nonce( 'foo', false ) );
	}

	/**
	 * Tests that column names (letters) are converted to their positions (numbers) properly.
	 *
	 * @dataProvider data_letter_to_number
	 *
	 * @since 1.1.0
	 *
	 * @param string $letter Letter to convert.
	 * @param int    $number Conversion result number.
	 */
	public function test_letter_to_number( string $letter, int $number ): void {
		$this->assertSame( $number, TablePress::letter_to_number( $letter ) );
	}

	/**
	 * Provides test data for the letter to number test.
	 *
	 * @since 1.4.0
	 *
	 * @return array<int, array<string, int>> Test data.
	 */
	public function data_letter_to_number(): array {
		// phpcs:disable WordPress.Arrays.CommaAfterArrayItem.SpaceAfterComma, WordPress.Arrays.ArrayDeclarationSpacing.SpaceAfterArrayOpener, NormalizedArrays.Arrays.ArrayBraceSpacing.SpaceAfterArrayOpenerSingleLine, Universal.WhiteSpace.CommaSpacing.TooMuchSpaceAfter
		return array(
			array(    '',     0 ),
			array(   '-',     0 ),
			array(   'a',     1 ),
			array( '“a”',     1 ),
			array(   'A',     1 ),
			array(  'A,',     1 ),
			array(   'Z',    26 ),
			array(  'Z1',    26 ),
			array(  'AA',    27 ),
			array(  'Aa',    27 ),
			array(  'aA',    27 ),
			array(  'aa',    27 ),
			array(  'AZ',    52 ),
			array(  'BA',    53 ),
			array( 'AAA',   703 ),
			array( 'ZZZ', 18278 ),
			array( 'zzz', 18278 ),
		);
		// phpcs:enable
	}

	/**
	 * Tests that columns positions (numbers) are converted to their names (letters) properly.
	 *
	 * @dataProvider data_number_to_letter
	 *
	 * @since 1.1.0
	 *
	 * @param int    $number Number to convert.
	 * @param string $letter Conversion result letter.
	 */
	public function test_number_to_letter( int $number, string $letter ): void {
		$this->assertSame( $letter, TablePress::number_to_letter( $number ) );
	}

	/**
	 * Provides test data for the number to letter test.
	 *
	 * @since 1.4.0
	 *
	 * @return array<int, array{int, string}> Test data.
	 */
	public function data_number_to_letter(): array {
		// phpcs:disable WordPress.Arrays.CommaAfterArrayItem.SpaceAfterComma, WordPress.Arrays.ArrayDeclarationSpacing.SpaceAfterArrayOpener, NormalizedArrays.Arrays.ArrayBraceSpacing.SpaceAfterArrayOpenerSingleLine, Universal.WhiteSpace.CommaSpacing.TooMuchSpaceAfter
		return array(
			array(    -1,    '' ),
			array(     0,    '' ),
			array(     1,   'A' ),
			array(    26,   'Z' ),
			array(    27,  'AA' ),
			array(    52,  'AZ' ),
			array(    53,  'BA' ),
			array(   703, 'AAA' ),
			array( 18278, 'ZZZ' ),
		);
		// phpcs:enable
	}

	/**
	 * Tests that range strings are converted to arrays of numbers properly.
	 *
	 * @dataProvider data_convert_range_string_to_array
	 *
	 * @since 3.4.0
	 *
	 * @param string $value      The range string.
	 * @param int    $min_number The minimum allowed number for the range, usually 0 or 1.
	 * @param int    $max_number The maximum allowed number for the range, usually the number of rows or columns in the table.
	 * @param int[]  $range      The expected array of numbers in the range.
	 */
	public function test_convert_range_string_to_array( string $value, int $min_number, int $max_number, array $range ): void {
		$this->assertSame( $range, TablePress::convert_range_to_array( $value, $min_number, $max_number ) );
	}

	/**
	 * Provides test data for the convert range string to array test.
	 *
	 * @since 3.4.0
	 *
	 * @return array<int, array{string, int, int, array}> Test data.
	 */
	public function data_convert_range_string_to_array(): array {
		// phpcs:disable WordPress.Arrays.CommaAfterArrayItem.SpaceAfterComma, WordPress.Arrays.ArrayDeclarationSpacing.SpaceAfterArrayOpener, NormalizedArrays.Arrays.ArrayBraceSpacing.SpaceAfterArrayOpenerSingleLine, Universal.WhiteSpace.CommaSpacing.TooMuchSpaceAfter
		return array(
			array(     '1', 1, 100, array() ),
			array(    '-2', 1, 100, array() ),
			array(    '3-', 1, 100, array() ),
			array(     '-', 1, 100, array() ),
			array(    '--', 1, 100, array() ),
			array(  '-2-4', 1, 100, array() ),
			array( '1-2-4', 1, 100, array() ),
			array(   '1-5', 9, 100, array() ),
			array(   'C-G', 1,   2, array() ),
			array(   '1-5', 1, 100, array( 1, 2, 3, 4, 5 ) ),
			array(   '5-1', 1, 100, array( 5, 4, 3, 2, 1 ) ),
			array(   'C-G', 1, 100, array( 3, 4, 5, 6, 7 ) ),
			array(   'G-C', 1, 100, array( 7, 6, 5, 4, 3 ) ),
			array(   'B-6', 1, 100, array( 2, 3, 4, 5, 6 ) ),
			array(   '6-B', 1, 100, array( 6, 5, 4, 3, 2 ) ),
			array(   '1-5', 1,   3, array( 1, 2, 3 ) ),
			array(   '5-1', 1,   3, array( 3, 2, 1 ) ),
			array(   'C-G', 1,   3, array( 3 ) ),
			array(   'G-C', 1,   3, array( 3 ) ),
			array(   'B-6', 1,   3, array( 2, 3 ) ),
			array(   '6-B', 4, 100, array( 6, 5, 4 ) ),
			array(   '1-5', 4, 100, array( 4, 5 ) ),
			array(   '5-1', 4, 100, array( 5, 4 ) ),
			array(   'C-G', 4, 100, array( 4, 5, 6, 7 ) ),
			array(   'G-C', 4, 100, array( 7, 6, 5, 4 ) ),
			array(   'B-6', 4, 100, array( 4, 5, 6 ) ),
			array(   '6-B', 4, 100, array( 6, 5, 4 ) ),
		);
		// phpcs:enable
	}

	/**
	 * Tests that comma-separated lists of row/column numbers/letters/ranges are converted to arrays of numbers properly.
	 *
	 * @dataProvider data_convert_row_column_list_to_array
	 *
	 * @since 3.4.0
	 *
	 * @param string $item_list       A comma-separated list of row/column numbers/letters/ranges.
	 * @param int    $min_number      The minimum allowed number for ranges, usually 0 or 1.
	 * @param int    $max_number      The maximum allowed number for ranges, usually the number of rows or columns in the table.
	 * @param array  $post_processing An array of post-processing operations to apply to the resulting array.
	 * @param int[]  $range           The expected array of numbers in the range.
	 */
	public function test_convert_row_column_list_to_array( string $item_list, int $min_number, int $max_number, array $post_processing, array $range ): void {
		$this->assertSame( $range, TablePress::convert_row_column_list_to_array( $item_list, $min_number, $max_number, $post_processing ) );
	}

	/**
	 * Provides test data for the convert row/column list to array test.
	 *
	 * @since 3.4.0
	 *
	 * @return array<int, array{string, int, int, array{unique?: bool, sort?: bool, zero-based?: bool}, array}> Test data.
	 */
	public function data_convert_row_column_list_to_array(): array {
		// phpcs:disable WordPress.Arrays.CommaAfterArrayItem.SpaceAfterComma, WordPress.Arrays.ArrayDeclarationSpacing.SpaceAfterArrayOpener, NormalizedArrays.Arrays.ArrayBraceSpacing.SpaceAfterArrayOpenerSingleLine, Universal.WhiteSpace.CommaSpacing.TooMuchSpaceAfter
		return array(
			array(          '', 1, 100, array(), array() ),
			array(        '0 ', 1, 100, array(), array() ),
			array(         '1', 1, 100, array(), array( 1 ) ),
			array(        '-1', 1, 100, array(), array() ),
			array(       ' C ', 1, 100, array(), array( 3 ) ),
			array(       '2,1', 1, 100, array(), array( 1, 2 ) ),
			array(   ' 1, ,,2', 1,   1, array(), array( 1 ) ),
			array(     '1 - 5', 1, 100, array(), array( 1, 2, 3, 4, 5 ) ),
			array(      'F-B,', 1,   5, array(), array( 2, 3, 4, 5 ) ),
			array( '1-D,9-8,6', 1, 100, array(), array( 1, 2, 3, 4, 6, 8, 9 ) ),
			array( '4,2,1,5,4', 2,   8, array( 'unique' => true ), array( 2, 4, 5 ) ),
			array( '4,2,1,5,4', 2,   8, array( 'unique' => false ), array( 2, 4, 4, 5 ) ),
			array( '4,2,1,5,4', 2,   8, array( 'sort' => true ), array( 2, 4, 5 ) ),
			array( '4,2,1,5,4', 2,   8, array( 'sort' => false ), array( 4, 2, 5 ) ),
			array(   '5-3,1,2', 1,   8, array( 'sort' => false ), array( 5, 4, 3, 1, 2 ) ),
			array( '4,2,1,5,4', 2,   8, array( 'zero-based' => true ), array( 1, 3, 4 ) ),
			array( '4,2,1,5,4', 2,   8, array( 'zero-based' => false ), array( 2, 4, 5 ) ),
		);
		// phpcs:enable
	}

	/**
	 * Tests that the screen URLs for non-toplevel admin menu entries are correct.
	 *
	 * @since 1.1.0
	 */
	public function test_url_not_toplevel(): void {
		TablePress::$controller->is_top_level_page = false;
		TablePress::$controller->parent_page = 'index.php';

		$this->assertSame( 'http://example.org/wp-admin/index.php?page=tablepress&action=list', TablePress::url() );
		$this->assertSame( 'http://example.org/wp-admin/index.php?page=tablepress&action=list', TablePress::url( array(), false ) );
	}

	/**
	 * Tests that the screen URLs for toplevel admin menu entries are correct.
	 *
	 * @since 1.1.0
	 */
	public function test_url_toplevel(): void {
		TablePress::$controller->is_top_level_page = true;
		TablePress::$controller->parent_page = 'middle';

		$this->assertSame( 'http://example.org/wp-admin/admin.php?page=tablepress', TablePress::url() );
		$this->assertSame( 'http://example.org/wp-admin/admin.php?page=tablepress', TablePress::url( array(), false ) );
	}

	/**
	 * Tests the extraction of top-level keys from a JavaScript object string.
	 *
	 * @since 3.0.0
	 *
	 * @covers TablePress::extract_keys_from_js_object_string()
	 */
	public function test_extract_keys_from_js_object_string(): void {
		$js_object_string = "{
			unquotedBool: true,
			'singleQuotedBool': true,
			\"doubleQuotedBool\": true,

			unquotedInt: 3,
			'singleQuotedInt': 3,
			\"doubleQuotedInt\": 3,

			/* Multi-line comment \"notCapturedMultiLineComment\": 4, */

			unquotedString1: \"baz\",
			'singleQuotedString1': \"baz\",
			\"doubleQuotedString1\": \"baz\",

			unquotedString2: 'bar',
			'singleQuotedString2': 'bar',
			\"doubleQuotedString2\": 'bar',

			// Single-line comment \"notCapturedSingleLineComment\": 'baz',

			unquotedFunction: function( test ) { return { notCaptured1: 3, 'notCaptured2': 'no', \"notCaptured3\": \"no\" }; },
			'singleQuotedFunction': function( test ) { return { notCaptured1: 3, 'notCaptured2': 'no', \"notCaptured3\": \"no\" }; },
			\"doubleQuotedFunction\": function( test ) { return { notCaptured1: 3, 'notCaptured2': 'no', \"notCaptured3\": \"no\" }; },

			unquotedArrowFunction1: ( test ) => { return { notCaptured1: 3, 'notCaptured2': 'no', \"notCaptured3\": \"no\" }; },
			'singleQuotedArrowFunction1': ( test ) => { return { notCaptured1: 3, 'notCaptured2': 'no', \"notCaptured3\": \"no\" }; },
			\"doubleQuotedArrowFunction1\": ( test ) => { return { notCaptured1: 3, 'notCaptured2': 'no', \"notCaptured3\": \"no\" }; },

			unquotedArrowFunction2: test => { return { notCaptured1: 3, 'notCaptured2': 'no', \"notCaptured3\": \"no\" }; },
			'singleQuotedArrowFunction2': test => { return { notCaptured1: 3, 'notCaptured2': 'no', \"notCaptured3\": \"no\" }; },
			\"doubleQuotedArrowFunction2\": test => { return { notCaptured1: 3, 'notCaptured2': 'no', \"notCaptured3\": \"no\" }; },

			unquotedObject: { notCaptured1: 3, 'notCaptured2': 'no', \"notCaptured3\": \"no\" },
			'singleQuotedObject': { notCaptured1: 3, 'notCaptured2': 'no', \"notCaptured3\": \"no\" },
			\"doubleQuotedObject\": { notCaptured1: 3, 'notCaptured2': 'no', \"notCaptured3\": \"no\" },

			unquotedArray: [ { notCaptured1: 3, 'notCaptured2': 'no', \"notCaptured3\": \"no\" }, uncaptured, 3, 'test', ],
			'singleQuotedArray': [ { notCaptured1: 3, 'notCaptured2': 'no', \"notCaptured3\": \"no\" }, uncaptured, 3, 'test', ],
			\"doubleQuotedArray\": [ { notCaptured1: 3, 'notCaptured2': 'no', \"notCaptured3\": \"no\" }, uncaptured, 3, 'test', ],

			shorthandProperty1,
			'notCapturedShorthandProperty2',
			\"notCapturedShorthandProperty3\",

			unquotedShorthandMethod() { return { notCaptured1: 3, 'notCaptured2': 'no', \"notCaptured3\": \"no\" }; },
			'notCapturedSingleQuotedShorthandMethod'() { return { notCaptured1: 3, 'notCaptured2': 'no', \"notCaptured3\": \"no\" }; },
			\"notCapturedDoubleQuotedShorthandMethod\"() { return { notCaptured1: 3, 'notCaptured2': 'no', \"notCaptured3\": \"no\" }; },

			\"\": 'empty string key with double quotes',
			'': 'empty string key with double quotes',

			unquotedString2: 'duplicated key that should not be captured',
			'singleQuotedString2': 'duplicated key that should not be captured',
			\"doubleQuotedString2\": 'duplicated key that should not be captured',

			shorthandAsLastKey
		}";

		$extracted_object_keys = TablePress::extract_keys_from_js_object_string( $js_object_string );
		$expected_keys = array(
			'unquotedBool',
			'singleQuotedBool',
			'doubleQuotedBool',
			'unquotedInt',
			'singleQuotedInt',
			'doubleQuotedInt',
			'unquotedString1',
			'singleQuotedString1',
			'doubleQuotedString1',
			'unquotedString2',
			'singleQuotedString2',
			'doubleQuotedString2',
			'unquotedFunction',
			'singleQuotedFunction',
			'doubleQuotedFunction',
			'unquotedArrowFunction1',
			'singleQuotedArrowFunction1',
			'doubleQuotedArrowFunction1',
			'unquotedArrowFunction2',
			'singleQuotedArrowFunction2',
			'doubleQuotedArrowFunction2',
			'unquotedObject',
			'singleQuotedObject',
			'doubleQuotedObject',
			'unquotedArray',
			'singleQuotedArray',
			'doubleQuotedArray',
			'shorthandProperty1',
			'unquotedShorthandMethod',
			'shorthandAsLastKey',
		);

		$this->assertSame( $expected_keys, $extracted_object_keys );
	}

} // class TablePress_Test_TablePress_Utils
