/**
 * Ajex.FileManager
 * http://demphest.ru/ajex-filemanager
 *
 * @version
 * 1.0.3 (24 May 2010)
 *
 * @copyright
 * Copyright (C) 2009-2010 Demphest Gorphek
 *
 * @license
 * Dual licensed under the MIT and GPL licenses.
 *   http://www.opensource.org/licenses/mit-license.php
 *   http://www.gnu.org/licenses/gpl.html
 *
 * Ajex.FileManager is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Lesser General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This file is part of Ajex.FileManager.
 */

var $cfg = {
	display:	{fileName: true, fileDate: false, fileSize: false},
	view:		{list: false, thumb: true},
	menu:	{file: {}, folder: {}},
	contextmenu: true,
	cutKey: 15,
	dir: '',
	file: '',
	thumb: '',
	skin: 'dark',
	lang:	'ru',
	type:	'file',
	sort: 'name',
	returnTo: 'ckeditor',
	tmp: []
};
if ('' != (isSkin = parseUrl('skin'))) { $cfg.skin = isSkin; }
if ('' != (isType = parseUrl('type'))) { $cfg.type = isType; }
if ('' != (isReturn = parseUrl('returnTo'))) { $cfg.returnTo = isReturn; }
if ('' != (isCMenu = parseUrl('contextmenu'))) { $cfg.contextmenu = 'true' == isCMenu? true : false; }
if ('' != (isLang = parseUrl('langCode'))) { $cfg.lang = isLang; }
if ('' != (isLang = parseUrl('lang'))) { $cfg.lang = isLang; }

var $ajaxConnector = 'ajax/php/ajax.php';
if ('' != (isConnector = parseUrl('connector'))) {
	switch(isConnector) {
		case '###':
			break;
		case 'php':
		default:
			//$ajaxConnector = 'ajax/php/ajax.php';
	}
}

$('head').prepend('<script type="text/javascript" src="lang/' + $cfg.lang + '.js"></script>');
$('head').append('<link type="text/css" href="skin/' + $cfg.skin + '/' + $cfg.skin + '.css" rel="stylesheet" />');

if ($cfg.contextmenu) {
	$('head').append('<script type="text/javascript" src="lib/jquery.contextmenu.js"></script>');
}

var menuDiv	= {};
var statusDiv	= {};
var dialogDiv	= {};

var Action = {
	createFolder: function() {
		if ('root' == $cfg.dir || '' == $cfg.dir) {return false;}

		$cfg.tmp['mode'] = 'createFolder';
		$cfg.tmp['oldname'] = $cfg.dir;
		$cfg.tmp['key'] = $cfg.dir;

		dialogSet($lang.enterNameCreateFolder, '<b>' + $lang.location + '</b> [' + $cfg.url + $cfg.dir + '/]<br /><input type="text" id="newName" value="" class="t" /><br />' + $lang.allowRegSymbol);
		return;
	},
	renameFolder: function() {
		var folders = $cfg.dir.split('/');
		if (1 == folders.length) return;

		var folder = folders[folders.length - 1];
		var key = $cfg.dir.substring(0, $cfg.dir.lastIndexOf('/'));

		$cfg.tmp['mode'] = 'renameFolder';
		$cfg.tmp['oldname'] = $cfg.dir;
		$cfg.tmp['key'] = key;

		dialogSet($lang.enterNewNameFolder, '<b>' + folder + '</b> [' + $cfg.url + key + '/]<br /><input type="text" id="newName" value="" class="t" /><br />' + $lang.allowRegSymbol);
		return;
	},
	deleteFolder: function() {
		if ('root' == $cfg.dir || '' == $cfg.dir) {return false;}

		$('#dirsList').dynatree('disable');
		$.post($ajaxConnector + '?mode=deleteFolder', {
					dir:	$cfg.dir,
					type:	$cfg.type,
					lang:	$cfg.lang
				},
				function(reply) {
					$('#dirsList').dynatree('enable');
					if (reply.isDelete) {
						var key = $cfg.dir.substring(0, $cfg.dir.lastIndexOf('/'));
						var tree = $('#dirsList').dynatree('getTree');
						var node = tree.getNodeByKey(encodeURIComponent(key));
						node.reload(true);

						$('>div.l', statusDiv).html('<div class="warning"><b>' + $lang.successDeleteFolder + '</b></div>');
					} else {
						$('>div.l', statusDiv).html('<div class="warning"><b>' + $lang.failedDeleteFolder + '</b></div>');
					}
			}, 'json');
		return;
	},
	uploadFolder: function() {
		if ('' == $('input:file').val()) {
			$('>div.l', statusDiv).html('<div class="successUpload"><b>' + $lang.chooseDownloads + '</b></div>');
			return;
		}

		var downLoaded = $('#dowloaded');
		downLoaded.html('<div class="isDownload">' + $lang.resultUpload + '</div>').fadeIn(1000);
		$('#filesUploadForm').ajaxSubmit({
				url:	$ajaxConnector + '?mode=uploads' ,
				type:	'post',
				dataType: 'json',
				beforeSubmit: function() {
					var f = $('#filesUploadForm');
					$('input[name="dir"]', f).val($cfg.dir);
					$('input[name="type"]', f).val($cfg.type);
					return true;
				},
				success : function showResponse(response, status) {
					$('input:file').MultiFile('reset');
					var loaded = '';
					if (response.downloaded.length) {
						$('>div.l', statusDiv).html('<div class="successUpload"><b>' + $lang.successUpload + '</b></div>');
						for (var i=-1; ++i<response.downloaded.length;) {
							if (response.downloaded[i][0]) {
								loaded += '<div><span class="ok">ok</span> ' + response.downloaded[i][1] + '</div>';
							} else {
								loaded += '<div><span class="no">no</span> ' + response.downloaded[i][1] + '</div>';
							}
						}
						viewsUpdate($cfg.dir);
						downLoaded.append(loaded);
						setTimeout("$('#dowloaded').fadeOut(3000);", 2000);
					} else {
						downLoaded.fadeOut(1);
					}
				}
			});
		return;
	},

	deleteFiles: function() {
		var files = [];
		$('#fileThumb input[name="file\\[\\]"]:checked').each(function() {
				files.push(this.value);
			});
		if (!files.length) {
			return;
		}

		$.post($ajaxConnector + '?mode=deleteFiles', {
			dir:	$cfg.dir,
			files:	files.join('::'),
			type:	$cfg.type,
			lang:	$cfg.lang
		}, function(reply) {
			appendFiles(reply);
		}, 'json');
	},
	deleteFile: function() {
		if ('' == $cfg.file) return false;
		$.post($ajaxConnector + '?mode=deleteFiles', {
			dir:	$cfg.dir,
			files:	$cfg.file,
			type:	$cfg.type,
			lang:	$cfg.lang
		}, function(reply) {
			appendFiles(reply);
		}, 'json');
	},
	renameFile: function() {
		if ('' == $cfg.file) return false;
		$cfg.tmp['mode'] = 'renameFile';
		$cfg.tmp['oldname'] = $cfg.file;
		$cfg.tmp['key'] = '';

		dialogSet($lang.enterNewNameFile, '<b>' + $cfg.file + '</b> [' + $cfg.url + $cfg.dir + '/]<br /><input type="text" id="newName" value="" class="t" /><br />' + $lang.allowRegSymbol);
	},
	downloadFile: function() {
		if ('' == $cfg.file) return false;
		location.replace($ajaxConnector + '?downloadFile=' + $cfg.dir + '/' + $cfg.file);
	},
	lookFile: function() {
		if ('' == $cfg.file) return false;
		window.open($cfg.url + $cfg.dir +'/'+ $cfg.file, 'preView', '');
	},
	setThumb: function() {
		if ('' == $cfg.file) return false;
		_setReturnData($cfg.thumb);
	},
	setFile: function() {
		if ('' == $cfg.file) return false;
		_setReturnData($cfg.url + $cfg.dir +'/'+ $cfg.file);
	}

};


$(document).ready(function() {

	$('#loading').bind('ajaxSend', function() {
		$(this).show();
	}).bind('ajaxComplete', function() {
		$(this).hide();
	});

	$.post($ajaxConnector + '?mode=cfg', {
			type:	$cfg.type,
			lang: $cfg.lang
	},
	function(reply) {
		for (var i in reply.config) {
			$cfg[i] = reply.config[i];
		}

		$('#dirsList').dynatree({
			title: 'upload',
			rootVisible: true,
			persist: true,
			clickFolderMode: 1,
			fx: {height: "toggle", duration: 200},
			children: $cfg.children,
			onActivate: function(dtnode) {
				$cfg.file = '';
				$cfg.dir = decodeURIComponent(dtnode.data.key);
				viewsUpdate(dtnode.data.key);
				return;
			},
			onLazyRead: function(dtnode) {
				$.post($ajaxConnector + '?mode=getDirs', {
						dir:	dtnode.data.key,
						type:	$cfg.type,
						lang:	$cfg.lang
					},
					function(reply) {
						dtnode.addChild(reply.dirs);
						dtnode.setLazyNodeStatus(DTNodeStatus_Ok);
						$cfg.contextmenu? bindFolderContextMenu() : null;
					}
				, 'json');

				return false;
			}
		});
		
		if ('' != (tmp = $.cookie('dynatree-active'))) {
			$cfg.file = '';
			$cfg.dir = decodeURIComponent(tmp);
			viewsUpdate(tmp);
		}

		$cfg.contextmenu? bindFolderContextMenu() : null;
		$('.multi').MultiFile({
			max: 16,
			accept: $cfg.allow,
			list: '#uploadList',
			STRING: {
				remove:		$lang.removeFile,
				selected:	$lang.selected,
				denied:		$lang.deniedExt,
				duplicate:	$lang.duplicate
			}
		});

		if ('' != $cfg.maxUpload) {
			$('span[lang="chooseFileUpload"]', $('#uploadList')).append(' <' + $cfg.maxUpload + '');
		}

		$('head').append('<style type="text/css">#fileThumb .thumb img {width: '+$cfg.thumbWidth+'px;height:'+$cfg.thumbHeight+'px;} #fileThumb .name {width: '+$cfg.thumbWidth+'px;}</style>');

	}, 'json');


	$('.dirsMenu a', $('#dirs')).click(function() {
			if ('block' == $('#author').css('display')) {
				$('#author').hide();
			} else {
				$('#author').css({'left': '30%', 'top': '20%'}).show();
			}
			return false;
	});

	menuDiv		= $('#menu');
	statusDiv	= $('#status');
	dialogDiv		= $('#dialog');

	for (var i in $lang) {
		$('span[lang="' + i + '"]').text($lang[i]);
	}

	$('.view label[for="viewlist"], .view label[for="viewthumb"]', menuDiv).click(function() {
		if ($('#viewlist').attr('checked')) {
			$('#fileThumb').hide();
			$('#fileList').show();
			$cfg.view.list = true;
			$cfg.view.thumb = false;
		} else {
			$('#fileList').hide();
			$('#fileThumb').show();
			$cfg.view.list = false;
			$cfg.view.thumb = true;
		}
		return;
	});

	$('.display label', menuDiv).click(function() {
		var attrId = $(this).attr('for');
		var attrDiv = attrId.substring(4).toLowerCase();

		if ($('#' + attrId).attr('checked')) {
			$('#fileThumb .' + attrDiv).show();
			$cfg.display[attrId] = true;
		} else {
			$('#fileThumb .' + attrDiv).hide();
			$cfg.display[attrId] = false;
		}
	});

	$('#checkAll').click(function() {
		if ($(this).attr('checked')) {
			$('#fileList tbody input[name="file\\[\\]"], #fileThumb input[name="file\\[\\]"]').attr('checked', 'checked');
		} else {
			$('#fileList tbody input[name="file\\[\\]"], #fileThumb input[name="file\\[\\]"]').removeAttr('checked');
		}
		return;
	});

	$('.sort label', menuDiv).click(function() {
		var attrId = $(this).attr('for');
		$cfg.sort = attrId.substring(4).toLowerCase();
		viewsUpdate($cfg.dir);
	});

	$('.dirsMenu .folderMenu', $('#dirs')).html('\
			<a href="#" onclick="Action.uploadFolder()" class="uploadFolder" title="' + $lang.uploadSelectFiles + '"></a>\
			<a href="#" class="separator"></a>\
			<a href="#" onclick="Action.deleteFolder()" class="deleteFolder" title="' + $lang.deleteFolder + '"></a>\
			<a href="#" onclick="Action.renameFolder()" class="renameFolder" title="' + $lang.renameFolder + '"></a>\
			<a href="#" onclick="Action.createFolder()" class="createFolder" title="' + $lang.createFolder + '"></a>\
	');
	$('.r div', statusDiv).html('\
			<a href="#" onclick="Action.deleteFiles()" class="deleteFiles" title="' + $lang.deleteCheckedFile + '"></a>\
			<a href="#" onclick="Action.deleteFile()" class="deleteFile" title="' + $lang.deleteFile + '"></a>\
			<a href="#" class="separator"></a>\
			<a href="#" onclick="Action.renameFile()" class="renameFile" title="' + $lang.renameFile + '"></a>\
			<a href="#" onclick="Action.downloadFile()" class="downloadFile" title="' + $lang.downloadFile + '"></a>\
			<a href="#" onclick="Action.lookFile()" class="lookFile" title="' + $lang.lookAt + '"></a>\
			<a href="#" class="separator"></a>\
			<a href="#" onclick="Action.setThumb()" class="setThumb" title="' + $lang.selectThumb + '"></a>\
			<a href="#" onclick="Action.setFile()" class="setFile" title="' + $lang.select + '"></a>\
	');

	if ($cfg.contextmenu) {
		$cfg.menu.file = [
			{
				'<span lang="select">Select</span>' : {
					onclick : function(menuItem, menu) {return Action.setFile();},
					icon : 'skin/_ico/arrow_rotate_anticlockwise.png'
				}
			},
			{
				'<span lang="selectThumb">Select this thumbnail</span>' : {
					onclick : function(menuItem, menu) {return Action.setThumb();},
					disabled : false,
					icon : 'skin/_ico/arrow_out.png'
				}
			},
			$.contextMenu.separator,
			{
				'<span lang="lookAt">Look</span>' : {
					onclick : function(menuItem, menu) {return Action.lookFile();},
					icon : 'skin/_ico/eye.png'
				}
			},
			{
				'<span lang="downloadFile">Download file</span>' : {
					onclick : function(menuItem, menu) {return Action.downloadFile();},
					icon : 'skin/_ico/arrow_down.png'
				}
			},
			{
				'<span lang="renameFile">Rename file</span>' : {
					onclick: function(menuItem, menu) {return Action.renameFile();},
					icon: 'skin/_ico/application_xp_terminal.png'
				}
			},
			$.contextMenu.separator,
			{
				'<span lang="deleteFile">Delete file</span>' : {
					onclick: function(menuItem, menu) {return Action.deleteFile();},
					icon: 'skin/_ico/cross.png'
				}
			},
			{
				'<span lang="deleteCheckedFile">Delete checked files</span>' : {
					onclick: function(menuItem, menu) {return Action.deleteFiles();},
					icon: 'skin/_ico/delete.png'
				}
			}
		];

		$cfg.menu.folder = [
			{
				'<span lang="createFolder">Create folder</span>' : {
					onclick: function(menuItem, menu) {return Action.createFolder();},
					icon: 'skin/_ico/folder_add.png'
				}
			},
			{
				'<span lang="renameFolder">Rename folder</span>' : {
					onclick: function(menuItem, menu) {return Action.renameFolder();},
					icon: 'skin/_ico/application_xp_terminal.png'
				}
			},
			{
				'<span lang="deleteFolder">Delete folder</span>' : {
					onclick: function(menuItem, menu) {return Action.deleteFolder();},
					icon: 'skin/_ico/folder_delete.png'
				}
			},
			$.contextMenu.separator,
			{
				'<span lang="uploadSelectFiles">Upload selected files</span>' : {
					onclick : function(menuItem, menu) {return Action.uploadFolder();},
					icon : 'skin/_ico/arrow_up.png'
				}
			}
		];
	}

	$(dialogDiv).dialog({
		bgiframe: true,
		resizable: false,
		width: 400,
		height: 180,
		modal: true,
		autoOpen: false,
		overlay: {
			backgroundColor: '#000',
			opacity: 0.5
		},
		buttons: {
			'Cancel': function() {
				$(this).dialog('close');
			},
			' OK ': function() {
				var newname = $('#newName').val();
				if (!/^[a-z0-9-_#~\$%()\[\]&=]+/i.test(newname)) {
					return false;
				}
				$(this).dialog('close');
				$('#dialog input').attr('disabled', 'disabled');

				$.post($ajaxConnector + '?mode=' + $cfg.tmp['mode'], {
						dir:	$cfg.dir,
						type:	$cfg.type,
						lang:	$cfg.lang,
						oldname:	$cfg.tmp['oldname'],
						newname:	newname
					},
					function(reply) {
						if (reply.isSuccess && ('createFolder' == $cfg.tmp['mode'] || 'renameFolder' == $cfg.tmp['mode'])) {
							if ('exist' == reply.isSuccess) {
								$('>div.l', statusDiv).html('<div class="warning"><b>' + $lang.folderExist + '</b></div>');
								return;
							}
							var tree = $('#dirsList').dynatree('getTree');
							var node = tree.getNodeByKey(encodeURIComponent($cfg.tmp['key']));
							node.reload(true);
						} else {
							appendFiles(reply);
						}
					}
				, 'json');

				return;
			}
		}
	});

	if ($('#author a').length) $('#author a').attr('target', '_blank'); else $('#files').html(''); // :/
});


function dialogSet(title, html)
{
	$('div.ui-dialog span.ui-dialog-title').html(title);
	$(dialogDiv).html(html);
	$(dialogDiv).dialog('open');
	$('#newName').focus();
	return;
}

function bindFolderContextMenu()
{
	return $('.ui-dynatree-document, .ui-dynatree-folder').not('#ui-dynatree-id-root').contextMenu($cfg.menu.folder, {
		theme: $cfg.skin,
		beforeShow: function() {
			$cfg.dir = decodeURIComponent($(this.target).attr('id'));
			$cfg.dir = $cfg.dir.substr($cfg.cutKey);

			/*if ('' == $('input[name="uploadFiles\\[\\]"]').val()) {
				$(this.menu).find('.context-menu-item').eq(4).addClass('context-menu-item-disabled');
			} else {
				$(this.menu).find('.context-menu-item').eq(4).removeClass('context-menu-item-disabled');
			}*/

			for (var i in $lang) {$('span[lang="' + i + '"]', $(this.menu)).text($lang[i]);}/*		TODO: remake		*/
		}
	});
}

function bindFileContextMenu()
{
	return $('#fileThumb .thumb, #fileList .name').contextMenu($cfg.menu.file, {
		theme: $cfg.skin,
		beforeShow: function() {
			$cfg.file = $cfg.view.thumb? $('.name', this.target).text() : $('a', this.target).parent().text();
			if ('' == $(this.target).attr('thumb')) {
				$(this.menu).find('.context-menu-item').eq(1).toggleClass('context-menu-item-disabled');
			} else {
				$(this.menu).find('.context-menu-item').eq(1).removeClass('context-menu-item-disabled');
			}

			for (var i in $lang) {$('span[lang="' + i + '"]', $(this.menu)).text($lang[i]);}/*		TODO: remake		*/
		}
	});
}

function _setReturnData(input, data)
{
	switch($cfg.returnTo) {
		case 'ckeditor':
			window.top.opener['CKEDITOR'].tools.callFunction(parseUrl('CKEditorFuncNum'), input, data);
			window.top.close();
			window.top.opener.focus();
			break;

		case 'tinymce':
			var win = window.dialogArguments || opener || parent || top;
			tinyMCE = win.tinyMCE;
			var params = tinyMCE.activeEditor.windowManager.params;
			params.window.document.getElementById(params.input).value = input;
			try {
				params.window.ImageDialog.showPreviewImage(input);
			} catch(e) {}
			window.close();
			break;

		default:
			try {
				if ('$' == $cfg.returnTo.substr(0, 1)) {
					var objInput = $cfg.returnTo.substr(1);
					window.top.opener.document.getElementById(objInput).value = input;
				} else {
					window.top.opener[$cfg.returnTo](input);
				}
				window.close();
			} catch(e) {
				alert('Function is not available or does not exist: ' + $cfg.returnTo + "\r" + e.message);
			}
	}

	return true;
}

function viewsUpdate(dir)
{
	if ('root' == dir)
		return;

	$.post($ajaxConnector + '?mode=getFiles', {
			dir:	dir,
			type:	$cfg.type,
			lang:	$cfg.lang,
			sort:	$cfg.sort
		},
		function(reply) {
			appendFiles(reply);
		}
	, 'json');

	return;
}

function appendFiles(reply)
{
	$('>div.l', statusDiv).html('<div>' + $cfg.url + $cfg.dir + '/</div><div><b>' + reply.files.length + '</b> ' + $lang.fileOf + '</div>');

	var files = reply.files;
	var list = '', thumb = '', w_h = '', attr = '';

	for (var i in files) {
		attr = 'file="' + ($cfg.url + $cfg.dir + '/' + files[i].name) + '" thumb="' + (files[i].width? ($cfg.url + $cfg.thumb + '/' + $cfg.dir + '/' + files[i].name) : '') + '"';
		thumb += '<div class="thumb ext_' + files[i].ext + '" ' + attr + '><div class="image">';

		if (files[i].width) {
			w_h = '(' + files[i].width + ' x ' + files[i].height + ') ';
			thumb += '<img src="' + files[i].thumb + '" alt="" />';
		} else {
			w_h = '';
			thumb += '<img src="skin/.gif" alt="" />';
		}

		thumb += '</div><div class="check"><input type="checkbox" name="file[]" value="' + files[i].name + '" /></div>';
		thumb += '<div class="name" ' + ($cfg.display.fileName? 'style="display:block;"' : 'style="display:none"') + '>' + files[i].name + '</div>';
		thumb += '<div class="date" ' + ($cfg.display.fileDate? 'style="display:block;"' : 'style="display:none"') + '>' + files[i].date + '</div>';
		thumb += '<div class="size" ' + ($cfg.display.fileSize? 'style="display:block;"' : 'style="display:none"') + '>' + w_h + files[i].size + '</div>';
		thumb += '</div>';

		list += '<tr>';
		list += '<td><input type="checkbox" name="file[]" value="' + files[i].name + '" /></td>';
		list += '<td><div class="name"' + attr + '><a href="">' + files[i].name + '</a></div></td>';
		list += '<td><div class="date">' + files[i].date + '</div></td>';
		list += '<td><div class="size">' + w_h + files[i].size + '</div></td>';
		list += '</tr>';
	}

	$('#fileThumb').html(thumb);
	$('#fileList > table > tbody').html(list);
	$('#fileThumb > div.thumb').each(function() {

		var div = $(this);
		div.click(function() {
			$('#fileThumb > div').removeClass('thumbClick');
			$cfg.file = $('.name', div).text();
			$cfg.thumb = $(div).attr('thumb');
			$('>div.l', statusDiv).html('<div class="cutName"><a href="' + $cfg.url + $cfg.dir + '/' + $cfg.file + '" target="_urlFile">' + $cfg.url + $cfg.dir + '/' + $cfg.file + '</a></div><div>'+$lang.fileSize+': '+$('.size', div).text()+'</div><div>'+$lang.fileDate+': '+$('.date', div).text()+'</div>');
			div.addClass('thumbClick');

		}).mouseover(function() {
			div.addClass('thumbOver');

		}).mouseout(function() {
			div.removeClass('thumbOver');
			//div.css({'color': '#fff', 'background-color': '#5a5a5a'});

		}).dblclick(function() {
			_setReturnData($cfg.url + $cfg.dir + '/' + $('.name', div).text());
		});

		$('#fileList .name a').dblclick(function () {
			_setReturnData($cfg.url + $cfg.dir + '/' + $(this).text());
			return false;
		}).click(function() {
			$cfg.file = $(this).text();
			$('>div.l', statusDiv).html('<div class="cutName"><a href="' + $cfg.url + $cfg.dir + '/' + $cfg.file + '" target="_urlFile">' + $cfg.url + $cfg.dir + '/' + $cfg.file + '</a></div><div>'+$lang.fileSize+': '+$('.size', div).text()+'</div><div>'+$lang.fileDate+': '+$('.date', div).text()+'</div>');
			return false;
		});

	});


	$('#fileList input[name="file\\[\\]"]').click(function () {
		$(this).attr('checked')? $('#fileThumb input[value="' + $(this).attr('value') + '"]').attr('checked', 'checked') : $('#fileThumb input[value="' + $(this).attr('value') + '"]').removeAttr('checked');
	});
	$('#fileThumb input[name="file\\[\\]"]').click(function () {
		$(this).attr('checked')? 	$('#fileList input[value="' + $(this).attr('value') + '"]').attr('checked', 'checked') : $('#fileList input[value="' + $(this).attr('value') + '"]').removeAttr('checked');
	});

	$cfg.contextmenu? bindFileContextMenu() : null;
	return;
}







/*
 * -----
 * misc
 *
 * */

function parseUrl(name)
{
	name = name.replace(/[\[]/,"\\\[").replace(/[\]]/,"\\\]");
	var regexS = "[\\?&]"+name+"=([^&#]*)";
	var regex = new RegExp( regexS );
	var results = regex.exec( window.location.href );
	if (null == results) {
		return '';
	}
	return results[1];
}

var cssFix = function()
{
	var u = navigator.userAgent.toLowerCase(),
	is = function(t) {
		return (u.indexOf(t) != -1);
	};
	$("html").addClass([(!(/opera|webtv/i.test(u)) && /msie (\d)/.test(u)) ? ('ie ie' + RegExp.$1)
		: is('firefox/2') ? 'gecko ff2'	: is('firefox/3') ? 'gecko ff3'	: is('gecko/') ? 'gecko'
		: is('chrome/') ? 'chrome'
		: is('opera/9') ? 'opera opera9'	: /opera (\d)/.test(u) ? 'opera opera' + RegExp.$1
		: is('konqueror') ? 'konqueror'
		: is('applewebkit/') ? 'webkit safari'
		: is('mozilla/') ? 'gecko'
		: '',
		(is('x11') || is('linux')) ? ' linux' : is('mac') ? ' mac' : is('win') ? ' win'
	: ''].join(''));
}();

