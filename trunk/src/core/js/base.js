var $ = function (id) { 
	if(!document.getElementById) document.getElementById = function (id){
		return document.all[id];
	}

	return document.getElementById(id); 
};
var getCl = function (el) {
	var className = el.getAttribute('className');
	if (className && className != "")
		return className;
	else
		return el.getAttribute('class');
};
var setCl = function(el, c) { 
	el.setAttribute('class', c);
	el.setAttribute('className', c);
};

Object.extend = function(destination, source) {
	for (var property in source)
		destination[property] = source[property];
	return destination;
};

function createElement(name, attrs, children) {
	var element = document.createElement(name);
	if (attrs != undefined && attrs != null)
		for(var attr in attrs) {
			element.setAttribute(attr, attrs[attr]);
			if (attr == "className")
				element.setAttribute('class', attrs[attr]);
		}
	appendChildren(element, children);
	return element;
}

function appendChildren(element, children) {
	if (children != undefined && children != null)
		for(var child in children) {
			element.appendChild(children[child]);
		}
}

function checkEmail(email) { return email.length > 0 && email.match(/[\w\d._+]+@[\w\d.-]+\.[a-z]{2,4}$/i); }

function showMessage(container, msg, ext, isNotice, showProgress) {
	while(container.firstChild) container.removeChild(container.firstChild);

	if (ext != undefined && ext != null) {
		var info = createElement('div', {className: 'info'});
		info.innerHTML = ext;

		container.appendChild(createElement('div', 
			{className: isNotice ? 'notice' : 'error'}, 
			[document.createTextNode(msg), createElement('br'), info]));
	} else {
		if (showProgress != undefined && showProgress != null && showProgress) {
			container.appendChild(createElement('div', 
				{className: 'info'}, 
				[document.createTextNode(msg), 
					createElement('img', {src: www + 'core/img/star.gif', alt: '*'})]));
	    } else {
   			container.appendChild(document.createTextNode(msg));
	    	setCl(container, 
	    		isNotice != undefined && isNotice != null && isNotice ? 'notice' : 'error');
	    }
	}
}
function showProgress(container, msg) {
	showMessage(container, msg, null, true, true);
}
function showError(container, msg) {
	showMessage(container, msg, null, false, false);
}
function showNotice(container, msg) {
	showMessage(container, msg, null, true, false);
}
function showErrorExt(container, msg, ext) {
	showMessage(container, msg, ext, false, false);
}
function checkEmptyField(field, notice, msg) {
	if (field.value.match(/^\s*$/)) {
		showError(notice, msg);
		field.focus();
		return true;
	}
	return false;
}

var Ajax = function(url, params) {
	params = (params == undefined || params == null) 
		? {method: 'GET'}
		: params;

	var handler = {
		xmlHttp: null,
		params: params,
		async: true, 

		method: params.method ? params.method : 'GET',
		contentType: params.contentType ? params.contentType : 
			(params.method == 'POST' ? 'application/x-www-form-urlencoded' : null),
		url: url,
		postData: params.postData,
		
		successful: function (ajax) { if(params.successful) params.successful(ajax); },
		failed: function(ajax) { if(params.failed) params.failed(ajax); },
		crashed: function(ajax, e) { if(params.crashed) params.crashed(ajax, e); else dump(e); },
		
		request: function () {
			try {
				this.xmlHttp = createHttpRequest();
				this.xmlHttp.onreadystatechange = this.onreadystatechange;
				this.xmlHttp.open(this.method, this.url, this.async);
				if (this.contentType)
					this.xmlHttp.setRequestHeader("Content-Type", this.contentType);
				this.xmlHttp.send(this.postData);
			} catch(e) {
				this.crashed(this, e);
			}
		},

		responseXML: function() {
			return this.xmlHttp.responseXML;
		},

		responseText: function() {
			return this.xmlHttp.responseText;
		},

		status: function() {
			return this.xmlHttp.status;
		}
	};
	Object.extend(handler, {
		onreadystatechange: function() {
			try {
				if(handler.xmlHttp && handler.xmlHttp.readyState == 4 && handler.xmlHttp.status == 200) {
					handler.successful(handler);
				} else if (handler.xmlHttp && handler.xmlHttp.readyState == 4 && handler.xmlHttp.status != 200) {
					handler.failed(handler);
				}
			} catch(e) {
				handler.crashed(handler, e);
			}
		}});
	return handler;
};

function dump(o) {
	var msg = "";
	var columns = 3;
	var count = 1;
	for(var v in o)
		msg += v + "=" + o[v] + (count++ % columns ? "\t\t" : "\n");
	alert(msg);
}

function getTextTimeDifference(ts) {
	var months = new Array(Locale.monJan, Locale.monFeb, Locale.monMar, Locale.monApr, Locale.monMay, 
		Locale.monJun, Locale.monJul, Locale.monAug, Locale.monSep, Locale.monOct, Locale.monNov, Locale.monDec);

	var diff = new Date().getTime()/1000 - ts;
	var minutes = diff/60;
	var hours = minutes/60;
	var days = hours/24;
	if (diff <= 60) {
		return Locale.lessMinute + " " + Locale.back;
	} else if (diff > 60 && diff < 3600) {
		return Locale.getMinutesText(parseInt(minutes)) + " " + Locale.back;
	} else if (diff >= 3600 && diff < 3600 * 24)  {
		return Locale.getHoursText(parseInt(hours)) + " " + 
			Locale.getMinutesText(parseInt(diff%3600/60)) + " " + Locale.back;
	} else if (diff >= 3600 * 24 && diff < 3600 * 24 * 10) {
		return Locale.getDaysText(parseInt(days)) + " " + 
			Locale.getHoursText(parseInt(diff/3600%24)) + " " + 
			Locale.getMinutesText(parseInt(diff%3600/60)) + " " + Locale.back;
	} else {
		var date = new Date();
		date.setTime(ts * 1000);
		return date.getDate() + ' ' + 
			months[date.getMonth()] + ' ' +
			date.getFullYear() + ' ' +
			date.getHours() + ':' + date.getMinutes();
	}
}

function updateTime() {
	var spans = document.getElementsByTagName('span');
	for(var i = 0; i < spans.length; i++) {
		var span = spans.item(i);
		if (getCl(span) != null && getCl(span).indexOf('time:') != -1) {
			span.innerHTML = getTextTimeDifference(getCl(span).substr('time:'.length));
			setCl(span, '');
		}
	}
}

var oldWindowOnload = window.onload;
window.onload = function() {
	if(oldWindowOnload)
		oldWindowOnload();
	updateTime();
};
