<?php

/**
 * Tests basic TablePress utility functions.
 */
class TablePress_Test_TablePress_Utils extends TablePress_TestCase {

	public function setUp() {
		TablePress::run();
	}

	public function test_controller_was_set_up() {
		$this->assertTrue( is_object( TablePress::$controller ) );
	}

	public function test_nonce() {
		$this->assertEquals( 'tablepress_foo_bar', TablePress::nonce( 'foo', 'bar' ) );
		$this->assertEquals( 'tablepress_foo', TablePress::nonce( 'foo' ) );
		$this->assertEquals( 'tablepress_foo', TablePress::nonce( 'foo', false ) );
	}

	/**
	 * @dataProvider data_letter_to_number
	 *
	 * @param string $letter Letter to convert.
	 * @param int    $number Conversion result number.
	 */
	public function test_letter_to_number( $letter, $number ) {
		$this->assertEquals( $number, TablePress::letter_to_number( $letter ) );
	}
	public function data_letter_to_number() {
		return array(
			array(    '',     0 ),
			array(   'a',     1 ),
			array(   'A',     1 ),
			array(   'Z',    26 ),
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
	}

	/**
	 * @dataProvider data_number_to_letter
	 *
	 * @param int    $number Number to convert.
	 * @param string $letter Conversion result letter.
	 */
	public function test_number_to_letter( $number, $letter ) {
		$this->assertEquals( $letter, TablePress::number_to_letter( $number ) );
	}
	public function data_number_to_letter() {
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
	}

	public function test_url_not_toplevel() {
		TablePress::$controller->is_top_level_page = false;
		TablePress::$controller->parent_page = 'index.php';

		$this->assertEquals( 'http://example.org/wp-admin/index.php?page=tablepress&action=list', TablePress::url() );
		$this->assertEquals( 'http://example.org/wp-admin/index.php?page=tablepress&action=list', TablePress::url( array(), false ) );
	}

	public function test_url_toplevel() {
		TablePress::$controller->is_top_level_page = true;
		TablePress::$controller->parent_page = 'middle';

		$this->assertEquals( 'http://example.org/wp-admin/admin.php?page=tablepress', TablePress::url() );
		$this->assertEquals( 'http://example.org/wp-admin/admin.php?page=tablepress', TablePress::url( array(), false ) );
	}

}
