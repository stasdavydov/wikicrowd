<?
	ob_start('ob_gzhandler');

	require_once 'core.php';

	$mode = array_key_exists('edit', $_GET) ? 'edit' : 'view';

	$person = getSessionPerson();
	if ($mode == "edit" 
		&& personCanView($person) 
		&& !personCanEdit($person)) {

		header('Location: ?');
		exit;
	}

	$chapter = new chapter(false);

	if ((!personCan($person, $mode) && isGuest($person)) 
		|| ! $chapter->exists()) {
		include 'auth.php';
		exit;
	}

	install_sape();

	$xslFile = CORE.'xml/chapter.xsl';

	$params = array(
		'MODE'=>personCan($person, $mode) ? $mode : 'restricted',
		'UID'=>$person->getAttribute('uid'),
		'NAME'=>$person->getAttribute('name'),
		'ADMIN'=>isAdmin($person),
		'CANEDIT'=>personCanEdit($person),
		'CANVIEW'=>personCanView($person));

	echo $chapter->transform($xslFile, $params);

	flush_sape();

	ob_end_flush();
?>
