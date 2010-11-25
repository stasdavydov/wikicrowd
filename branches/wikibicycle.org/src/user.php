<?
	require_once 'core.php';

	if (! array_key_exists('uid', $_GET)) {
  		error404();
	}
	ob_start('ob_gzhandler');

	$person = getSessionPerson();
	if (isGuest($person) && ! personCanView($person)) {
		include HOME.'auth.php';
		exit;
	}

	$uid = trim($_GET['uid']);
	if ($uid == "") {
		// list all persons	
		getPersonIndex();
		install_sape();
		echo transformXML(CACHE.'persons-index.xml', CORE.'xml/persons.xsl', 
			array('UID' => $person->getAttribute('uid'), 
				'NAME' => $person->getAttribute('name'),
				'ADMIN' => isAdmin($person)), PROJECT_MTIME);
		flush_sape();
		ob_end_flush();
		exit;
	}

	$personFile = PERSONS.$uid.'.xml';
	if (!file_exists($personFile)) {
  		error404();
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

	install_sape();
	echo transformXML($personFile, $xsl, $params, PROJECT_MTIME);
	flush_sape();

	ob_end_flush();
?>
