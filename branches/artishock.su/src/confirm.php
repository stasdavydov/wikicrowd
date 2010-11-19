<?
    ob_start('ob_gzhandler');

	require_once 'core.php';

	if (! array_key_exists('login', $_GET) ||
		! array_key_exists('hash', $_GET)) {
  		header('HTTP/1.0 404 Not Found');
   		exit;
	}

	$login = trim($_GET['login']);
	$hash = trim($_GET['hash']);

	$msg = '';

	if ($person = loadPerson($login, true)) {
		rename(PERSONS."sandbox/$login.xml", PERSONS.$login.'.xml');
		$msg = "<a href='".www."person/$login'>".getMessage('YourAccout')."</a> ".getMessage('SuccessfullyActivated'). 
			' '.getMessage('WelcomeToWiki')." <a href='".www."'>".title."</a>!";
		doLogin($person);
	} else if ($person = loadPerson($login)) {
		if ($person->hasAttribute('newemail')) {
			if ($person->getAttribute('newemailcheck') == $hash) {
				$person->setAttribute('email', $person->getAttribute('newemail'));
				$person->removeAttribute('newemail');
				$person->removeAttribute('newemailcheck');
				$person->ownerDocument->save(PERSONS.$login.'.xml');
				$msg = getMessage('EmailChangeIsConfirmed');
			} else {
				$msg = getMessage('EmailChangeWrongCode');
			}
		} else {
			$msg = "<a href='".www."person/$login'>".getMessage('YourAccout')."</a> ".getMessage('AlreadyActivated');
		}
	} else {
  		header('HTTP/1.0 404 Not Found');
   		exit;
	}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=LOCALE?>">
<head><title><?=getMessage('AccoutActivation')?> &#0187; <?=title?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<link rel="shortcut icon" href="<?=www?>core/img/favicon.gif" />
<link rel="stylesheet" type="text/css" href="<?=www?>core/css/main.css"/>
<style type="text/css">
body { margin: 0 0 0 1em; }
</style>
</head>
<body>
<div class="menu"><div class="rightside"><a href="<?=www?>allchanges/"><?=getMessage('AllChanges')?></a><a href="<?=www?>"><?=getMessage('ToHome')?></a></div></div>
<h1><?=getMessage('AccoutActivation')?> &raquo; <a href="<?=www?>"><?=title?></a></h1>
<?
	echo $msg;
?>
</body>
</html>
<?
	ob_end_flush();
?>