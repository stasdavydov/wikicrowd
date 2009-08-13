<?
	require_once 'core.php';
	require_once 'core/person.php';

	ob_start('ob_gzhandler');

	header('Content-type: text/xml');
	echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";

	function auth($msg = '', $login = '', $password = '') {
?><auth login="<?=
		htmlspecialchars($login, ENT_COMPAT, 'utf-8')?>" password="<?=
		htmlspecialchars($password, ENT_COMPAT, 'utf-8')?>"><?=$msg?></auth><?
		ob_end_flush();
		exit;
	}

	if(! array_key_exists('do', $_REQUEST)) {
		internal(getMessage('ActionIsNotSet'));
	}

	$do = $_REQUEST['do'];
	
	
	if ($do == "edit") {

		$chapter = new chapter();

		if (! array_key_exists('id', $_REQUEST))
			internal(getMessage('IDisNotSet'));
		$id = trim($_REQUEST['id']);
		if ($id == "") 
			internal(getMessage('IDisEmpty'));
		if (! array_key_exists('rev', $_REQUEST))
			internal(getMessage('RevIsNotSet'));
		$rev = trim($_REQUEST['rev']);
		if ($rev == "") 
			internal(getMessage('RevIsEmpty'));

		$chapter->edit($id, $rev);
	
	
	
	} else if ($do == "save") {

		if (! ($person = getSessionPerson())) {
			if (array_key_exists('login', $_REQUEST) 
				&& array_key_exists('password', $_REQUEST)) {
				$login = trim($_REQUEST['login']);
				$password = trim($_REQUEST['password']);
			    if ($person = loadPerson($login)) {
			    	if($person->getAttribute('password') == md5($password))
				    	doLogin($person);
				    else {
						auth(getMessage('LoginOrPasswordWrong'), $login, $password);
				    }
				} else {
					auth(getMessage('LoginWrong'), $login, $password);
				}
			} else
				auth();
		}

		$chapter = new chapter();

		$chapter->update($_REQUEST, $person->getAttribute('uid'));


	} else if ($do == "loadchanges") {

		$chapter = new chapter();

		if (! array_key_exists('id', $_REQUEST))
			internal(getMessage('IDisNotSet'));
		$id = trim($_REQUEST['id']);
		if ($id == "") 
			internal(getMessage('IDisEmpty'));

		$chapter->changes($id);



	} else if ($do == "auth") {

		if (! array_key_exists('login', $_REQUEST))
			internal(getMessage('LoginIsNotSet'));
		$login = trim($_REQUEST['login']);
		if ($login == "")
			warn(getMessage('LoginIsEmpty'));

		if (!array_key_exists('password', $_REQUEST))
			internal(getMessage('PasswordIsNotSet'));
		$password = trim($_REQUEST['password']);
	
	    $person = loadPerson($login);
	    if ($person == NULL) {
			if ($person = loadPerson($login, true))
				warn(getMessage('AccountIsNotActive'));
			else
	    		warn(getMessage('AccountIsNotFound').': '.$login);
	    }

	    if ($person->getAttribute('password') != md5($password))
	    	warn(getMessage('PasswordIsWrong'));

	    doLogin($person, array_key_exists('remember', $_REQUEST) ? $_REQUEST['remember'] : false);

?><logged/><?



	} else if ($do == "register") {
		if (! anyoneCanRegister)
			warn(getMessage('RegistrationIsClosed'));

		if (! array_key_exists('login', $_REQUEST))
			internal(getMessage('LoginIsNotSet'));
		$login = trim($_REQUEST['login']);
		if ($login == "")
			warn(getMessage('LoginCannotBeEmpty'));
		else if (! preg_match('/^[a-zA-Z0-9]+$/', $login)) 
			warn(getMessage('LoginRule'));
		else if (loadPerson($login))
			warn(getMessage('LoginIsUsed'));

		if (!array_key_exists('password', $_REQUEST))
			internal(getMessage('PasswordIsNotSet'));
		$password = stripslashes(trim($_REQUEST['password']));
		if ($password == "")
			warn(getMessage('PasswordCannotBeEmpty'));

		if (!array_key_exists('name', $_REQUEST))
			internal(getMessage('NameIsNotSet'));
		$name = stripslashes(trim($_REQUEST['name']));
		if ($name == "")
			warn(getMessage('NameIsRequired'));

		if (!array_key_exists('email', $_REQUEST))
			internal(getMessage('EmailIsNotSet'));
		$email = trim($_REQUEST['email']);
		if ($email == "")
			warn(getMessage('EmailCannotBeEmpty'));
		else if (! preg_match('/[\w\d._+]+@[\w\d.-]+\.[a-z]{2,4}$/i', $email)) 
			warn(getMessage('EmailIsWrong'));

		if (!array_key_exists('info', $_REQUEST))
			internal(getMessage('InfoIsNotSet'));
		$info = strip_tags(stripslashes(trim($_REQUEST['info'])));

		if ($person = loadPerson($login, true)) {
			if ($person->getAttribute('email') != $email && 
				$person->getAttribute('password') != md5($password)) {
				warn(getMessage('LoginIsRegistered'));
			}
		}

		// 1. create person's file in sandbox
		$person = createPerson($login, $password, $name, $email, $info, 
			newUserCanEdit, newUserCanView, false);
		$person->save(HOME."persons/sandbox/$login.xml");

		// 2. send confirmation e-mail 
		@mail ($email, 
			"=?UTF-8?b?".base64_encode(getMessage('RegistrationConfirmation').' "'.title.'"')."?=", 
			chunk_split(base64_encode($msg=
				sprintf(getMessage('RegistrationEmail'),
					$name, title, 
					"http://{$_SERVER['SERVER_NAME']}".www."check/$login-".md5(md5($password)).'/',
					"http://{$_SERVER['SERVER_NAME']}".www))), 
			"From: [WikiCrowd] <".supportEmail.">\n".
//			"BCC: Stas Davydov <$SUPPORT_EMAIL>\n".
			"Content-Type: text/plain;\r\n\tcharset=UTF-8\n".
			"Content-Transfer-Encoding: base64\n");

?><registered/><?


	
	} else if ($do == "chapterchanges") {

		if(! array_key_exists('last', $_REQUEST))
			internal(getMessage('LastCheckDateIsNotSet'));
		$last = $_REQUEST['last'];

		$chapter = new chapter();
		$person = getSessionPerson();
		echo $chapter->changedSince($last, $person ? 'edit' : 'view');
		

		
	} else if ($do == "logout") {

	    setcookie('uid', '', strtotime('-1 day'), '/');

?><loggedout/><?
		

	
	} else if ($do == "saveperson") {

		if (! ($person = getSessionPerson()))
			auth();

		if (!array_key_exists('name', $_REQUEST))
			internal(getMessage('NameIsNotSet'));
		$name = stripslashes(trim($_REQUEST['name']));
		if ($name == "")
			warn(getMessage('NameIsRequired'));

		if (!array_key_exists('email', $_REQUEST))
			internal(getMessage('EmailIsNotSet'));
		$email = trim($_REQUEST['email']);
		if ($email == "")
			warn(getMessage('EmailCannotBeEmpty'));
		else if (! preg_match('/[\w\d._+]+@[\w\d.-]+\.[a-z]{2,4}$/i', $email)) 
			warn(getMessage('EmailIsWrong'));

		if (!array_key_exists('info', $_REQUEST))
			internal(getMessage('InfoIsNotSet'));
		$info = stripslashes(trim($_REQUEST['info']));

		if (!array_key_exists('password', $_REQUEST))
			internal(getMessage('PasswordIsNotSet'));
		$password = stripslashes(trim($_REQUEST['password']));
		if (!array_key_exists('oldpassword', $_REQUEST))
			internal(getMessage('OldPasswordIsNotSet'));
		$oldpassword = stripslashes(trim($_REQUEST['oldpassword']));
		if ($password != "" && $oldpassword == "")
			warn(getMessage('NewPasswordOldPassword'));
		else if ($password != "" && md5($oldpassword) != $person->getAttribute('password'))
			warn(getMessage('WrongOldPassword'));
		else if ($password != "")
			$person->setAttribute('password', md5($password));

		$notify = array_key_exists('notify', $_REQUEST) && $_REQUEST['notify'] == "true" 
			? "true" : "false";

		$sandbox = $person->getAttribute('email') != $email;
		if ($sandbox) {
			$person->setAttribute('newemail', $email);
			$checkCode = md5(time() . $email);
			$person->setAttribute('newemailcheck', $checkCode);
		}
		$person->setAttribute('name', $name);
		$infoNode = $person->getElementsByTagName('info')->item(0);
		$person->setAttribute('notify', $notify);
		while($infoNode->firstChild) $infoNode->removeChild($infoNode->firstChild);
		$infoNode->appendChild($infoNode->ownerDocument->createTextNode($info));
		$personFileName = 'persons/'.$person->getAttribute('uid').'.xml';
		$person->ownerDocument->save($personFileName);
		if($sandbox) {
//			rename($personFileName, 'persons/sandbox/'.$person->getAttribute('uid').'.xml');
			// send confirmation e-mail 
			@mail ($email, 
				"=?UTF-8?b?".base64_encode(getMessage('EmailChangeConfirmation').' "'.title.'"')."?=", 
				chunk_split(base64_encode($msg=
					sprintf(getMessage('EmailChangeMessage'),
						$name, title, 
						"http://{$_SERVER['SERVER_NAME']}".www."check/$login-$checkCode/",
						"http://{$_SERVER['SERVER_NAME']}".www))), 
				"From: [WikiCrowd] <".supportEmail.">\n".
//				"BCC: Stas Davydov <$SUPPORT_EMAIL>\n".
				"Content-Type: text/plain;\r\n\tcharset=UTF-8\n".
				"Content-Transfer-Encoding: base64\n");
		}

?><saved/><?



	} else if ($do == "forget") {

		if (!array_key_exists('email', $_REQUEST))
			internal(getMessage('EmailIsNotSet'));
		$email = trim($_REQUEST['email']);
		if ($email == "")
			warn(getMessage('EmailIsEmpty'));
		else if (! preg_match('/[\w\d._+]+@[\w\d.-]+\.[a-z]{2,4}$/i', $email)) 
			warn(getMessage('EmailIsWrong'));
		
		$foundPerson = NULL;
		$d = opendir('persons/');
		while($f = readdir($d)) {
			if (preg_match('/^([a-zA-Z0-9]*)\.xml$/', $f, $matches)) {
				$person = loadPerson($matches[1]);
				if ($person->getAttribute("email") == $email) {
					$foundPerson = $person;
					break;
				}
			}
		}
		closedir($d);
		if ($foundPerson) {
			$newPassword = substr(md5(time()), 0, 8);
			$foundPerson->setAttribute('password', md5($newPassword));
			$foundPerson->ownerDocument->save('persons/'.$foundPerson->getAttribute('uid').'.xml');

			@mail ($email, 
				"=?UTF-8?b?".base64_encode(getMessage('NewPasswrodOnSite').' "'.title.'"')."?=", 
				chunk_split(base64_encode($msg=
					sprintf(getMessage('NewPasswrodMessage'),
						$name, title, $foundPerson->getAttribute('uid'), $newPassword,
                        "http://{$_SERVER['SERVER_NAME']}".www."auth/",
                        "http://{$_SERVER['SERVER_NAME']}".www."person/".$foundPerson->getAttribute('uid'),
                        "http://{$_SERVER['SERVER_NAME']}".www))), 
				"From: [WikiCrowd] <supportEmail>\n".
//				"BCC: Stas Davydov <$SUPPORT_EMAIL>\n".
				"Content-Type: text/plain;\r\n\tcharset=UTF-8\n".
				"Content-Transfer-Encoding: base64\n");

?><sent/><?
		} else {
			$d = opendir('persons/sandbox/');
			while($f = readdir($d)) {
				if (preg_match('/^([a-zA-Z0-9]*)\.xml$/', $f, $matches)) {
					$person = loadPerson($matches[1]);
					if ($person->getAttribute("email") == $email) {
						$foundPerson = $person;
						break;
					}
				}
			}
			closedir($d);

			if ($foundPerson) {
				warn(getMessage('AccountIsNotActiveYet'));
			} else {
				warn(getMessage('ThereAreNoAccounts'));
			}
		}


	}

	ob_end_flush();
?>