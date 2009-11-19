<?
	ob_start('ob_gzhandler');

	require_once 'core.php';

	$mode = array_key_exists('view', $_GET) 
		? 'view' 
		: (array_key_exists('rtf', $_GET) ? 'rtf' : 'edit');

	$person = getSessionPerson();
	if ($mode == "edit" 
		&& personCanView($person) 
		&& !personCanEdit($person)) {

		header('Location: ?view');
		exit;
	}

	if ($mode == "rtf" 
		&& personCanView($person) 
		&& !isAdmin($person)) {

		header('Location: ?view');
		exit;
	}


	$chapter = new chapter(false);

	if ((!personCan($person, $mode) && isGuest($person)) 
		|| ! $chapter->exists()) {
		include 'auth.php';
		exit;
	}

	$xslFile = CORE.'xml/chapter.xsl';

	if ($mode == "rtf") {
		$xslFile = CORE.'xml/rtf.xsl';

		function rtfencode($content) {
			$translate = array();
			for($c = 128; $c < 256; $c++)
				$translate[chr($c)] = "\\'".strtolower(dechex($c));
		 	
		 	return strtr($content, $translate);
		}
		ob_start('rtfencode');

		header('Content-Type: application/rtf');
	}

	$params = array(
		'MODE'=>personCan($person, $mode) ? $mode : 'restricted',
		'UID'=>$person->getAttribute('uid'),
		'NAME'=>$person->getAttribute('name'),
		'ADMIN'=>isAdmin($person),
		'CANEDIT'=>personCanEdit($person),
		'CANVIEW'=>personCanView($person));

	echo $chapter->transform($xslFile, $params, $mode == "rtf");

	ob_end_flush();

	if ($mode == "rtf") {
		ob_end_flush();
	}
?>
