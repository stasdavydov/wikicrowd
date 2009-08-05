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

	$person = getSessionPerson();
	if (isGuest($person) && ! personCanView($person)) {
		include HOME.'auth.php';
		exit;
	}

	$params = array('MODE'=>'restricted');
	$xsl = CORE.'xml/person.xsl';

	if ($person->getAttribute('uid') == $uid && !isGuest($person))
		$params['MODE'] = 'edit';
	else if (personCanView($person))
		$params['MODE'] = 'view';

	$params['UID'] = $person->getAttribute('uid');
	$params['NAME'] = $person->getAttribute('name');
	$params['ADMIN'] = isAdmin($person);

	echo transformXML($personFile, $xsl, $params, PROJECT_MTIME);

	ob_end_flush();
?>
