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

var AjexFileManager = {
	init: function(params) {
		if ('undefined' == typeof(params)) params = {};

		this.path = params.path || function() {
			var s = document.getElementsByTagName('script');
			for (var i=-1; ++i<s.length;) {
				if (s[i].getAttribute('src') && -1 != (src = s[i].getAttribute('src')).indexOf('/ajex.js')) {
					return src.substring(0, src.lastIndexOf('/'));
				}
			}
			alert('Undefined variable "path" in AjexFileManager.init({path:"/path/to/AjexFileManager/"});');
			return null;
		}();
		if ('/' == this.path.substring(this.path.length-1)) {
			this.path = this.path.substring(0, this.path.length-1);
		}

		this.returnTo = params.returnTo || 'ckeditor';
		this.connector = params.connector || 'php';

		this.width = params.width || 1000;
		this.height = params.height || 660;
		this.skin = params.skin || 'dark';
		this.lang = params.lang || 'ru';

		if ('undefined' != typeof(params.contextmenu) && false === params.contextmenu) {
			this.contextmenu = false;
		} else {
			this.contextmenu = true;
		}

		if ('ckeditor' == this.returnTo) {
			if ('undefined' != typeof(params.editor)) {
				params.editor.config['filebrowserWindowWidth']	= this.width;
				params.editor.config['filebrowserWindowHeight']	= this.height;
				params.editor.config['filebrowserBrowseUrl']	= this.path + '/index.html?type=file&connector=' + this.connector + '&lang=' + this.lang + '&returnTo=' + this.returnTo + '&skin=' + this.skin + '&contextmenu=' + this.contextmenu;
				params.editor.config['filebrowserUploadUrl']	= this.path + '/ajax/' + this.connector + '/ajax.' + this.connector + '?type=file&mode=QuickUpload';

				var type = ['Flash', 'Image'];
				for (var i in type) {
					params.editor.config['filebrowser' + type[i] + 'WindowWidth']	= this.width;
					params.editor.config['filebrowser' + type[i] + 'WindowHeight']	= this.height;
					params.editor.config['filebrowser' + type[i] + 'BrowseUrl']	= this.path + '/index.html?type=' + type[i].toLowerCase() + '&connector=' + this.connector + '&lang=' + this.lang + '&returnTo=' + this.returnTo + '&skin=' + this.skin + '&contextmenu=' + this.contextmenu;
					params.editor.config['filebrowser' + type[i] + 'UploadUrl']	= this.path + '/ajax/' + this.connector + '/ajax.' + this.connector + '?mode=QuickUpload&type=' + type[i].toLowerCase();
				}
			} else {
				alert('You need to pass the object in the variable "editor"');
			}

		} else if('tinymce' == this.returnTo) {

		} else {
			this.type = params.type || 'file';
			this.url = this.path + '/index.html?type=' + this.type.toLowerCase() + '&connector=' + this.connector + '&lang=' + this.lang + '&skin=' + this.skin + '&contextmenu=' + this.contextmenu;
			this.args = 'width=' + this.width +',height=' + this.height + 'resizable=1,menubar=0,scrollbars=0,location=1,left=0,top=0,screenx=,screeny=';
		}

		return;
	},

	open: function(params, url, type, win) {
		if ('undefined' != typeof(params.returnTo)) {
			returnTo = params.returnTo;
		} else {
			returnTo = this.returnTo;
		}

		switch(returnTo) {
			case 'ckeditor':
				break;

			case 'tinymce':
			    tinyMCE.activeEditor.windowManager.open({
			        url: this.path + '/index.html?type=' + type.toLowerCase() + '&connector=' + this.connector + '&returnTo=' + this.returnTo + '&lang=' + this.lang + '&skin=' + this.skin + '&contextmenu=' + this.contextmenu,
			        width: this.width,
			        height: this.height,
			        inline: 'yes',
			        close_previous: 'no'
			    }, {
			        window: win,
			        input: params
			    });
				break;

			default:
				var win = window.open(this.url + '&returnTo=' + returnTo, 'AjexFileManager', this.args);
				win.focus();
				break;
		}

		return;
	}

}
