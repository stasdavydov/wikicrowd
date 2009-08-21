<?php
	$version = '%version%';
	$email_regexp = '%emailregexp%';

	$pathinfo = pathinfo($_SERVER['SCRIPT_FILENAME']);
	$www = substr($_SERVER['REQUEST_URI'], 0, 
		strlen($_SERVER['REQUEST_URI']) - strlen($pathinfo['basename']));

	$update = file_exists('core/config.xml');

	if (! defined('__DIR__'))
		define('__DIR__', dirname(__FILE__));

	function unpackMyself($to = __DIR__) {
		$me = fopen(__FILE__, 'r') or die('Cannot open myself');
    
		while(! feof($me) && ($line = fgets($me)) !== FALSE)
			if (preg_match('/^\/\* package/', $line))
				break;
		$chunks = '';
		while(! feof($me) && ($line = fgets($me)) !== FALSE)
			if (preg_match('/^\*\/ \?>/', $line))
				break;
			else
				$chunks .= trim($line);
    
		fclose($me);
    
		$zipFileName = 'wikicrowd.tmp.zip';
		$zip = fopen($zipFileName, 'w') or die('Cannot create temporary zip file');
		fwrite($zip, base64_decode($chunks));
		fclose($zip);
    
		$zip = new ZipArchive;
		$zip->open($zipFileName);
		$zip->extractTo($to);
		$zip->close();

		unlink($zipFileName);
	}

	function updateHTAccess() {
		global $www;
		file_put_contents('.htaccess', str_replace('%www%', $www, file_get_contents('.htaccess')));
	} 

	function unlinkRecursive($path) {
		if (! file_exists($path))
			return;

		if (is_dir($path)) {
			$dir = opendir($path);
			while($f = readdir($dir))
				if ($f != "." && $f != "..")
					unlinkRecursive($path.'/'.$f);
			closedir($dir);

			rmdir($path);
		} else
			unlink($path);
	}

	function cleanUp() {
		unlink(__FILE__);
		unlinkRecursive(__DIR__.'/migrate');
		unlinkRecursive(__DIR__.'/tmp');
	}

	function checkNewerVersion() {
		global $version;
		$svnVersion = @file_get_contents('http://wikicrowd.googlecode.com/svn/trunk/build/version.txt');
		if(preg_match('/\d\.\d\.\d/', $svnVersion) && version_compare($svnVersion, $version) > 0) {
?><form method="post" action="">
<input type="hidden" name="update" value="<?=$svnVersion?>"/>
<p class="update">New version is available: <a href="http://wikicrowd.googlecode.com/files/wikicrowd-<?=$svnVersion?>.zip">WikiCrowd <?=$svnVersion?></a>.
<input type="submit" style="float:none;display:inline;margin:0;" value="Download"/></p>
</form>
<?php
		}
	}

	// %embed(../src/core/person.php)%


	if (strcasecmp($_SERVER['REQUEST_METHOD'], 'post') == 0 
		&& array_key_exists('update', $_POST)) {
		// update
		$url = "http://wikicrowd.googlecode.com/files/wikicrowd-{$_POST['update']}.zip";
		file_put_contents(__DIR__.'/tmp.zip', file_get_contents($url));
		$zip = new ZipArchive();
		$zip->open(__DIR__.'/tmp.zip');
		$zip->extractTo(__DIR__, '/install.php');
		$zip->close();
		unlink(__DIR__.'/tmp.zip');

		header('Location: install.php?ts='.time());
		exit;
	}

	if ($update) {
		if (! file_exists(__DIR__.'/tmp'))
			mkdir(__DIR__.'/tmp');
		unpackMyself(__DIR__.'/tmp');
		require_once('tmp/migrate/migrate.php');

		$migrator = migrator::getInstance();
		$errors = array();

		if (strcasecmp($_SERVER['REQUEST_METHOD'], 'post') == 0) {
			$migrator->checkCustomUI($errors);
			if (count($errors) == 0) {
				unpackMyself();

?><html><head><title>WikiCrowd installation update</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style type="text/css">
body { font-family: "Trebuchet MS", "Arial", serif; font-size: 100%; }
</style>
</head>
<body>
<h1>WikiCrowd installation update</h1>
<?
				$migrator->doMigrate();
				updateHTAccess();

?><p>WikiCrowd installation is updated. Return to the wiki <a href="<?=$www?>">home page</a>.</p>
</body>
</html>
<?
				cleanUp();

				exit;
			}
		}
		
?><html><head><title>WikiCrowd installation update</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
<style type="text/css">
body, input, select { font-family: "Trebuchet MS", "Arial", serif; font-size: 100%; }
fieldset { width: 32em; border: 1px dotted #999; padding: 0.5em; margin: 0 0 1em 0; }
* html fieldset { width: 34em; }
.update { border: 1px solid #0C0; background: #DFD; padding: 1em; width: 31em; }
.error { border: 1px solid #C00; background: #FDD; padding: 1em; width: 31em; }
.error li { margin-left: 1em; color: #C00;}
.error li span { color: #000; }
label { display: block; margin: 1em 0.25em 0 0; }
select, input { display: block; }
fieldset input { display: inline; }
</style>
</head>
<body>
<h1>WikiCrowd installation update</h1>
<p>Update WikiCrowd from version <?=$migrator->getFromVersion()?> to <?=$migrator->getToVersion()?>.</p>
<?
		if (count($errors) > 0) {
?><ul class="error">
<?
			foreach($errors as $error) {
?><li><span><?=$error?></span></li>
<?
			}
?></ul>
<?
		}

?><form method="post" action=""><?
		$migrator->customUI();
?><input style="padding:0.25em;font-size:110%;" type="submit" value="Update"/></form>
<?
		checkNewerVersion();
?>
</body>
</html>
<?
		exit;
	}
		
	$errors = array();

	$title = 'WikiCrowd';
	$homePage = 'Home';
	$supportEmail = array_key_exists('SERVER_ADMIN', $_SERVER) ? $_SERVER['SERVER_ADMIN'] : '';
	$locale = 'en';

	$login = '';
	$password = '';
	$email = '';

	$accessPlans = array(
		array('Personal private', true, false, true, false, false),
		array('Personal public', false, false, false, false, true),
		array('Community private', true, true, true, false, false),
		array('Community public', true, true, true, false, true),
		array('Anonymous wiki (like Wikipedia)', true, true, true, true, true));

	$accessPlan = 3;
	$anyoneCanRegister = true;
	$newUserCanEdit = true;
	$newUserCanView = true;
	$guestCanEdit = false;
	$guestCanView = true;

	if (strcasecmp($_SERVER['REQUEST_METHOD'], 'post') == 0) {

		$title = stripslashes(trim($_POST['title']));
		if($title == "")
			$errors['title'] = 'Title is required';

		$homePage = stripslashes(trim($_POST['homePage']));
		if ($homePage == "")
			$errors['homePage'] = 'Home page is required';

		$supportEmail = trim($_POST['supportEmail']);
		if ($supportEmail != "" && !preg_match($email_regexp, $supportEmail))
			$errors['supportEmail'] = 'Support e-mail looks wrong';

		$locale = $_POST['locale'];

		$login = stripslashes(trim($_POST['login']));
		if ($login == "")
			$errors['login'] = 'Your login is required';
		else if (! preg_match('/^[0-9A-Za-z]+$/', $login))
			$errors['login'] = 'Wrong login, please use letters A-Z, a-z and digits 0-9.';

		$password = stripslashes(trim($_POST['password']));
		if ($password == "")
			$errors['password'] = 'Your password is required.';

		$email = trim($_POST['email']);
		if ($email == "")
			$errors['email'] = 'Your e-mail is required.';
		else if (! preg_match($email_regexp, $email)) 
			$errors['email'] = 'Your e-mail looks wrong.';

		$accessPlan = array_key_exists('plan', $_POST) ? $_POST['plan'] : count($accessPlans);
		$anyoneCanRegister = array_key_exists('anyoneCanRegister', $_POST) ? $_POST['anyoneCanRegister'] : '';
		$newUserCanEdit = array_key_exists('newUserCanEdit', $_POST) ? $_POST['newUserCanEdit'] : '';
		$newUserCanView = array_key_exists('newUserCanView', $_POST) ? $_POST['newUserCanView'] : '';
		$guestCanEdit = array_key_exists('guestCanEdit', $_POST) ? $_POST['guestCanEdit'] : '';
		$guestCanView = array_key_exists('guestCanView', $_POST) ? $_POST['guestCanView'] : '';

		if (count($errors) == 0) {
			// 1. unpack
			unpackMyself();

			// 2. create config.xml
			$dom = new DOMDocument('1.0', 'utf-8');
			$dom->appendChild($dom->implementation->createDocumentType(
				'config', 'WikiCrowd', 'xml/wikicrowd.dtd'));
			$config = $dom->createElement('config');
			$config->setAttribute('version', $version);
			$dom->appendChild($config);

			function addProperty($dom, $name, $value) {
				$property = $dom->createElement('property');
				$property->setAttribute('name', $name);
				$property->setAttribute('value', $value);
				$dom->documentElement->appendChild($property);
			}
			addProperty($dom, 'www', $www);
			addProperty($dom, 'title', $title);
			addProperty($dom, 'supportEmail', $supportEmail);
			addProperty($dom, 'homePage', $homePage);
    
			// since 0.0.7
			addProperty($dom, 'LOCALE', $locale);
   
			// since 0.0.8
			if ($accessPlan < count($accessPlans)) {
				$anyoneCanRegister = $accessPlans[$accessPlan][1];
				$newUserCanEdit = $accessPlans[$accessPlan][2];
				$newUserCanView = $accessPlans[$accessPlan][3];
				$guestCanEdit = $accessPlans[$accessPlan][4];
				$guestCanView = $accessPlans[$accessPlan][5];
			}
			addProperty($dom, 'anyoneCanRegister', $anyoneCanRegister); // anyone can register on wiki
			addProperty($dom, 'newUserCanEdit', $newUserCanEdit); // new registered user can edit any page
			addProperty($dom, 'newUserCanView', $newUserCanView); // new registered user can view any page
    
			$dom->save('core/config.xml');

			// create admin account
			$admin = createPerson($login, $password, 'Admin', $email, 'Wiki Admin', true, true, true);
			$admin->save("persons/$login.xml");
			doLogin($admin->documentElement);	// auto login Admin

			// create guest account
			$guest = createPerson('guest', md5(time()), 'Guest', 'noreply', 'Guest account', 
				$guestCanEdit, $guestCanView, false);
			$guest->save("persons/guest.xml");

			// 3. update .htaccess
			updateHTAccess();

			// 4. clean up installation files
			cleanUp();

			// 5. redirect to home page
			header('Location: '.$www);

			exit;
		}
	}

?><html><head><title>WikiCrowd installation</title>
<style type="text/css">
body, input, select { font-family: "Trebuchet MS", "Arial", serif; font-size: 100%; }
fieldset { width: 32em; border: 1px dotted #999; padding: 0.5em; margin: 0 0 1em 0; }
* html fieldset { width: 34em; }
input { display: block; float:right; margin: 0.75em 0 0 0;}
label { display: block; clear: both; float: left; margin: 1em 0.25em 0 0;}
.optional { color: #999; }
.error { padding: 1em; width: 31em; }
.error li { margin-left: 1em; color: #C00;}
.error li span { color: #000; }
.update { border: 1px solid #0C0; background: #DFD; padding: 1em; width: 31em; }
.info { border: 1px solid #CC0; background: #FFD; margin-top: 1em; padding: 1em; width: 36.8em; font-size:85%;}
table { border-right: 1px solid #999; border-bottom: 1px solid #999; }
td { text-align: center; border-left: 1px solid #999; }
td.ne { border-left:none; padding-right:0.25em;text-align:left;}
td label, td input { display:inline; margin: 0; float:none;}
tr.odd { background: #EEE; }
th { border: 1px solid #999; border-right: none; font-size:90%;}
th.ne { border: none; border-bottom: 1px solid #999; }
<?
	if (count($errors) > 0) {
		foreach(array_keys($errors) as $id)
			echo '#'.$id.', ';
		echo '.error { background: #FDD; border: 1px solid #C00; }';
	}
?>
</style>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head>
<body>
<h1>WikiCrowd installation</h1>
<p class="info">WikiCrowd is a light useful wiki engine.
Please visit <a href="http://code.google.com/p/wikicrowd/">WikiCrowd home page</a> to get more information about it.</p>
<?php
	// check newer version
	checkNewerVersion();
	
	$reqFails = array();

	// check system requirements

	@file_put_contents('test.tmp', '');
	if (! @file_exists('test.tmp')) {
		$reqFails[] = 'Cannot create files in '.dirname(__FILE__);
	} 
	@unlink('test.tmp');

	if (version_compare(phpversion(), '5.2', '<'))
		$reqFails[] = 'PHP version 5.2 or later is required';

	if (!class_exists('DOMDocument'))
		$reqFails[] = 'DOM support is required';

	if (!class_exists('XSLTProcessor'))
		$reqFails[] = 'XSL suuport is required';

	if(! function_exists('iconv'))
		$reqFails[] = 'iconv support is required';

	if (count($reqFails) > 0) {
?><p>The following issue<?= count($reqFails) > 1 ? 's' : '' ?> should be fixed before installation:</p>
<ul class="error">
<?
		foreach($reqFails as $error) {
?><li><span><?=$error?></span></li><?
		}
?></ul>
<p><a href="<?=$_SERVER['REQUEST_URI']?>">Try again</a> when fixed.</p>
<?	} else {

		if (count($errors) > 0) {
?><ul class="error"><?
			foreach($errors as $error) {
?><li><span><?=$error?></span></li><?
			}
?></ul><?
		}

		$locales = array(%locales%);

?><form method="post" action="">
<p>Please, correct the following information if required and press "Install".</p>
<fieldset>
<legend>Installing WikiCrowd into http://<?=$_SERVER['SERVER_NAME']?><?=$_SERVER['SERVER_PORT'] == 80 ? '' : ':'.$SERVER['SERVER_PORT']?><?=$www?></legend>
<label for="title">Title of wiki site:</label> <input type="text" name="title" id="title" size="50" value="<?=$title?>"/>
<label for="homePage">Home page name:</label> <input type="text" name="homePage" id="homePage" size="50" value="<?=$homePage?>"/>
<label class="optional" for="supportEmail"><nobr>Support's e-mail:</nobr><br/><small>(optional)</small></label> <input type="text" name="supportEmail" id="supportEmail" size="50" value="<?=$supportEmail?>"/>
<label style="border-top:1px dotted #CCC; padding-top: 0.5em;">Use <select name="locale"><?php
		array_walk($locales, create_function(
			'$name, $code', 
			'if ($code == "") continue;
			 echo "<option ".($code == $locale ? "selected=\"selected\" " : "").
				"value=\"$code\">$name</option>";'));
?></select> language for user interface.</label>
</fieldset>

<fieldset>
<legend>Admin account</legend>
<p>Use these login and password to login as admin.</p>
<label for="login">Your login:</label> <input type="text" name="login" id="login" size="50" value="<?=$login?>"/>
<label for="password">Your password:</label> <input type="text" name="password" id="password" size="50" value="<?=$password?>"/>
<label for="email">Your e-mail:</label> <input type="text" name="email" id="email" size="50" value="<?=$email?>"/>
</fieldset>

<fieldset>
<legend>Access rights</legend>
<table border="0" cellspacing="0">
<col style="width: 2em;"/>
<thead>
<tr><th colspan="2" class="ne">&nbsp;</th>
<th>Anyone can register</th>
<th>New user can edit</th>
<th>New user can read</th>
<th>Guest can edit</th>
<th>Guest can read</th></tr>
</thead>
<tbody>
<?
		foreach($accessPlans as $plan=>$details) {
?><tr<?	if ($plan % 2) echo ' class="odd"'; ?>><td><input type="radio" name="plan" <?
			if ($plan == $accessPlan)
				echo 'checked="checked" ';
?>id="plan<?=$plan?>" value="<?=$plan?>"/></td>
<td class="ne"><label for="plan<?=$plan?>"><?=$details[0]?></label></td>
<?			
			for($idx = 1; $idx < count($details); $idx++) {
?><td><input type="checkbox" <? if ($details[$idx]) echo 'checked="checked" '; ?>disabled="disabled"/></td>
<?
			}
?></tr>
<?
		}
?><tr<?	if (count($accessPlans) % 2) echo ' class="odd"'; ?>><td><input type="radio" name="plan" id="plan<?=count($accessPlans)?>" <?
			if (count($accessPlans) == $accessPlan)
				echo 'checked="checked" ';
?>value="<?=count($accessPlans)?>"/></td>
<td class="ne"><label for="plan<?=count($accessPlans)?>">Your choice</label></td>
<td><input type="checkbox" name="anyoneCanRegister" value="1"<?
		if ($anyoneCanRegister) echo ' checked="checked"'; ?>/></td>
<td><input type="checkbox" name="newUserCanEdit" value="1"<?
		if ($newUserCanEdit) echo ' checked="checked"'; ?>/></td>
<td><input type="checkbox" name="newUserCanView" value="1"<?
		if ($newUserCanView) echo ' checked="checked"'; ?>/></td>
<td><input type="checkbox" name="guestCanEdit" value="1"<?
		if ($guestCanEdit) echo ' checked="checked"'; ?>/></td>
<td><input type="checkbox" name="guestCanView" value="1"<?
		if ($guestCanView) echo ' checked="checked"'; ?>/></td>
</tr>
</tbody>
</table>
</fieldset>

<input style="float:left; padding:0.25em;font-size:110%;" type="submit" value="Install"/>
</form>
<?php

	}
?>
</body>
</html>

<?php
/* package
*/ ?>
