<?php

// todo: create unit-test for wiki-syntax

$str = '*bold*';
$expected = '<strong>bold</strong>';

$str = '/italic/';
$expected = '<em>italic</em>';


$str = '_subscript_';
$expected = '<sub>subscript</sub>';


$str = '^superscript^';
$expected = '<sup>superscript</sup>';

// todo: add more cases

?>