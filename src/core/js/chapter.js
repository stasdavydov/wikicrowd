function createHttpRequest() {
	if (window.ActiveXObject) return new ActiveXObject("Microsoft.XMLHTTP");
	else if (window.XMLHttpRequest) return new XMLHttpRequest();
	else
		alert('There is no XMLHttpRequest handler');
}

var BlockController = {
	plugins: {},
	registerPlugin: function (type, plugin) { this.plugins[type] = plugin; },
	plugin: function(type) { return this.plugins[type];	},

	initBlockEvents: function(el) {
		el.onmouseover = function() { editMouseOver(el.parentNode); };
		el.onmouseout = function() { editMouseOut(el.parentNode); };
		el.onclick =  function() { edit(el.parentNode); }
	},
	getContainerParams: function(container) {
		// id__type__rev
		var params = container.getAttribute('id').split(/__/);
		return {id: params[0], type: params[1], rev: params[2]};
	},
	containers: {},
	getContainerById: function(id) {
		return this.containers[id];
	},
	init: function() {
		var oldOnload;
		if (window.onload)
			oldOnload = window.onload;

		window.onload = function() {
			var divs = document.getElementsByTagName('div');
			for(var i = 0; i<divs.length; i++) {
				var div = divs[i];

				if (getCl(div).indexOf('part') != -1) {
					var params = BlockController.getContainerParams(div);
					BlockController.containers[params.id] = div;

					var block = div.firstChild;
					block.setAttribute('id', 'block' + params.id);
					BlockController.initBlockEvents(block);

					var control = createElement('div', 
						{id: 'control' + params.id, className: 'control'},	
						[document.createTextNode(Locale.Change)]);
					BlockController.initBlockEvents(control);
					div.insertBefore(control, div.firstChild);
				}
			}

			if (oldOnload)
				oldOnload();
		}
	},
	switchToEditMode: function(id, editMode) {
		setCl(this.getContainerById(id), editMode ? 'part hold' : 'part');
	},
	createRawBlock: function(rawNode) {
		var div = createElement('div');
		div.innerHTML = rawNode.firstChild.nodeValue;
		var block = div.firstChild;
		block.setAttribute('id', 'block' + rawNode.getAttribute('id'));
		this.initBlockEvents(block);
		return block;
	},
	createContainer: function(rawNode) {
		var id = rawNode.getAttribute('id');

    	var container = createElement('div', {className: 'part fade', 
    		id: id + '__' + rawNode.getAttribute('type') + '__' + rawNode.getAttribute('rev')},
    		[BlockController.createRawBlock(rawNode)]);

		var control = createElement('div', 
			{id: 'control' + id, className: 'control'},	
			[document.createTextNode(Locale.Change)]);
		BlockController.initBlockEvents(control);
		container.insertBefore(control, container.firstChild);

		this.containers[id] = container;

		return container;
	},
	updateContainer: function(rawNode) {
		var id = rawNode.getAttribute('id');
		var container = this.getContainerById(id);

		container.removeChild($('block' + id));
		container.appendChild(BlockController.createRawBlock(rawNode));
		container.setAttribute('id', 
			id + '__' + rawNode.getAttribute('type') + '__' + rawNode.getAttribute('rev'));

		if ($('changes' + id)) {
			loadChanges(id);
		} else if (rawNode.getAttribute('rev') > 0 && ! $('loadchanges' + id)) {
			createChangesSign(id);
		}

//		lastCheck = rawNode.getAttribute('created-ts') * 1000;
	}

};

var editMouseOver = function(div) { 
	if (getCl(div).indexOf('hold') == -1) {
		setCl(div, 'part over'); 
	}
};
var editMouseOut = function(div) { 
	if (getCl(div).indexOf('hold') == -1) {
		setCl(div, 'part'); 
	}
};

var editIsOff = false;

var edit = function(div) { 
	if (editIsOff) {
		editIsOff = false;
	} else {
		var params = BlockController.getContainerParams(div);
		if (getCl(div).indexOf('hold') == -1) 
			BlockController.plugin(params.type).edit(params.id, params.rev); 
	}
};

var editOff = function() {
    editIsOff = true;
};

var Plugin = function(type) {
	var plugin = {
	    appendEditForm: function(form, ajax) {},
	    onEdit: function(id) {},
	    getFormData: function() { return ''; }
	};
	BlockController.registerPlugin(type, plugin);
	return plugin;
};

var cancelEditing = function(id) {
	if ($('form' + id)) {
		$('form' + id).style.display = 'none';
	}
	$('block' + id).style.display = 'block';
	BlockController.switchToEditMode(id, false);
};

var approximateTextareaRows = function(textarea) {
    var rows = 0;
    var pos = 0;
    var cols = Math.ceil(textarea.getAttribute('cols') / 0.75);

    if (textarea.value.length > 1000)
        rows = 20;
    else {
        while (pos != -1) {
            var nextPos = textarea.value.indexOf('\n', pos + 1);
            if (nextPos == -1) {
                rows += Math.ceil((textarea.value.length - pos) / cols);
                break;
            } else if (nextPos - pos < 60)
                rows += 1;
            else
                rows += (1 + Math.ceil((nextPos - pos) / cols));
            pos = nextPos;
        }
    }
    if (rows <= 1)
        rows = 2;

    if (rows != textarea.getAttribute('rows')) {
        textarea.setAttribute('rows', rows);
    }
};

var BlockPlugin = function(type) {
	return Object.extend(new Plugin(type), {
		authError: false,

		edit: function(id, rev) {
			var container = BlockController.getContainerById(id);
//			setCl($('container' + id), 'part hold');

			if ($('form' + id)) {
				$('form' + id).style.display = 'block';
				$('block' + id).style.display = 'none';

				BlockController.switchToEditMode(id, true);
				this.onEdit(id);
			} else {
				// create form 
				var form = createElement('form', 
					{id: 'form' + id, method: 'get', action: ''},
					[createElement('input', {type: 'hidden', id: 'rev' + id, value: rev}),
					 createElement('div', {id: 'formloader' + id})]);
				var plugin = this;
				form.onsubmit = function() {
					plugin.save(id, rev);
					return false;
				};
				form.onkeydown = function(event) { 
					if (event == undefined || event == null) 
						event = window.event; 
					if(event.keyCode == 27) {
						cancelEditing(id); 
					}
				};
				container.insertBefore(form, $('block' + id));

				// append plugin dependent form content
				showProgress($('formloader' + id), Locale.EditFormLoading);

				new Ajax(www + 'ajax.php?do=edit&id=' + id + '&rev=' + rev + '&ts=' + new Date().getTime(), {
					method: 'GET', successful: function(ajax) {
						var id = ajax.params.id;

						$('block' + id).style.display = 'none';

						ajax.params.plugin.appendEditForm(form, ajax);
						appendChildren(form, 
							[createElement('br'), 
					 		createElement('div', {id: 'error' + id, className: 'error'}), 
					 		createElement('input', {id: 'submit' + id, type: 'submit', value: Locale.Save}), 
					 		createElement('input', {id: 'cancel' + id, type: 'reset', value: Locale.Cancel})]);
						$('cancel' + id).onclick = function () {
							cancelEditing(id);
							ajax.params.plugin.authError = false;
							return false;
						};
						$('formloader' + id).parentNode.removeChild($('formloader' + id));
						
						BlockController.switchToEditMode(id, true);
						ajax.params.plugin.onEdit(id);

					}, failed: this.crashed, 
					id: id, plugin: this}).request();
			}
		},

		save: function(id, rev) {
			var postData = 
				"id=" + id + "&rev=" + rev + 
				($('overwrite') != null ? '&overwrite=' : '') + 
				(this.authError 
					? 	'&login=' + encodeURIComponent($('login').value) +
						'&password=' + encodeURIComponent($('password').value)
					: '');
			var formData = this.getFormData(id);
			for(var name in formData)
				postData += '&' + name + '=' + formData[name];

			showProgress($('error' + id), Locale.Saving);
			new Ajax(www+"ajax.php?do=save&ts=" + new Date().getTime(), {
				method: 'POST', postData: postData, 
				successful: this.saved, failed: this.saveFailed, crashed: this.crashed,
				id: id, plugin: this}).request();
		},
		saved: function(ajax) {
			var xml = ajax.responseXML();
			var warn = xml.getElementsByTagName('warn');
			var conflict = xml.getElementsByTagName('conflict');
			var saved = xml.getElementsByTagName('updated');
			var inserted = xml.getElementsByTagName('inserted');
			var auth = xml.getElementsByTagName('auth');
			var renamed = xml.getElementsByTagName('chapterrenamed');
	
			ajax.params.plugin.authError = false;

			var id = ajax.params.id;
			
			if (warn.length > 0) {
				showError($('error' + id), warn[0].firstChild.nodeValue);
			} else if (conflict.length > 0) {
				conflict = conflict[0];
				showErrorExt($('error' + id), 
					Locale.getMessage('VersionConflict'), 
					Locale.getMessage('VersionConflictBody', 
						getTextTimeDifference(conflict.getAttribute('created-ts')),
                        www + 'person/' + conflict.getAttribute('author'),
                        conflict.getAttribute('author')) + ': <br/><br/>' +
					'<div id="conflict"></div>');
				var div = $('error' + id).getElementsByTagName('div');
				div[div.length-1].innerHTML = conflict.firstChild.nodeValue;

				$('submit' + id).value = Locale.Overwrite;
				$('form' + id).insertBefore(
					createElement('input', {type: 'hidden', id: 'overwrite'}), 
					$('form' + id).firstChild);
			} else if (auth.length > 0) {
				auth = auth[0];
				showErrorExt($('error' + id), 
					Locale.SessionExpired + ' ', 
					(auth.firstChild ? auth.firstChild.nodeValue + '<br/>': '') + 
					Locale.SpecifyLoginAndPassword + '<br/>' +
					'<label for="login">' + Locale.LoginAsLogin + ': <input type="text" id="login" value="' + 
					auth.getAttribute('login') + '"/></label> ' +
					'<label for="password">' + Locale.Password + ': <input type="password" id="password" value="' +
					auth.getAttribute('password') + '"/></label><br/>' +
					Locale.getMessage('IfYouForgetShort', www + 'auth/'));
				ajax.params.plugin.authError = true;
			} else if (saved.length > 0 || inserted.length > 0){
				var container = BlockController.getContainerById(id);
				container.removeChild($('form' + id));

				if (saved.length > 0) {
					BlockController.updateContainer(saved[0]);

					BlockController.switchToEditMode(id, false);
					setCl(container, 'fade part');
				} else {
					cancelEditing(id);
				}

				if (inserted.length > 0) {
					var insertBefore = $('block' + id).parentNode.nextSibling;
					var parentNode = $('block' + id).parentNode.parentNode;

					for(var i = 0; i < inserted.length; i++) {
    					var container = BlockController.createContainer(inserted.item(i));

    					if (insertBefore)
							parentNode.insertBefore(container, insertBefore);
						else
							parentNode.appendChild(container);
    				}
    			}
		
				FadeEffect.start();
			} else if (renamed.length > 0) {
				var change = renamed.item(0);
				location.href = www + change.getAttribute('newtitle');
			} else {
				showError($('error' + id), Locale.SomethingWithServer + ' RAW output: ' + 
					ajax.responseText());
			}

		},
		saveFailed: function(ajax) {
			showError($('error' + ajax.params.id), Locale.getMessage('CannotSaveChanges', ajax.status()));
		},

		crashed: function(ajax, e) {
			alert(Locale.SomethingWithNetwork + ' (' + e + ')');
		}
	});
};

var TextBlockPlugin = function(type) {
	return Object.extend(new BlockPlugin(type), {
		rows: 10,

		appendEditForm: function(form, ajax) {
			var id = ajax.params.id;

			var text = ajax.responseXML().getElementsByTagName('text')[0].firstChild;
			var text = text ? text.nodeValue : '';
			var input;

			appendChildren(form, 
				[input = createElement('textarea', 
					{id: 'text' + id + 'input', cols: 60, rows: this.rows},
					[document.createTextNode(text)])]);
			approximateTextareaRows(input);
			input.onkeypress = function() {
				approximateTextareaRows(input);
			};
		},
		onEdit: function(id){
			$('text' + id + 'input').focus();
		},
		getFormData: function(id) {
			return { text: encodeURIComponent($('text' + id + 'input').value) };
		}
	});
};

function loadChanges (id) {
	if (! $('loadchanges' + id)) {
		createChangesSign(id);
	}

	$('loadchanges' + id).removeChild($('loadchanges' + id).firstChild);
	$('loadchanges' + id).appendChild(
		createElement('img', {src: www + 'core/img/star.gif', alt: '*'}));

	if ($('changes' + id))
		$('changes' + id).parentNode.removeChild($('changes' + id));

	new Ajax(www+"ajax.php?do=loadchanges&id=" + id + "&ts=" + new Date().getTime(), {
		successful: changesLoaded, 
		failed: loadChangesFailed,
		crashed: loadChangesCrashed,
		id: id}).request();
}

var maxZ = 2;

function changesLoaded(ajax) {
	var xml = ajax.responseXML();
	var warn = xml.getElementsByTagName('warn');
	if (warn.length > 0) {
		showLoadChangesError(Locale.SupportIsNotified + ' (' + warn[0].firstChild.nodeValue);
	} else {
		var id = ajax.params.id;

		$('loadchanges' + id).innerHTML = "*";
		setCl($('loadchanges' + id), 'changes serv opened');
		$('loadchanges' + id).setAttribute('title', Locale.CloseList);
		$('loadchanges' + id).setAttribute('href', 'javascript:closeChanges(\'' + id + '\')');

		var div = createElement('div');
		div.innerHTML = ajax.responseText().substr(ajax.responseText().indexOf("\n")+1);
		var ul = div.firstChild;
		$('block' + id).parentNode.appendChild(ul);

		ul.style.zIndex = maxZ++;
		$('loadchanges' + id).style.zIndex = maxZ++;

		updateTime();
	}
}

function loadChangesFailed (ajax) {
 	showLoadChangesError(Locale.SomethingWithServer + 'Code: ' + ajax.status());
}

function loadChangesCrashed(ajax, e) {
	alert(Locale.SomethingWithNetwork + ' (' + e + ')');
}

function createChangesSign(blockId) {
	$('block' + blockId).parentNode.appendChild(createElement('a', 
		{className: 'changes serv', id: 'loadchanges' + blockId, 
			href: "javascript:loadChanges('" + blockId + "')",
			title: Locale.ViewChangeList},
		[document.createTextNode("*")]));
}

function showLoadChangesError(msg) {
	$('loadchanges' + id).innerHTML = "*";
	alert(Locale.CannotGetChangeList + ' ' + msg);
}

function closeChanges(id) {
	setCl($('loadchanges' + id), 'changes serv');
	$('changes' + id).parentNode.removeChild($('changes' + id));
	$('loadchanges' + id).setAttribute('href', "javascript:loadChanges('" + id + "')");
	$('loadchanges' + id).setAttribute('title', Locale.ViewChangeList);
}

var lastCheck = parseInt(new Date().getTime() / 1000);
var chapterChangeChecker = function() {
	new Ajax(www+"ajax.php?do=chapterchanges" + "&last=" + lastCheck, 
		{successful: chapterChanged}).request();
};

var refreshPeriod = 30000;
setTimeout(chapterChangeChecker, refreshPeriod);

function chapterChanged(ajax) {
	var xml = ajax.responseXML();
	var renamed = xml.getElementsByTagName('renamed');
	var blocks = xml.getElementsByTagName('block');

	if (renamed.length > 0) {
		renamed = renamed.item(0);
		alert(Locale.getMessage('ChapterWasRenamed', 
			renamed.getAttribute('author'), renamed.getAttribute('to')));

		location.href = www + renamed.getAttribute('to');
		return;
	}

	for(var i = 0; i < blocks.length; i++) {
		var rawNode = blocks.item(i);
		var id = rawNode.getAttribute('id');

		// 1. find block by id
		var container = BlockController.getContainerById(id);
		var containerParams = container ? BlockController.getContainerParams(container) : null;

		// 2. if it's not in edit mode, update layout
		if (container && ! $('form' + id) 
			&& containerParams.rev < rawNode.getAttribute('rev')) {

			BlockController.updateContainer(rawNode);

		} else if (container == null) {
			// try to find where to insert new node
			var prevId = rawNode.getAttribute('prev-block-id');
			var nextId = rawNode.getAttribute('next-block-id');

			var container = BlockController.createContainer(rawNode);

			var el;
			if (el = BlockController.getContainerById(prevId)) {
				if (el.nextSibling)
					el.parentNode.insertBefore(container, el.nextSibling);
				else
					el.parentNode.appendChild(container);
			} else if (el = BlockController.getContainerById(nextId)) {
				el.parentNode.insertBefore(container, el);
			} else {
				// todo: alert: node is not found
				alert('Cannot find node'); 
			}

			if (rawNode.getAttribute('rev') > 0 && el)
				createChangesSign(id);
		}
	}
	lastCheck = parseInt(new Date().getTime()/1000);
	setTimeout(chapterChangeChecker, refreshPeriod);
}


var initBlockEvents = function(div) {
	block.onmouseover = function() { editMouseOver(div); };
	block.onmouseout = function() { editMouseOut(div); };
	block.onclick =  function() { edit(div); };
};

var createControl = function(id) {
	var control = createElement('div', 
		{id: 'control' + id, className: 'control'},	
		[document.createTextNode(Locale.Change)]);
	initBlockEvents(control);
	return control;
};

BlockController.init();

var help = function() {
    $('help-content').style.display =
            $('help-content').style.display == "block" ? "none" : "block";
};