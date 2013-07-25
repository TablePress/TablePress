<?php

class TablePress_Test_TablePress_Utils extends TablePress_TestCase {

	function setUp() {
		TablePress::run();
	}

	function test_nonce() {
		$this->assertEquals( 'tablepress_foo_bar', TablePress::nonce( 'foo', 'bar' ) );
		$this->assertEquals( 'tablepress_foo', TablePress::nonce( 'foo' ) );
		$this->assertEquals( 'tablepress_foo', TablePress::nonce( 'foo', false ) );
	}

	function test_letter_to_number() {
		$this->assertEquals( 0, TablePress::letter_to_number( '' ) );
		$this->assertEquals( 1, TablePress::letter_to_number( 'a' ) );
		$this->assertEquals( 1, TablePress::letter_to_number( 'A' ) );
		$this->assertEquals( 26, TablePress::letter_to_number( 'Z' ) );
		$this->assertEquals( 27, TablePress::letter_to_number( 'AA' ) );
		$this->assertEquals( 27, TablePress::letter_to_number( 'Aa' ) );
		$this->assertEquals( 27, TablePress::letter_to_number( 'aA' ) );
		$this->assertEquals( 27, TablePress::letter_to_number( 'aa' ) );
		$this->assertEquals( 52, TablePress::letter_to_number( 'AZ' ) );
		$this->assertEquals( 703, TablePress::letter_to_number( 'AAA' ) );
		$this->assertEquals( 18278, TablePress::letter_to_number( 'ZZZ' ) );
		$this->assertEquals( 18278, TablePress::letter_to_number( 'zzz' ) );

	}

	function test_number_to_letter() {
		$this->assertEquals( '', TablePress::number_to_letter( -1 ) );
		$this->assertEquals( '', TablePress::number_to_letter( 0 ) );
		$this->assertEquals( 'A', TablePress::number_to_letter( 1 ) );
		$this->assertEquals( 'Z', TablePress::number_to_letter( 26 ) );
		$this->assertEquals( 'AA', TablePress::number_to_letter( 27 ) );
		$this->assertEquals( 'AZ', TablePress::number_to_letter( 52 ) );
		$this->assertEquals( 'AAA', TablePress::number_to_letter( 703 ) );
		$this->assertEquals( 'ZZZ', TablePress::number_to_letter( 18278 ) );
	}

	function test_url_not_toplevel() {
		TablePress::$controller->is_top_level_page = false;
		TablePress::$controller->parent_page = 'index.php';

		$this->assertEquals( 'http://example.org/wp-admin/index.php?page=tablepress&action=list', TablePress::url() );
		$this->assertEquals( 'http://example.org/wp-admin/index.php?page=tablepress&action=list', TablePress::url( array(), false ) );
	}

	function test_url_toplevel() {
		TablePress::$controller->is_top_level_page = true;
		TablePress::$controller->parent_page = 'middle';

		$this->assertEquals( 'http://example.org/wp-admin/admin.php?page=tablepress', TablePress::url() );
		$this->assertEquals( 'http://example.org/wp-admin/admin.php?page=tablepress', TablePress::url( array(), false ) );
	}

}
