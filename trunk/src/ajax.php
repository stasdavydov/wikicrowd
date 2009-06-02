<?
	require_once 'core.php';
//	require_once 'diff.php';

	ob_start('ob_gzhandler');

	header('Content-type: text/xml');
	echo '<?xml version="1.0" encoding="UTF-8"?>'."\n";

	function auth($msg = '', $login = '', $password = '') {
?><auth login="<?=
		htmlspecialchars($login, ENT_COMPAT, 'utf-8')?>" password="<?=
		htmlspecialchars($password, ENT_COMPAT, 'utf-8')?>"><?=
		iconv('windows-1251', 'utf-8', $msg)?></auth><?
		ob_end_flush();
		exit;
	}

	function logChange($book, $id, $author) {
		$dom = new DOMDocument();
		$changesFile = CORE.'core/changes.xml';
		if (! file_exists($changesFile)) {
			$dom = new DOMDocument('1.0', 'utf-8');
			$dom->appendChild($dom->implementation->createDocumentType(
				'changes', 'WikiCrowd', 'xml/wikicrowd.dtd'));
			$dom->appendChild($dom->createElement('changes'));
		} else
			$dom->load($changesFile);

		$change = $dom->createElement('change');
		$change->setAttribute('chapter', $book);
		$change->setAttribute('id', $id);
		setAuthorRequiredData($change, $author, time());
		$dom->documentElement->appendChild($change);
		$dom->save($changesFile);
	}

	function createPerson($login, $password, $name, $email, $info) {
		$dom = new DOMDocument('1.0', 'UTF-8');
		$dom->appendChild($dom->implementation->createDocumentType(
			'person', 'WikiCrowd', '../core/xml/wikicrowd.dtd'));

		$person = $dom->createElement('person');
		$person->setAttribute('uid', $login);
		$person->setAttribute('password', md5($password));
		$person->setAttribute('name', $name);
		$person->setAttribute('email', $email);
		$person->setAttribute('created-ts', time());
		$person->setAttribute('created-date', date('d/m/Y H:i'));
		$inf = $dom->createElement('info');
		$inf->appendChild($dom->createCDATASection($info));
		$person->appendChild($inf);
		$dom->appendChild($person);
		return $dom;
	}

	if(! array_key_exists('do', $_REQUEST)) {
		internal('Действие не определено.');
	}

	$do = $_REQUEST['do'];
	
	
	if ($do == "edit") {

		$chapter = new chapter();

		if (! array_key_exists('id', $_REQUEST))
			internal('ID не указан.');
		$id = trim($_REQUEST['id']);
		if ($id == "") 
			internal('ID пуст.');
		if (! array_key_exists('rev', $_REQUEST))
			internal('Rev не указан.');
		$rev = trim($_REQUEST['rev']);
		if ($rev == "") 
			internal('Rev пуста.');

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
						auth('Логин или пароль указаны неверно.', $login, $password);
				    }
				} else {
					auth('Логин указан неверно.', $login, $password);
				}
			} else
				auth();
		}

		$chapter = new chapter();

		$chapter->update($_REQUEST, $person->getAttribute('uid'));

//		logChange($chapter, $id, $person->getAttribute('uid'));


	} else if ($do == "loadchanges") {

		$chapter = new chapter();

		if (! array_key_exists('id', $_REQUEST))
			internal('ID не указан.');
		$id = trim($_REQUEST['id']);
		if ($id == "") 
			internal('ID пуст.');

		$chapter->changes($id);



	} else if ($do == "auth") {

		if (! array_key_exists('login', $_REQUEST))
			internal('Логин не указан');
		$login = trim($_REQUEST['login']);
		if ($login == "")
			warn('Логин пуст');

		if (!array_key_exists('password', $_REQUEST))
			internal('Пароль не указан');
		$password = trim($_REQUEST['password']);
	
	    $person = loadPerson($login);
	    if ($person == NULL) {
			if ($person = loadPerson($login, true))
				warn('Ваша учетная запись еще не активирована. Пожалуйста, кликните на ссылку '.
					'из письма с подтверждением, чтобы активировать Вашу учетную запись.');
			else
	    		warn('Нет такого человека: '.$login);
	    }

	    if ($person->getAttribute('password') != md5($password))
	    	warn('Пароль, который Вы указали, неверный');

	    doLogin($person, array_key_exists('remember', $_REQUEST) ? $_REQUEST['remember'] : false);

?><logged/><?



	} else if ($do == "register") {

		if (! array_key_exists('login', $_REQUEST))
			internal('Логин не указан');
		$login = trim($_REQUEST['login']);
		if ($login == "")
			warn('Логин не может быть пустым.');
		else if (! preg_match('/^[a-zA-Z0-9]+$/', $login)) 
			warn('Логин может содержать только латинские буквы (a-z, A-Z) или арабские цифры (0-9).');
		else if (loadPerson($login))
			warn('Указанный Вами логин уже используется на нашем сайте.');

		if (!array_key_exists('password', $_REQUEST))
			internal('Пароль не указан');
		$password = stripslashes(trim($_REQUEST['password']));
		if ($password == "")
			warn('Пароль не может быть пустым. Если не задать пароль, кто угодно сможет выдать себя за вас.');

		if (!array_key_exists('name', $_REQUEST))
			internal('Имя не указано');
		$name = stripslashes(trim($_REQUEST['name']));
		if ($name == "")
			warn('Нам бы очень хотелось знать, как Вас зовут. Укажите, пожалуйста, Ваше имя.');

		if (!array_key_exists('email', $_REQUEST))
			internal('E-mail не указан');
		$email = trim($_REQUEST['email']);
		if ($email == "")
			warn(
				'E-mail нужен для того, чтобы точно убедиться, что Вы - это Вы. '.
				'Еще он нужен, чтобы получить письмом пароль, если вдруг Вы его забыли. '.
				'А еще нам очень хочется иметь возможность связаться с Вами, если что (никакого СПАМа!). '.
				'Укажите, пожалуйста, Ваш e-mail.');
		else if (! preg_match('/[\w\d._+]+@[\w\d.-]+\.[a-z]{2,4}$/i', $email)) 
			warn('В Вашем e-mail ошибка, проверьте, пожалуйста. Хочется чего-то вроде email@server.com');

		if (!array_key_exists('info', $_REQUEST))
			internal('Информация не указана');
		$info = strip_tags(stripslashes(trim($_REQUEST['info'])));
		if ($info == "")
			warn('Нам бы очень хотелось знать чуть больше о Вас.');

		if ($person = loadPerson($login, true)) {
			if ($person->getAttribute('email') != $email && 
				$person->getAttribute('password') != md5($password)) {
				warn('Некто, с указанным Вами логином, уже зарегистрирован. Если это были Вы, '.
					'пожалуйста, укажите пароль, который Вы использовали первый раз, и продолжите регистрацию.');
			}
		}

		// 1. create person's file in sandbox
		$dom = createPerson($login, $password, $name, $email, $info);
		$dom->save("persons/sandbox/$login.xml");

		// 2. send confirmation e-mail 
		@mail ($email, 
			"=?Windows-1251?b?".base64_encode("Подтверждение регистрации на сайте \"".
				iconv('UTF-8', 'windows-1251', title)."\"")."?=", 
			chunk_split(base64_encode($msg=
			"Здравствуйте, ".iconv('UTF-8', 'windows-1251', $name)."!\n\n".
			"Большое спасибо за регистрацию на сайте \"".
				iconv('UTF-8', 'windows-1251', title)."\"!\n".
			"Чтобы активировать Вашу учетную запись, пожалуйста, перейдите \n".
			"по следующей ссылке: \n".
			"http://{$_SERVER['SERVER_NAME']}".www."check/$login-".md5(md5($password))."/\n\n".
			"Если Вы не просили присылать Вам это письмо, прошу извинить меня, ".
			"что оно к Вам пришло.\nВероятно, кто-то использовал Ваш e-mail на ".
			"сайте http://{$_SERVER['SERVER_NAME']}".www."\n".
			"Если это повторится, пожалуйста, свяжитесь со мной.\n\n".
			"С уважением,\n".
			"Служба поддержки сайта\n")), 
			"From: [WikiCrowd] <".supportEmail.">\n".
//			"BCC: Stas Davydov <$SUPPORT_EMAIL>\n".
			"Content-Type: text/plain;\r\n\tcharset=windows-1251\n".
			"Content-Transfer-Encoding: base64\n");

?><registered/><?


	
	} else if ($do == "chapterchanges") {

		if(! array_key_exists('last', $_REQUEST))
			internal('Дата последнего запроса не указана');
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
			internal('Имя не указано');
		$name = stripslashes(trim($_REQUEST['name']));
		if ($name == "")
			warn('Нам бы очень хотелось знать, как Вас зовут. Укажите, пожалуйста, Ваше имя.');

		if (!array_key_exists('email', $_REQUEST))
			internal('E-mail не указан');
		$email = trim($_REQUEST['email']);
		if ($email == "")
			warn(
				'E-mail нужен, чтобы получить письмом пароль, если вдруг Вы его забыли. '.
				'А еще нам очень хочется иметь возможность связаться с Вами, если что (никакого СПАМа!). '.
				'Укажите, пожалуйста, Ваш e-mail.');
		else if (! preg_match('/[\w\d._+]+@[\w\d.-]+\.[a-z]{2,4}$/i', $email)) 
			warn('В Вашем e-mail ошибка, проверьте, пожалуйста. Хочется чего-то вроде email@server.com');

		if (!array_key_exists('info', $_REQUEST))
			internal('Информация не указана');
		$info = stripslashes(trim($_REQUEST['info']));
		if ($info == "")
			warn('Нам бы очень хотелось знать чуть больше о Вас.');

		if (!array_key_exists('password', $_REQUEST))
			internal('Пароль не указан');
		$password = stripslashes(trim($_REQUEST['password']));
		if (!array_key_exists('oldpassword', $_REQUEST))
			internal('Старый пароль не указан');
		$oldpassword = stripslashes(trim($_REQUEST['oldpassword']));
		if ($password != "" && $oldpassword == "")
			warn('Если Вы хотите задать новый пароль, пожалуйста, укажите Ваш старый пароль. '.
				'Это нужно для дополнительной проверки, что Вы - это Вы.');
		else if ($password != "" && md5($oldpassword) != $person->getAttribute('password'))
			warn('Ваш неправильно указали Ваш старый пароль.');
		else if ($password != "")
			$person->setAttribute('password', md5($password));

		$notify = array_key_exists('notify', $_REQUEST) && $_REQUEST['notify'] == "true" 
			? "true" : "false";

		$sandbox = $person->getAttribute('email') != $email;
		$person->setAttribute('name', $name);
		$person->setAttribute('email', $email);
		$infoNode = $person->getElementsByTagName('info')->item(0);
		$person->setAttribute('notify', $notify);
		while($infoNode->firstChild) $infoNode->removeChild($infoNode->firstChild);
		$infoNode->appendChild($infoNode->ownerDocument->createTextNode($info));
		$personFileName = 'persons/'.$person->getAttribute('uid').'.xml';
		$person->ownerDocument->save($personFileName);
		if($sandbox) {
			rename($personFileName, 'persons/sandbox/'.$person->getAttribute('uid').'.xml');
			// send confirmation e-mail 
			@mail ($email, 
				"=?Windows-1251?b?".base64_encode("Подтверждение изменения e-mail на сайте \"".
					iconv('UTF-8', 'windows-1251', title)."\"")."?=", 
				chunk_split(base64_encode($msg=
				"Здравствуйте, ".iconv('UTF-8', 'windows-1251', $name)."!\n\n".
				"Вы изменили Ваш e-mail адрес на сайте \"".
					iconv('UTF-8', 'windows-1251', title)."\".\n".
				"Чтобы активировать Вашу учетную запись, пожалуйста, перейдите \n".
				"по следующей ссылке: \n".
				"http://{$_SERVER['SERVER_NAME']}".www."check/$login-".md5(md5($password))."/\n\n".
				"Если Вы не просили присылать Вам это письмо, прошу извинить меня, ".
				"что оно к Вам пришло.\nВероятно, кто-то использовал Ваш e-mail на ".
				"сайте http://{$_SERVER['SERVER_NAME']}".www."\n".
				"Если это повторится, пожалуйста, свяжитесь с нами.\n\n".
				"С уважением,\n".
				"Служба поддержки сайта")), 
				"From: [WikiCrowd] <".supportEmail.">\n".
//				"BCC: Stas Davydov <$SUPPORT_EMAIL>\n".
				"Content-Type: text/plain;\r\n\tcharset=windows-1251\n".
				"Content-Transfer-Encoding: base64\n");
		}

?><saved/><?



	} else if ($do == "forget") {

		if (!array_key_exists('email', $_REQUEST))
			internal('E-mail не указан');
		$email = trim($_REQUEST['email']);
		if ($email == "")
			warn(
				'Если Вы не укажете Ваш e-mail, мы не сможем прислать Вам пароль.');
		else if (! preg_match('/[\w\d._+]+@[\w\d.-]+\.[a-z]{2,4}$/i', $email)) 
			warn('В Вашем e-mail ошибка, проверьте, пожалуйста. Хочется чего-то вроде email@server.com');
		
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
				"=?Windows-1251?b?".base64_encode("Новый пароль на сайте \"".
					iconv('UTF-8', 'windows-1251', title)."\"")."?=", 
				chunk_split(base64_encode($msg=
				"Здравствуйте, ".iconv('UTF-8', 'Windows-1251', $name)."!\n\n".
				"Вы попросили прислать Вам новый пароль на сайте \"".
					iconv('UTF-8', 'windows-1251', title)."\".\n\n".
				"Ваш логин: ".$foundPerson->getAttribute('uid')."\n".
				"Ваш новый пароль: $newPassword\n\n".
				"Введите их на странице входа на сайт: \n".
				"http://{$_SERVER['SERVER_NAME']}".www."auth/\n\n".
				"Вы можете изменить Ваш новый пароль, на странице с Вашей учетной записью:\n".
				"http://{$_SERVER['SERVER_NAME']}".www."person/".$foundPerson->getAttribute('uid')."\n\n".
				"[сюда инструкции!]\n\n".

				"Если Вы не просили присылать Вам это письмо, прошу извинить меня, ".
				"что оно к Вам пришло.\nВероятно, кто-то использовал Ваш e-mail на ".
				"сайте http://{$_SERVER['SERVER_NAME']}".www."\n".
				"Если это повторится, пожалуйста, свяжитесь со мной.\n\n".
				"С уважением,\n".
				"Служба поддержки сайта")), 
				"From: [WikiCrowd] <supportEmail>\n".
//				"BCC: Stas Davydov <$SUPPORT_EMAIL>\n".
				"Content-Type: text/plain;\r\n\tcharset=windows-1251\n".
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
				warn('Ваша учетная запись еще не активирована.');
			} else {
				warn('Не найдено ни одной учетной записи с указанным Вами e-mail.');
			}
		}


	}

	ob_end_flush();
?>