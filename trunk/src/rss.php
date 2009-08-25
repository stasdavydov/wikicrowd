<?
	ob_start('ob_gzhandler');

	require_once 'core.php';

	$person = getSessionPerson();
	if (isGuest($person) && ! personCanView($person)) {
		header('HTTP/1.0 403 Forbidden');
		exit;
	}

	header('Content-type: application/xml+rss');

	echo "<?xml version='1.0' encoding='UTF-8'?>\n";
	echo transformXML(CORE.'changes.xml', CORE.'xml/rss.xsl', 
		array('wwwHost'=>'http://'.$_SERVER['SERVER_NAME'].www), PROJECT_MTIME);

	ob_end_flush();
?>
