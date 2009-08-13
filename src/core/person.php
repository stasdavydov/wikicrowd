<?php

function createPerson($login, $password, $name, $email, $info, $canEdit, $canView, $admin = false) {
	$dom = new DOMDocument('1.0', 'UTF-8');

	$person = $dom->createElement('person');
	$person->setAttribute('uid', $login);
	$person->setAttribute('password', md5($password));
	$person->setAttribute('name', $name);
	$person->setAttribute('email', $email);
	
	$person->setAttribute('created-ts', time());
	$person->setAttribute('created-date', date('d/m/Y H:i'));
	
	$person->setAttribute('can-edit', $canEdit);
	$person->setAttribute('can-view', $canView);
	$person->setAttribute('admin', $admin);

	$inf = $dom->createElement('info');
	$inf->appendChild($dom->createCDATASection($info));
	$person->appendChild($inf);
	$dom->appendChild($person);
	return $dom;
}

function isGuest($person) {
	return $person->getAttribute('uid') == "guest";
}

function isAdmin($person) {
 	return $person->getAttribute("admin");
}

function personCanEdit($person) {
	return $person->getAttribute('can-edit');
}

function personCanView($person) {
	return $person->getAttribute('can-view');
}

function personCan($person, $right) {
	return $right == "edit" 
		? personCanEdit($person)
		: ($right == "view"
			? personCanView($person)
			: false);
}

function getSessionPerson() {
	if (array_key_exists('uid', $_COOKIE)) {
		$uid = split('-', $_COOKIE['uid']);
		if (count($uid) == 2) {
			$person = loadPerson($uid[0]);
			if ($person && md5($person->getAttribute('password')) == $uid[1])
				return $person;
		}
	} else
		return loadPerson('guest');
}

function loadPerson($uid, $sandbox = false) {
	$personFile = PERSONS.($sandbox ? 'sandbox/' : '').$uid.'.xml';
	if (! file_exists($personFile))
		return NULL;
	$dom = new DOMDocument();
	$dom->load($personFile);
	return $dom->documentElement;
}

function doLogin($person, $remember = false) {
    setcookie('uid', $person->getAttribute('uid').'-'.md5($person->getAttribute('password')),
    	$remember ? strtotime('+180 days') : 0, '/');
}

function getPersonIndex() {
	$users = array();

	$personsIndex = CACHE.'persons-index.xml';
	$personFilesTs = 0;
	$dir = opendir(PERSONS);
	while($f = readdir($dir)) {
		if (preg_match('/^([^.]+)\.xml$/', $f, $matchs)) {
			$uid = $matchs[1];
			$fileName = $uid.'.xml';
			$personFilesTs = max(filemtime(PERSONS.$fileName), $personFilesTs);
			$users[$uid] = 0;
		}
	}
	closedir($dir);

	$usersDOM = new DOMDOcument('1.0', 'UTF-8');
	if (! file_exists($personsIndex) || filemtime($personsIndex) < $personFilesTs) {
		// rebuild
		$usersDOM->appendChild($usersDOM->createElement('persons'));
		foreach($users as $uid=>$x)
			$usersDOM->documentElement->appendChild($usersDOM->importNode(loadPerson($uid), true));
		$usersDOM->save($personsIndex);
	} else
		$usersDOM->load($personsIndex);

	return array($users, $usersDOM);
}
?>