<?
	require_once '../../core.php';

	$localeFile = CORE.'xml/locale/'.LOCALE.'.xml';
	if (!file_exists($localeFile)) {
  		header('HTTP/1.0 404 Not Found');
   		exit;
	}

	header('Conetnt-type: text/javascript');

	ob_start('ob_gzhandler');

	$xsl = CORE.'xml/locale.js.xsl';

	function jsStringReplace($str) {
		return strtr($str, array('\''=>'\\\'', '&lt;'=>'<', '&gt;'=>'>'));
	}

	echo transformXML($localeFile, $xsl, array(), PROJECT_MTIME);

	ob_end_flush();
?>
