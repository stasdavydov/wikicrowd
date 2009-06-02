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
		rename("persons/sandbox/$login.xml", "persons/$login.xml");
		$msg = "<a href='".www."person/$login'>Ваша учетная запись</a> успешно активирована. 
			Добро пожаловать в wiki &laquo;<a href='".www."'>".title."</a>&raquo;!";
		doLogin($person);
	} else if (loadPerson($login)) {
		$msg = "<a href='".www."person/$login'>Ваша учетная запись</a> уже активирована.";
	} else {
  		header('HTTP/1.0 404 Not Found');
   		exit;
	}
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="RU">
<head><title>Активация учетной записи | <?=title?></title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
<link rel="stylesheet" type="text/css" href="<?=www?>core/css/main.css"/>
<style type="text/css">
body { margin: 0 0 0 1em; }
</style>
</head>
<body>
<h1>Активация учетной записи &raquo; <a href="<?=www?>"><?=title?></a></h1>
<?
	echo $msg;
?>
</body>
</html>
<?
	ob_end_flush();
?>