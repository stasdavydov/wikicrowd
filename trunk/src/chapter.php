<?
	ob_start('ob_gzhandler');

	require_once 'core.php';

	$mode = array_key_exists('view', $_GET) ? 'view' : 'edit';

	$person = getSessionPerson();
	if ($mode == "edit" 
		&& personCanView($person) 
		&& !personCanEdit($person)) {

		header('Location: ?view');
		exit;
	}

	$chapter = new chapter(false);

	if ((!personCan($person, $mode) && isGuest($person)) 
		|| ! $chapter->exists()) {
		include 'auth.php';
		exit;
	}

	$THEME->transform($chapter, $person, $mode);

	ob_end_flush();
?>