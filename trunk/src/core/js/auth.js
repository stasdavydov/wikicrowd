var authNotice, regNotice, forgetNotice;
var xmlHttp_auth;
function auth() {
	authNotice = $('authnotice');

	var login = $('login');
	var password = $('password');
	var remember = $('remember');

	if (login.value.match(/^\s*$/)) {
		showError(authNotice, 'Логин не может быть пустым.');
		login.focus();
		return false;
	} else if (! login.value.match(/^[a-zA-Z0-9]+$/)) {
		showError(authNotice, 'В логине могут быть только латинские буквы (a-z, A-Z) и арабские цифры (0-9).');
		login.focus();
		return false;
	}

	if (password.value.match(/^\s*$/)) {
		showError(authNotice, 'Пароль не может быть пустым.');
		password.focus();
		return false;
	}

	showProgress(authNotice, 'Идет проверка...');

	if (window.ActiveXObject) xmlHttp_auth = new ActiveXObject("Microsoft.XMLHTTP");
	else if (window.XMLHttpRequest) xmlHttp_auth = new XMLHttpRequest();
	xmlHttp_auth.onreadystatechange = handleAuth;
	xmlHttp_auth.open("GET", 
		www + "ajax.php?do=auth" +
		"&login=" + encodeURIComponent(login.value) +
		"&password=" + encodeURIComponent(password.value) + 
		"&remember=" + (remember.checked ? 1 : 0) +
		"&ts=" + new Date().getTime(), true);
	try {
		xmlHttp_auth.send(null);
	} catch (e) {
		showError(authNotice, 
			'Вероятно что-то с сетью. Попробуйте повторить чуть позже. (' + e + ')'); 
	}
	return false;
}
function handleAuth() {
	try {
		if(xmlHttp_auth && xmlHttp_auth.readyState == 4 && xmlHttp_auth.status == 200) {
			var xml = xmlHttp_auth.responseXML;
			var logged = xml.getElementsByTagName('logged');
			var warn = xml.getElementsByTagName('warn');
			if (logged.length == 1) {
				showNotice(authNotice, 'Проверка прошла успешно! Подождите, пожалуйста, несколько секунд, пока страница загрузится.');
				location.href = www + '?ts=' + new Date().getTime();
			} else if (warn.length > 0) {
				showError(authNotice, warn[0].firstChild.nodeValue);
			} else {
				showError(authNotice, 'Какие-то проблемы на сервере. RAW output: ' + xmlHttp_auth.responseText);
			}
		} else if (xmlHttp_auth && xmlHttp_auth.readyState == 4 && xmlHttp_auth.status != 200) {
		 	showError(authNotice, 'Какие-то проблемы на сервере. Код возврата: ' + xmlHttp_auth.status);
		}
	} catch(e) {
		showError(authNotice, 
			'Вероятно что-то с сетью. Попробуйте повторить чуть позже. (' + e + ')'); 
	}
}

var xmlHttp_reg;
function register() {
	regNotice = $('regnotice');

	var login = $('reglogin');
	var password = $('regpassword');
	var name = $('name');
	var email = $('email');
	var info = $('info');

	if (checkEmptyField(login, regNotice, 'Логин не может быть пустым')) return false;
	else if (! login.value.match(/^(\w|\d)+$/)) {
		showError(regNotice, 
			'Логин может содержать только латинские буквы (a-z, A-Z) или арабские цифры (0-9).');
		login.select();
		login.focus();
		return false;
	}
	if (checkEmptyField(password, regNotice, 
		'Пароль не может быть пустым. Если не задать пароль, кто угодно сможет выдать себя за вас.')) return false;
	if (checkEmptyField(name, regNotice, 
		'Нам бы очень хотелось знать, как Вас зовут. Укажите, пожалуйста, Ваше имя.')) return false;
	if (checkEmptyField(email, regNotice, 
		'E-mail нужен для того, чтобы точно убедиться, что Вы - это Вы. ' +
		'Еще он нужен, чтобы получить письмом пароль, если вдруг Вы его забыли. ' +
		'А еще нам очень хочется иметь возможность связаться с Вами, если что (никакого СПАМа!). ' +
		'Укажите, пожалуйста, Ваш e-mail.')) return false;
	else if (! checkEmail(email.value)) {
		showError(regNotice, 
			'В Вашем e-mail ошибка, проверьте, пожалуйста. Хочется чего-то вроде email@server.com');
		email.focus();
		return false;
	}
	if (checkEmptyField(info, regNotice, 'Нам бы очень хотелось знать чуть больше о Вас.')) return false;

	showProgress(regNotice, 'Идет регистрация...');

	if (window.ActiveXObject) xmlHttp_reg = new ActiveXObject("Microsoft.XMLHTTP");
	else if (window.XMLHttpRequest) xmlHttp_reg = new XMLHttpRequest();
	xmlHttp_reg.onreadystatechange = handleRegister;
	xmlHttp_reg.open("POST", 
		www + "ajax.php?do=register" +
		"&ts=" + new Date().getTime(), true);
	xmlHttp_reg.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	try {
		xmlHttp_reg.send(
			"login=" + encodeURIComponent(login.value) +
			"&password=" + encodeURIComponent(password.value) + 
			"&name=" + encodeURIComponent(name.value) +
			"&email=" + encodeURIComponent(email.value) +
			"&info=" + encodeURIComponent(info.value));
	} catch (e) {
		showError(regNotice, 
			'Вероятно что-то с сетью. Попробуйте повторить чуть позже. (' + e + ')'); 
	}
	return false;
}
function handleRegister() {
	try {
		if(xmlHttp_reg && xmlHttp_reg.readyState == 4 && xmlHttp_reg.status == 200) {
			var xml = xmlHttp_reg.responseXML;
			var registered = xml.getElementsByTagName('registered');
			var warn = xml.getElementsByTagName('warn');
			if (registered.length == 1) {
				showNotice(regNotice, 
					'Регистрация прошла успешно! На указанный Вами e-mail было отправлено письмо ' +
					'со ссылкой, перейдя по которой, Вы активируете Вашу учетную запись.');
			} else if (warn.length > 0) {
				showError(regNotice, warn[0].firstChild.nodeValue);
			} else {
				showError(regNotice, 'Какие-то проблемы на сервере. RAW output: ' + 
					xmlHttp_reg.responseText);
			}
		} else if (xmlHttp_reg && xmlHttp_reg.readyState == 4 && xmlHttp_reg.status != 200) {
	 		showError(regNotice, 'Какие-то проблемы на сервере. Код возврата: ' + xmlHttp_reg.status);
		}
	} catch(e) {
		showError(regNotice, 
			'Вероятно что-то с сетью. Попробуйте повторить чуть позже. (' + e + ')'); 
	}
}

var foretNotice;
var xmlHttml_forget;
function forget() {
	foretNotice = $('forgetnotice');
	var email = $('forgetemail');

	if (checkEmptyField(email, foretNotice, 
		'Если Вы не укажете Ваш e-mail, мы не сможем прислать Вам пароль.')) return false;
	else if (! checkEmail(email.value)) {
		showError(foretNotice, 
			'В Вашем e-mail ошибка, проверьте, пожалуйста. Хочется чего-то вроде email@server.com');
		email.focus();
		return false;
	}

	if (window.ActiveXObject) xmlHttml_forget = new ActiveXObject("Microsoft.XMLHTTP");
	else if (window.XMLHttpRequest) xmlHttml_forget = new XMLHttpRequest();
	xmlHttml_forget.onreadystatechange = handleForget;
	xmlHttml_forget.open("GET", 
		www + "ajax.php?do=forget" +
		"&email=" + email.value +
		"&ts=" + new Date().getTime(), true);
	try {
		showProgress(foretNotice, 'Отправляем...');
		xmlHttml_forget.send(null);
	} catch (e) {
		showError(foretNotice, 
			'Вероятно что-то с сетью. Попробуйте повторить чуть позже. (' + e + ')'); 
	}
	return false;
}
function handleForget() {
	try {
		if(xmlHttml_forget && xmlHttml_forget.readyState == 4 && xmlHttml_forget.status == 200) {
			var xml = xmlHttml_forget.responseXML;
			var sent = xml.getElementsByTagName('sent');
			var warn = xml.getElementsByTagName('warn');
			if (sent.length == 1) {
				showNotice(foretNotice, 
					'Мы отправили Вам письмо с новым паролем.');
			} else if (warn.length > 0) {
				showError(foretNotice, warn[0].firstChild.nodeValue);
			} else {
				showError(foretNotice, 'Какие-то проблемы на сервере. RAW output: ' + 
					xmlHttml_forget.responseText);
			}
		} else if (xmlHttml_forget && xmlHttml_forget.readyState == 4 && xmlHttml_forget.status != 200) {
	 		showError(foretNotice, 'Какие-то проблемы на сервере. Код возврата: ' + xmlHttml_forget.status);
		}
	} catch(e) {
		showError(foretNotice, 
			'Вероятно что-то с сетью. Попробуйте повторить чуть позже. (' + e + ')'); 
	}
}

var xmlHttp_logout;
function logout() {
	if (window.ActiveXObject) xmlHttp_logout = new ActiveXObject("Microsoft.XMLHTTP");
	else if (window.XMLHttpRequest) xmlHttp_logout = new XMLHttpRequest();
	xmlHttp_logout.onreadystatechange = handleLogout;
	xmlHttp_logout.open("GET", 
		www+"ajax.php?do=logout" +
		"&ts=" + new Date().getTime(), true);
	try {
		xmlHttp_logout.send(null);
	} catch (e) {
		alert('Вероятно что-то с сетью. Попробуйте повторить чуть позже. (' + e + ')'); 
	}
}
function handleLogout() {
	try{
		if(xmlHttp_logout && xmlHttp_logout.readyState == 4 && xmlHttp_logout.status == 200) {
			var xml = xmlHttp_logout.responseXML;
			var warn = xml.getElementsByTagName('warn');
			var loggedout = xml.getElementsByTagName('loggedout');
			if (warn.length > 0) {
				alert('Возникли сложности. ' +
					'Наша служба поддержки уже получила уведомление и постарается исправить проблему ' +
					'как можно скорее. (' + warn[0].firstChild.nodeValue);
			} else if (loggedout.length > 0) {
				location.href = www + '?ts=' + new Date().getTime();
			} else {
			 	alert('Возникли сложности. ' +
			 		'Какие-то проблемы на сервере. RAW output: ' + xmlHttp_logout.responseText);
			}
		} else if (xmlHttp_logout && xmlHttp_logout.readyState == 4 && xmlHttp_logout.status != 200) {
	 		alert('Возникли сложности. ' +
	 			'Какие-то проблемы на сервере. Код возврата: ' + xmlHttp_logout.status);
		}
	} catch(e) {
		alert('Вероятно что-то с сетью. Попробуйте повторить чуть позже. (' + e + ')'); 
	}
}

