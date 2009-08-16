<?php

// todo: create unit-test for wiki-syntax

require_once('simpletest/autorun.php');

class TestWikiSyntax extends UnitTestCase {
	function testBold1() {
		$str = '*bold*';
		$expected = '<strong>bold</strong>';
	}

	function testBoldItalic1() {
		$str = '*/bold/*';
		$expected = '<strong><em>bold</em></strong>';
	}

	function testBoldItalic2() {
		$str = '*/bold*/';
		$expected = '<strong>/bold</strong>/';
	}

	function testItalic1() {
		$str = '/italic/';
		$expected = '<em>italic</em>';
	}

	function testSubscript1() {
		$str = '_subscript_';
		$expected = '<sub>subscript</sub>';
	}

	function testSuperScript1() {
		$str = '^superscript^';
		$expected = '<sup>superscript</sup>';
	}
}

// todo: add more cases

?>