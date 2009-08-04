<?
	ob_start('ob_gzhandler');

	require_once 'core.php';

	$person = getSessionPerson();
	if (isGuest($person) && ! personCanView($person)) {
		include HOME.'auth.php';
		exit;
	}

	$params = array(
		'UID'=>$person->getAttribute('uid'),
		'NAME'=>$person->getAttribute('name'));

	echo transformXML(CORE.'changes.xml', CORE.'xml/allchanges.xsl', $params, PROJECT_MTIME);

	ob_end_flush();
?>
