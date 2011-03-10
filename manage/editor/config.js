/*
Copyright (c) 2003-2010, CKSource - Frederico Knabben. All rights reserved.
For licensing, see LICENSE.html or http://ckeditor.com/license
*/

CKEDITOR.editorConfig = function( config )
{
    // Define changes to default configuration here. For example:
    // config.language = 'fr';
    // config.uiColor = '#AADC6E';
    
    config.toolbar_Limited =
    [
        ['Source','-','Maximize', 'ShowBlocks'],
        ['Cut','Copy','Paste','PasteText','PasteFromWord'],
        ['Undo','Redo','-','Find','Replace','-','SelectAll','RemoveFormat'],
        '/',
        ['Bold','Italic','Underline','Strike','-','Subscript','Superscript'],
        ['NumberedList','BulletedList','-','Outdent','Indent'],
        ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
        '/',
        ['Link','Unlink','Anchor'],
        ['Image','Flash','Table','HorizontalRule','SpecialChar'],
        ['TextColor','BGColor'],
        '/',
        ['Styles','Font','FontSize'],
    ];
    
    config.toolbar_Basic =
    [
        ['Source','-','Maximize', 'ShowBlocks'],
        ['Bold', 'Italic'], 
        ['NumberedList', 'BulletedList'],
        ['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock'],
        ['Link', 'Unlink']
    ];
};
