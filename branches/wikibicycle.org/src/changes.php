<?
	ob_start('ob_gzhandler');

	require_once 'core.php';

	$person = getSessionPerson();
	if (isGuest($person) && ! personCanView($person)) {
		include HOME.'auth.php';
		exit;
	}

	install_sape();

	if(!file_exists(CORE.'changes.xml')) {
		$dom = new DOMDocument('1.0', 'utf-8');
		$dom->appendChild($dom->createElement('changes'));
		$dom->save(CORE.'changes.xml');
	}

	$page = 1;
	if (array_key_exists('page', $_GET))
		$page = trim($_GET['page']);
	if ($page < 0 || preg_match('/[^\d]+/', $page))
		$page = 1;

	echo transformXML(CORE.'changes.xml', CORE.'xml/allchanges.xsl', array(
		'UID'=>$person->getAttribute('uid'),
		'NAME'=>$person->getAttribute('name'),
		'ADMIN'=>isAdmin($person),
		'PAGE'=>$page,
		'PAGESIZE'=>10), PROJECT_MTIME);

	flush_sape();

	ob_end_flush();
?>
