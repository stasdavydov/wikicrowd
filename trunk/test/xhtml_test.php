<?php

require_once('simpletest/autorun.php');
if (file_exists('../core/plugins/wiki/node.php')) {
	require_once('../core.php');
} else {
	require_once('../src/core.php');
}

class TestXHtml extends UnitTestCase {
	function testXHtmlCorrect1 () {
		$this->assertTrue(isValidXHtml('<b>la la la </b>'));
	}

	function testXHtmlCorrect1a () {
		$this->assertTrue(isValidXHtml('<b class="blue">la la la </b>'));
	}

	function testXHtmlCorrect1b () {
		$this->assertTrue(isValidXHtml('<b><i>la</i></b>'));
	}

	function testXHtmlCorrect1c () {
		$this->assertTrue(isValidXHtml('<b><i>la</i> bla bla</b>'));
	}

	function testXHtmlCorrect2 () {
		$this->assertTrue(isValidXHtml(''));
	}

	function testXHtmlCorrect3 () {
		$this->assertTrue(isValidXHtml('aaaa'));
	}

	function testXHtmlWrong1 () {
		$this->assertFalse(isValidXHtml('<a>bbb'));
	}

	function testXHtmlWrong2 () {
		$this->assertFalse(isValidXHtml('<a>bbb</'));
	}

	// */aaa*/ -> <b><i>aaa</b></i>
	function testXHtmlWrong4 () {
		$this->assertFalse(isValidXHtml('<b><i>aaa</b></i>'));
	}
}

?>