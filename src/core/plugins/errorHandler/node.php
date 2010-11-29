<?php

// set up error handler
function myErrorHandler($errno, $errstr, $errfile, $errline, $errcontext) {
	$f = fopen(HOME.'/errors.txt', 'a');
	fwrite($f, 
		"Date: ".date('Y-m-d H:i:s')."\n".
		"Code: $errno\n".
		"ErrStr: $errstr\n".
		"$errfile:$errline\n".
		print_r($_SERVER, true)."\n".
		print_r($errcontext, true)."\n\n\n");
	return false;
}
set_error_handler("myErrorHandler");

?>