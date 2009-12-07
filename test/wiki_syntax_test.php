<?php

// todo: create unit-test for wiki-syntax

require_once('simpletest/autorun.php');
if (file_exists('../core/plugins/wiki/node.php')) {
	require_once('../core.php');
} else {
	require_once('../src/core.php');
}

define ('www', '/www/');

class TestWikiSyntax extends UnitTestCase {
	function testBold1() {
		$str = '*bold*';
		$expected = '<strong>bold</strong>';

		$this->assertEqual($expected, format_wiki($str));
	}

	function testBoldItalic1() {
		$str = '*/bold/*';
		$expected = '<strong><em>bold</em></strong>';

		$this->assertEqual($expected, format_wiki($str));
	}

	function testBoldItalic2() {
		$str = '*/bold*/';
		$expected = '<strong>/bold</strong>/';

		$this->assertEqual($expected, format_wiki($str));
	}

	function testItalic1() {
		$str = '/italic/';
		$expected = '<em>italic</em>';

		$this->assertEqual($expected, format_wiki($str));
	}
	
	function testDiv1() {
		$str = '<div>/italic/</div>';
		$expected = '<div><em>italic</em></div>';

		$this->assertEqual($expected, format_wiki($str));
	}
	
	function testDiv2() {
		$str = '<div>/italic</div>/';
		$expected = '<div>/italic</div>/';

		$this->assertEqual($expected, format_wiki($str));
	}

	function testSubscript1() {
		$str = '_subscript_';
		$expected = '<sub>subscript</sub>';

		$this->assertEqual($expected, format_wiki($str));
	}

	function testSuperScript1() {
		$str = '^superscript^';
		$expected = '<sup>superscript</sup>';

		$this->assertEqual($expected, format_wiki($str));
	}
	
	function testSuperScriptBold1() {
		$str = '*^superscript*^';
		$expected = '<strong>^superscript</strong>^';

		$this->assertEqual($expected, format_wiki($str));
	}

	function testLink1() {
		$str = '@page[http://microsoft.com/]';
		$expected = '<a onclick="javascript:editOff()" href="http://microsoft.com/">http://microsoft.com/</a>';

		$this->assertEqual($expected, format_wiki($str));
	}

	function testLink2() {
		$str = '@page[Home]';
		$expected = '<a onclick="javascript:editOff()" href="'.www.'Home">Home</a>';

		$this->assertEqual($expected, format_wiki($str));
	}

	function testLink3() {
		$str = '@page[Home] "123"';
		$expected = '<a onclick="javascript:editOff()" href="'.www.'Home">123</a>';

		$this->assertEqual($expected, format_wiki($str));
	}

	function testLink4() {
		$str = '@page "123"';
		$expected = '<a onclick="javascript:editOff()" href="'.www.'123">123</a>';

		$this->assertEqual($expected, format_wiki($str));
	}
}

// todo: add more cases

?>