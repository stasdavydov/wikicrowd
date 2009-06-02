<?
	ob_start('ob_gzhandler');

	require_once 'core.php';

	$chapter = new chapter(false);

	$xslFile = CORE.'xml/chapter.xsl';

	$person = getSessionPerson();

	$params = array('MODE'=>($person == NULL || array_key_exists('view', $_GET) ? 'view' : 'edit'));
	if($person) {
		$params['UID'] = $person->getAttribute('uid');
		$params['NAME'] = $person->getAttribute('name');
	}

	echo $chapter->transform($xslFile, $params);

	ob_end_flush();
?>
