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
//	var notify = $('notify');

	if (checkEmptyField(name, myNotice, Locale.NameIsRequired)) return false;
	if (checkEmptyField(email, myNotice, Locale.EmailCannotBeEmpty)) return false;
	else if (! checkEmail(email.value)) {
		showError(regNotice, Locale.EmailIsWrong);
		email.focus();
		return false;
	}

	if (! password.value.match(/^\s*$/) && oldPassword.value.match(/^\s*$/)) {
		showError(myNotice, Locale.NewPasswordOldPassword);
		return false;
	}

	showProgress(myNotice, Locale.Saving);

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
//			"&notify=" + (notify.checked ? "true" : "false") +
			"&oldpassword=" + encodeURIComponent(oldPassword.value) +
			"&password=" + encodeURIComponent(password.value));

	} catch (e) {
		showError(myNotice, Locale.SomethingWithNetwork + ' (' + e + ')'); 
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
				showError(myNotice, Locale.SomethingHappen + ' ' + Locale.SupportIsNotified + ' (' + 
					warn[0].firstChild.nodeValue);
			} else if (saved.length > 0) {
				showNotice(myNotice, Locale.ChangesSaved);
//				location.href = location.href + '?ts=' + new Date().getTime();
			} else {
			 	showError(myNotice, Locale.SomethingHappen + ' ' +
			 		Locale.SomethingWithServer + ' RAW output: ' + xmlHttp_savePerson.responseText);
			}
		} else if (xmlHttp_savePerson && xmlHttp_savePerson.readyState == 4 && xmlHttp_savePerson.status != 200) {
	 		showError(myNotice, Locale.SomethingHappen + ' ' +
	 			Locale.SomethingWithServer + ' Code: ' + xmlHttp_savePerson.status);
		}
	} catch(e) {
		showError(myNotice, Locale.SomethingWithNetwork + ' (' + e + ')'); 
	}
}

