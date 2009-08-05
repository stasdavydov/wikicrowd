<?
	ob_start('ob_gzhandler');

	require_once 'core.php';

	$mode = array_key_exists('view', $_GET) ? 'view' : 'edit';

	$person = getSessionPerson();
	if (isGuest($person) 
		&& $mode == "edit" 
		&& personCanView($person) 
		&& !personCanEdit($person)) {

		header('Location: ?view');
		exit;
	}

	if (!personCan($person, $mode) && isGuest($person)) {
		include 'auth.php';
		exit;
	}

	$chapter = new chapter(false);

	$xslFile = CORE.'xml/chapter.xsl';

	$params = array(
		'MODE'=>personCan($person, $mode) ? $mode : 'restricted',
		'UID'=>$person->getAttribute('uid'),
		'NAME'=>$person->getAttribute('name'),
		'ADMIN'=>isAdmin($person),
		'CANEDIT'=>personCanEdit($person),
		'CANVIEW'=>personCanView($person));

	echo $chapter->transform($xslFile, $params);

	ob_end_flush();
?>
