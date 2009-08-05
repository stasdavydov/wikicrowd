<?
	ob_start('ob_gzhandler');
	
	require_once 'core.php';
	
	$person = getSessionPerson();

	if (! isAdmin($person)) {
		header('HTTP/1.0 403 Forbidden');
		exit;
	}

	function checkNewerVersion() {
		global $version;
		$svnVersion = @file_get_contents('http://wikicrowd.googlecode.com/svn/trunk/build/version.txt');
		$url = "http://wikicrowd.googlecode.com/files/wikicrowd-$svnVersion.zip";
		if(preg_match('/\d\.\d\.\d/', $svnVersion) && strcmp($svnVersion, VERSION) > 0) {
?><p class="update"><?=sprintf(getMessage('NewVersionAvailable'), $url)?><form method="post" action="">
<input type="hidden" name="update" value="<?=$svnVersion?>"/>
<input type="submit" value="<?=getMessage('Update')?>"/></form></p>
<?php
		}
	}

	$errors = array();

	$title = title;
	$homePage = homePage;
	$supportEmail = supportEmail;
	$locale = LOCALE;

	$anyoneCanRegister = anyoneCanRegister;
	$newUserCanEdit = newUserCanEdit;
	$newUserCanView = newUserCanView;

	$users = array();

	if (strcasecmp($_SERVER['REQUEST_METHOD'], 'get') == 0) {
		$dir = opendir(PERSONS);
		while($f = readdir($dir)) {
			if (preg_match('/^([^.]+)\.xml$/', $f, $matchs)) {
				$user = loadPerson($matchs[1]);
				$uid = $user->getAttribute('uid');
				$users[$uid]['admin'] = isAdmin($user);
				$users[$uid]['canEdit'] = personCanEdit($user);
				$users[$uid]['canView'] = personCanView($user);

				$users[$uid]['original_admin'] = $users[$uid]['admin'];
				$users[$uid]['original_canEdit'] = $users[$uid]['canEdit'];
				$users[$uid]['original_canView'] = $users[$uid]['canView'];
			}
		}
		closedir($dir);
	}

	$messages = array();

	if (strcasecmp($_SERVER['REQUEST_METHOD'], 'post') == 0) {
		$users = $_POST['user'];
		
		$title = stripslashes(trim($_POST['title']));
		if($title == "")
			$errors['title'] = getMessage('TitleIsRequired');

		$homePage = stripslashes(trim($_POST['homePage']));
		if ($homePage == "")
			$errors['homePage'] = getMessage('HomePageIsRequired');

		$supportEmail = trim($_POST['supportEmail']);
		if ($supportEmail != "" && !preg_match('/[\w\d._+]+@[\w\d.-]+\.[a-z]{2,4}$/i', $email))
			$errors['supportEmail'] = getMessage('SupportsEmailLookWrong');

		$anyoneCanRegister = array_key_exists('anyoneCanRegister', $_POST) ? $_POST['anyoneCanRegister'] : '';
		$newUserCanEdit = array_key_exists('newUserCanEdit', $_POST) ? $_POST['newUserCanEdit'] : '';
		$newUserCanView = array_key_exists('newUserCanView', $_POST) ? $_POST['newUserCanView'] : '';

		if (! array_key_exists('admin', $users[$person->getAttribute('uid')]) 
			|| ! $users[$person->getAttribute('uid')]['admin']) {
			// check that some other admins exist
			$adminExists = false;
			foreach($users as $uid=>$property) {
				if ($uid == "guest" && array_key_exists('admin', $property) && $property['admin'])
					$errors[] = getMessage('GuestNotAdmin');
				else
					$adminExists |= (array_key_exists('admin', $property) && $property['admin']);
			}
			if (!$adminExists)
				$errors[] = getMessage('NoAdminExist');
		}

		$locale = $_POST['locale'];

		if(count($errors) == 0) {
			$dom = new DOMDocument();
			$dom->load(CORE.'config.xml');

			setProperty($dom, 'title', $title);
			setProperty($dom, 'homePage', $homePage);
			setProperty($dom, 'supportEmail', $supportEmail);

			setProperty($dom, 'LOCALE', $locale);

			setProperty($dom, 'anyoneCanRegister', $anyoneCanRegister);
			setProperty($dom, 'newUserCanEdit', $newUserCanEdit);
			setProperty($dom, 'newUserCanView', $newUserCanView);
			$dom->save(CORE.'config.xml');

			foreach($users as $uid=>$property) {
				$admin = array_key_exists('admin', $property) && $property['admin'];
				$canEdit = array_key_exists('canEdit', $property) && $property['canEdit'];
				$canView = array_key_exists('canView', $property) && $property['canView'];

				$changed = 
						$admin != $property['original_admin']
					|| 	$canEdit != $property['original_canEdit']
					|| 	$canView != $property['original_canView'];

				if ($changed) {
					$user = loadPerson($uid);
					$user->setAttribute('can-edit', $canEdit);
					$user->setAttribute('can-view', $canView);
					$user->setAttribute('admin', $admin);
					$user->ownerDocument->save(PERSONS.$uid.'.xml');
				}
			}
			header('Location: '.www.'configure/?ts='.time());
			exit;
		}
	}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=LOCALE?>">
<head><title><?=getMessage('Configure')?> | <?=title?></title>
<link rel="shortcut icon" href="<?=www?>core/img/favicon.gif" />
<link rel="stylesheet" type="text/css" href="<?=www?>core/css/main.css"/>
<script type="text/javascript">var www = '<?=www?>';</script>
<script type="text/javascript" src="<?=www?>core/js/base.js" charset="windows-1251">//<!--"--></script>
<script type="text/javascript" src="<?=www?>core/js/auth.js" charset="windows-1251">//<!--"--></script>
<style type="text/css">
h1 { margin: 0.25em 0 0.5em 0.65em; }
h2 { margin: 1em 0 0 0; }
input { display: block; margin: 0.75em 0 0 0;}
input.hidden { display: none;}
label { display: block; margin: 1em 0.25em 0 0;}
form { color: #000; }
.optional { color: #999; }
.error { padding: 1em; width: 31em; border: 1px solid #C00; background: #FDD; }
.error li { margin-left: 1em; color: #C00;}
.error li span { color: #000; }
.update { border: 1px solid #0C0; background: #DFD; padding: 1em; width: 31em; }
.info { border: 1px solid #CC0; background: #FFD; margin-top: 1em; padding: 1em; width: 36.8em; font-size:85%;}
table { border-right: 1px solid #999; border-bottom: 1px solid #999; }
td { text-align: center; border-left: 1px solid #999; padding: 0.25em;}
td.left { text-align:left; }
td.right { text-align:right; }
td.ne { border-left:none;}
td label, td input { display:inline; margin: 0; float:none;}
tr.odd { background: #EEE; }
th { border: 1px solid #999; border-right: none; font-size:90%; padding:0.25em;}
th.ne { border: none; border-bottom: 1px solid #999; }
<?
	if (count($errors) > 0) {
		foreach(array_keys($errors) as $id)
			if (trim($id) != "")
				echo '#'.$id.', ';
		echo '.error { background: #FDD; border: 1px solid #C00; }';
	}
?>
</style>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
</head>
<body><?
		
	menu('configure');

?><h1><?=getMessage('Configure')?> &raquo; <a href="<?=www?>"><?=title?></a></h1>
<div id="chapter">
<?php

	// check newer version
	checkNewerVersion();
	
	if (count($errors) > 0) {
?><ul class="error"><?
		foreach($errors as $error) {
?><li><span><?=$error?></span></li><?
		}
?></ul><?
	}

	if (count($messages) > 0) {
?><p class="info"><?
		foreach($messages as $message) {
?><?=$message?><br/><?
		}
?></p><?
	}

	$locales = array();

	$dir = opendir(CORE.'xml/locale');
	while($f = readdir($dir)) {
		if (preg_match('/^([^.]+)\.xml$/', $f, $matchs)) {
			$code = $matchs[1];
			$dom = new DOMDocument();
			$dom->load(CORE.'xml/locale/'.$code.'.xml');
			$locales[$code] = $dom->documentElement->getAttribute('language');
		}
	}
	closedir($dir);

?><form method="post" action="">
<label for="title"><?=getMessage('Title')?>:</label> <input type="text" name="title" id="title" size="50" value="<?=$title?>"/>
<label for="homePage"><?=getMessage('HomePage')?>:</label> <input type="text" name="homePage" id="homePage" size="50" value="<?=$homePage?>"/>
<label class="optional" for="supportEmail"><nobr><?=getMessage('SupportsEmail')?>:</nobr><br/><small>(optional)</small></label> <input type="text" name="supportEmail" id="supportEmail" size="50" value="<?=$supportEmail?>"/>
<h2><?=getMessage('Language')?></h2>
<label for="locale"><?=getMessage('Use')?> <select name="locale" id="locale"><?php
	foreach($locales as $code=>$name) {
		if ($code == "") continue;
		echo '<option '.($code == $locale ? 'selected="selected" ' : '').
			"value='$code'>$name</option>";
	}
?></select> <?=getMessage('LanguageForUI')?></label>

<h2><?=getMessage('AccessRights')?></h2>
<table border="0" cellspacing="0" cellpadding="3">
<thead>
<th><?=getMessage('AnyoneCanRegister')?></th>
<th><?=getMessage('NewUserCanEdit')?></th>
<th><?=getMessage('NewUserCanView')?></th>
</thead>
<tbody>
<tr>
<td><input type="checkbox" name="anyoneCanRegister" value="1"<?
	if ($anyoneCanRegister) echo ' checked="checked"'; ?>/></td>
<td><input type="checkbox" name="newUserCanEdit" value="1"<?
	if ($newUserCanEdit) echo ' checked="checked"'; ?>/></td>
<td><input type="checkbox" name="newUserCanView" value="1"<?
	if ($newUserCanView) echo ' checked="checked"'; ?>/></td>
</tr>
</tbody>
</table>

<h2><?=getMessage('UserRights')?></h2>
<?
	foreach($users as $uid=>$property) {
?><input class="hidden" type="hidden" name="user[<?=$uid?>][uid]" value="<?=
		$uid?>"/><input class="hidden" type="hidden" name="user[<?=$uid?>][original_admin]" value="<?=
		$property['original_admin']?>"/><input class="hidden" type="hidden" name="user[<?=$uid?>][original_canEdit]" value="<?=
		$property['original_canEdit']?>"/><input class="hidden" type="hidden" name="user[<?=$uid?>][original_canView]" value="<?=
		$property['original_canView']?>"/><?
	}
?><table border="0" cellspacing="0" cellpadding="3">
<thead><tr><th><?=getMessage('User')?></th><th><?=
	getMessage('Admin')?></th><th><?=getMessage('CanEdit')?></th><th><?=getMessage('CanRead')?></th></tr>
<tbody>
<?
	foreach($users as $uid=>$property) {
?><tr><td class="right"><a href="<?=www?>person/<?=$uid?>"><?=$uid?></a></td>
<td><input type="checkbox" name="user[<?=$uid?>][admin]"<?

		if (array_key_exists('admin', $property) && $property['admin'] && $uid != "guest")
			echo ' checked="checked"';
		if ($uid == "guest") {
			echo ' disabled="disabled"';
			echo ' title="'.getMessage('GuestNotAdmin').'"';
		}
?> value="1"/></td><td><input type="checkbox" name="user[<?=$uid?>][canEdit]"<?
		if (array_key_exists('canEdit', $property) && $property['canEdit'])
			echo ' checked="checked"';
?> value="1"/></td><td><input type="checkbox" name="user[<?=$uid?>][canView]"<?
		if (array_key_exists('canView', $property) && $property['canView'])
			echo ' checked="checked"';
?> value="1"/></td></tr>
<?
	}
	
?></tbody>
</table>
<br/>
<input style="padding:0.25em;font-size:110%;" type="submit" value="<?=getMessage('Save')?>"/>
</form>
</div>
<p class="copyright"><a href="http://code.google.com/p/wikicrowd/">WikiCrowd</a> v.<?php echo VERSION; //?> by 
<a href="http://davidovsv.narod.ru/">Stas Davydov</a> and <a href="http://outcorp-ru.blogspot.com/">Outcorp</a>.<br/>
License: <a href="http://www.gnu.org/licenses/lgpl.html">LGPL</a>.</p>
</body>
</html>
<?
	ob_end_flush();
?>