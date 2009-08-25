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
		if(preg_match('/\d\.\d\.\d/', $svnVersion) && version_compare($svnVersion, VERSION) > 0) {
			$url = "http://wikicrowd.googlecode.com/files/wikicrowd-$svnVersion.zip";

?><form method="post" action=""><p class="update"><?=sprintf(getMessage('NewVersionAvailable'), $url, $svnVersion)?>
<input type="hidden" name="update" value="<?=$svnVersion?>"/>
<input type="submit" value="<?=getMessage('Download')?>"/></p></form>
<?php

		}
	}

	if (strcasecmp($_SERVER['REQUEST_METHOD'], 'post') == 0 
		&& array_key_exists('update', $_POST)) {
		// update
		$url = "http://wikicrowd.googlecode.com/files/wikicrowd-{$_POST['update']}.zip";
		file_put_contents(HOME.'tmp.zip', file_get_contents($url));
		$zip = new ZipArchive();
		$zip->open(HOME.'tmp.zip');
		$zip->extractTo(HOME, 'install.php');
		$zip->close();
		unlink(HOME.'tmp.zip');

		header('Location: '.www.'install.php');
		exit;
	}

	$errors = array();

	$title = title;
	$homePage = homePage;
	$supportEmail = supportEmail;
	$locale = LOCALE;

	$anyoneCanRegister = anyoneCanRegister;
	$newUserCanEdit = newUserCanEdit;
	$newUserCanView = newUserCanView;

	list($users, $usersDOM) = getPersonIndex();

	$messages = array();

	if (strcasecmp($_SERVER['REQUEST_METHOD'], 'post') == 0) {
		$users = $_POST['user'];
		
		$persons = $usersDOM->getElementsByTagName('person');
		$personsChanged = false;
		for($i = 0; $i < $persons->length; $i++) {
			$user = $persons->item($i);
			$uid = $user->getAttribute('uid');

			$admin = is_array($users[$uid]) 
				&& array_key_exists('admin', $users[$uid]) && $users[$uid]['admin'];
			$canEdit = is_array($users[$uid]) 
				&& array_key_exists('canEdit', $users[$uid]) && $users[$uid]['canEdit'];
			$canView = is_array($users[$uid]) 
				&& array_key_exists('canView', $users[$uid]) && $users[$uid]['canView'];

			$changed = 
				$admin != $user->getAttribute('admin')
				|| 	$canEdit != $user->getAttribute('can-edit')
				|| 	$canView != $user->getAttribute('can-view');
			$personsChanged |= $changed;

			if ($changed) {
				$user->setAttribute('can-edit', $canEdit);
				$user->setAttribute('can-view', $canView);
				$user->setAttribute('admin', $admin);
				$user->setAttribute('changed', '');
			}
		}

		$title = stripslashes(trim($_POST['title']));
		if($title == "")
			$errors['title'] = getMessage('TitleIsRequired');

		$homePage = stripslashes(trim($_POST['homePage']));
		if ($homePage == "")
			$errors['homePage'] = getMessage('HomePageIsRequired');

		$supportEmail = trim($_POST['supportEmail']);
		if ($supportEmail != "" && !preg_match(EMAIL_REGEXP, $supportEmail))
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

			if ($personsChanged) {
				$xpath = new DOMXPath($usersDOM);
				$persons = $xpath->query('//person[@changed]');
				for($i = 0; $i < $persons->length; $i++) {
					$user = $persons->item($i);
					$user->removeAttribute('changed');

					$person = loadPerson($user->getAttribute('uid'));
					$person->setAttribute('can-edit', $user->getAttribute('can-edit'));
					$person->setAttribute('can-view', $user->getAttribute('can-view'));
					$person->setAttribute('admin', $user->getAttribute('admin'));
					$person->ownerDocument->save(PERSONS.$user->getAttribute('uid').'.xml');
				}
		
				$usersDOM->save($personsIndex);
			}

			header('Location: '.www.'configure/?ts='.time());
			exit;
		}
	}

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="<?=LOCALE?>">
<head><title><?=getMessage('Configure')?> &#0187; <?=title?></title>
<link rel="shortcut icon" href="<?=www?>core/img/favicon.gif" />
<link rel="stylesheet" type="text/css" href="<?=www?>core/css/main.css"/>
<script type="text/javascript">var www = '<?=www?>';</script>
<script type="text/javascript" src="<?=www?>core/js/base.js" charset="windows-1251">//<!--"--></script>
<script type="text/javascript" src="<?=www?>core/js/auth.js" charset="windows-1251">//<!--"--></script>
<style type="text/css">
h1 { margin: 0.25em 0 0.5em 0.65em; }
h2 { margin: 1em 0 0 0; }
#chapter { padding-top: 1em !important;}
.block { display: block; }
input { margin: 0.75em 0 0 0;}
input.hidden { display: none;}
label { display: block; margin: 1em 0.25em 0 0;}
form { color: #000; }
.optional { color: #999; }
.error { padding: 1em; width: 31em; border: 1px solid #C00; background: #FDD; }
.error li { margin-left: 1em; color: #C00;}
.error li span { color: #000; }
.update { border: 1px solid #0C0; background: #DFD; padding: 0.75em; width: 25em; }
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
<label for="title"><?=getMessage('Title')?>:</label> <input class="block" type="text" name="title" id="title" size="50" value="<?=$title?>"/>
<label for="homePage"><?=getMessage('HomePage')?>:</label> <input class="block" type="text" name="homePage" id="homePage" size="50" value="<?=$homePage?>"/>
<label class="optional" for="supportEmail"><nobr><?=getMessage('SupportsEmail')?>:</nobr><br/><small>(optional)</small></label> <input class="block" type="text" name="supportEmail" id="supportEmail" size="50" value="<?=$supportEmail?>"/>
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
	echo transformDOM($usersDOM, CORE.'xml/persons_index.xsl', array());
	
?><br/>
<input style="padding:0.25em;font-size:110%;" type="submit" value="<?=getMessage('Save')?>"/>
</form>
</div>
<p class="copyright"><a href="http://code.google.com/p/wikicrowd/">WikiCrowd</a> v.<?php echo VERSION; //?> by 
<a href="http://davidovsv.narod.ru/">Stas Davydov</a> and <a href="http://outcorp-ru.blogspot.com/">Outcorp</a>.<br/>
<?
	if(defined('license')) {
		echo license;
	} else {
?>License: <a href="http://www.gnu.org/licenses/lgpl.html">LGPL</a>.<?
	}
?></p>
</body>
</html>
<?
	ob_end_flush();
?>