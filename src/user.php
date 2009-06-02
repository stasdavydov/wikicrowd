<?
	require_once 'core.php';

	if (! array_key_exists('uid', $_GET)) {
  		header('HTTP/1.0 404 Not Found');
   		exit;
	}
	$uid = trim($_GET['uid']);
	$personFile = PERSONS.$uid.'.xml';
	if (!file_exists($personFile)) {
  		header('HTTP/1.0 404 Not Found');
   		exit;
	}

	ob_start('ob_gzhandler');

	$params = array('MODE'=>'view');
	$xsl = CORE.'xml/person.xsl';
	if($person = getSessionPerson()) {
		if ($person->getAttribute('uid') == $uid)
			$params['MODE'] = 'edit';

		$params['UID'] = $person->getAttribute('uid');
		$params['NAME'] = $person->getAttribute('name');
	} 

	echo transformXML($personFile, $xsl, $params, XSL_MTIME);

	ob_end_flush();
?>
