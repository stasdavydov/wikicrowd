var xmlHttp_savePerson;
var myNotice;
function savePerson() {
	myNotice = $('regnotice');

	var name = $('name');
	var email = $('email');
	var originalemail = $('originalemail');
	var info = $('info');
	var oldPassword = $('regoldpassword');
	var password = $('regpassword');
	var notify = $('notify');

	if (checkEmptyField(name, myNotice, 
		'Нам бы очень хотелось знать, как Вас зовут. Укажите, пожалуйста, Ваше имя.')) return false;
	if (checkEmptyField(email, myNotice, 
		'E-mail нужен, чтобы получить письмом пароль, если вдруг Вы его забыли. ' +
		'А еще нам очень хочется иметь возможность связаться с Вами, если что (никакого СПАМа!). ' +
		'Укажите, пожалуйста, Ваш e-mail.')) return false;
	else if (! checkEmail(email.value)) {
		showError(regNotice, 
			'В Вашем e-mail ошибка, проверьте, пожалуйста. Хочется чего-то вроде email@server.com');
		email.focus();
		return false;
	}
	if (checkEmptyField(info, myNotice, 'Нам бы очень хотелось знать чуть больше о Вас.')) return false;
	if (! password.value.match(/^\s*$/) && oldPassword.value.match(/^\s*$/)) {
		showError(myNotice, 'Если Вы хотите задать новый пароль, пожалуйста, укажите Ваш старый пароль. ' +
			'Это нужно для дополнительной проверки, что Вы - это Вы.');
		return false;
	}

	if (originalemail.value != email.value &&
		! confirm('Вы изменили Ваш e-mail. После сохранения, Вам на него будет отправлено ' +
			'письмо с просьбой о подтверждении нового адреса. До тех пор, пока он не будет ' +
			'подтвержден, повторный вход на сайт будет невозможен. Если Вы не уверены, что ' +
			'указали Ваш новый e-mail правильно, прервите процесс сохранения и проверьте адрес ' +
			'еще раз. Продолжить процесс сохранения?'))
		return false;

	showProgress(myNotice, 'Сохраняю...');

	if (window.ActiveXObject) xmlHttp_savePerson = new ActiveXObject("Microsoft.XMLHTTP");
	else if (window.XMLHttpRequest) xmlHttp_savePerson = new XMLHttpRequest();
	xmlHttp_savePerson.onreadystatechange = handleSavePerson;
	xmlHttp_savePerson.open("POST", 
		www+"ajax.php?do=saveperson" +
		"&ts=" + new Date().getTime(), true);
	xmlHttp_savePerson.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	try {
		xmlHttp_savePerson.send(			
			"&name=" + encodeURIComponent(name.value) +
			"&email=" + encodeURIComponent(email.value) +
			"&info=" + encodeURIComponent(info.value) +
			"&notify=" + (notify.checked ? "true" : "false") +
			"&oldpassword=" + encodeURIComponent(oldPassword.value) +
			"&password=" + encodeURIComponent(password.value));

	} catch (e) {
		showError(myNotice, 'Вероятно что-то с сетью. Попробуйте повторить чуть позже. (' + e + ')'); 
	}

	return false;
}
function handleSavePerson() {
	try{
		if(xmlHttp_savePerson && xmlHttp_savePerson.readyState == 4 && xmlHttp_savePerson.status == 200) {
			var xml = xmlHttp_savePerson.responseXML;
			var warn = xml.getElementsByTagName('warn');
			var saved = xml.getElementsByTagName('saved');
			if (warn.length > 0) {
				showError(myNotice, 'Возникли сложности. ' +
					'Наша служба поддержки уже получила уведомление и постарается исправить проблему ' +
					'как можно скорее. (' + warn[0].firstChild.nodeValue);
			} else if (saved.length > 0) {
				showNotice(myNotice, 'Изменения сохранены.');
//				location.href = location.href + '?ts=' + new Date().getTime();
			} else {
			 	showError(myNotice, 'Возникли сложности. ' +
			 		'Какие-то проблемы на сервере. RAW output: ' + xmlHttp_savePerson.responseText);
			}
		} else if (xmlHttp_savePerson && xmlHttp_savePerson.readyState == 4 && xmlHttp_savePerson.status != 200) {
	 		showError(myNotice, 'Возникли сложности. ' +
	 			'Какие-то проблемы на сервере. Код возврата: ' + xmlHttp_savePerson.status);
		}
	} catch(e) {
		showError(myNotice, 'Вероятно что-то с сетью. Попробуйте повторить чуть позже. (' + e + ')'); 
	}
}

