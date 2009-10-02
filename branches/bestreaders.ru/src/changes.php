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
		'NAME'=>$person->getAttribute('name'),
		'ADMIN'=>isAdmin($person));

	if(!file_exists(CORE.'changes.xml')) {
		$dom = new DOMDocument('1.0', 'utf-8');
		$dom->appendChild($dom->createElement('changes'));
		$dom->save(CORE.'changes.xml');
	}

	echo transformXML(CORE.'changes.xml', CORE.'xml/allchanges.xsl', $params, PROJECT_MTIME);

	ob_end_flush();
?>
