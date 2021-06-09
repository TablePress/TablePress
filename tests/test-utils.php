<?php

/**
 * Tests for basic TablePress utility functions.
 * @package TablePress
 * @subpackage Unit Tests
 * @since 1.1.0
 */
class TablePress_Test_TablePress_Utils extends TablePress_TestCase {

	/**
	 * Initialize TablePress before every test.
	 *
	 * @since 1.1.0
	 */
	public function setUp() {
		parent::setUp();
		TablePress::run();
	}

	/**
	 * Test that the TablePress Controller object was initialized.
	 *
	 * @since 1.4.0
	 */
	public function test_controller_was_set_up() {
		$this->assertTrue( is_object( TablePress::$controller ) );
	}

	/**
	 * Test that names of nonces are generated properly.
	 *
	 * @since 1.1.0
	 */
	public function test_nonce() {
		$this->assertSame( 'tablepress_foo_bar', TablePress::nonce( 'foo', 'bar' ) );
		$this->assertSame( 'tablepress_foo', TablePress::nonce( 'foo' ) );
		$this->assertSame( 'tablepress_foo', TablePress::nonce( 'foo', false ) );
	}

	/**
	 * Test that column names (letters) are converted to their positions (numbers) properly.
	 *
	 * @dataProvider data_letter_to_number
	 *
	 * @since 1.1.0
	 *
	 * @param string $letter Letter to convert.
	 * @param int    $number Conversion result number.
	 */
	public function test_letter_to_number( $letter, $number ) {
		$this->assertSame( $number, TablePress::letter_to_number( $letter ) );
	}

	/**
	 * Provide test data for the letter to number test.
	 *
	 * @since 1.4.0
	 *
	 * @return array Test data.
	 */
	public function data_letter_to_number() {
		// phpcs:disable WordPress.Arrays.CommaAfterArrayItem.SpaceAfterComma, WordPress.Arrays.ArrayDeclarationSpacing.SpaceAfterArrayOpener
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
		// phpcs:enable
	}

	/**
	 * Test that columns positions (numbers) are converted to their names (letters) properly.
	 *
	 * @dataProvider data_number_to_letter
	 *
	 * @since 1.1.0
	 *
	 * @param int    $number Number to convert.
	 * @param string $letter Conversion result letter.
	 */
	public function test_number_to_letter( $number, $letter ) {
		$this->assertSame( $letter, TablePress::number_to_letter( $number ) );
	}

	/**
	 * Provide test data for the number to letter test.
	 *
	 * @since 1.4.0
	 *
	 * @return array Test data.
	 */
	public function data_number_to_letter() {
		// phpcs:disable WordPress.Arrays.CommaAfterArrayItem.SpaceAfterComma, WordPress.Arrays.ArrayDeclarationSpacing.SpaceAfterArrayOpener
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
	 * Test that the screen URLs for non-toplevel admin menu entries are correct.
	 *
	 * @since 1.1.0
	 */
	public function test_url_not_toplevel() {
		TablePress::$controller->is_top_level_page = false;
		TablePress::$controller->parent_page = 'index.php';

		$this->assertSame( 'http://example.org/wp-admin/index.php?page=tablepress&action=list', TablePress::url() );
		$this->assertSame( 'http://example.org/wp-admin/index.php?page=tablepress&action=list', TablePress::url( array(), false ) );
	}

	/**
	 * Test that the screen URLs for toplevel admin menu entries are correct.
	 *
	 * @since 1.1.0
	 */
	public function test_url_toplevel() {
		TablePress::$controller->is_top_level_page = true;
		TablePress::$controller->parent_page = 'middle';

		$this->assertSame( 'http://example.org/wp-admin/admin.php?page=tablepress', TablePress::url() );
		$this->assertSame( 'http://example.org/wp-admin/admin.php?page=tablepress', TablePress::url( array(), false ) );
	}

} // class TablePress_Test_TablePress_Utils
