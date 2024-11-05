// CKEditor default config for OpenConf

CKEDITOR.editorConfig = function( config ) {
	config.toolbar = [
		{ name: 'document', items: [ 'Source' ] },
		{ name: 'clipboard', items: [ 'Cut', 'Copy', 'Paste', 'PasteText', 'PasteFromWord', '-', 'Undo', 'Redo' ] },
		{ name: 'editing', items: [ 'Find', 'Replace' ] },
		{ name: 'basicstyles', items: [ 'Bold', 'Italic', 'Underline', 'Strike', 'Subscript', 'Superscript', '-', 'RemoveFormat' ] },
		{ name: 'paragraph', items: [ 'NumberedList', 'BulletedList', '-', 'Outdent', 'Indent', '-', 'Blockquote', '-', 'JustifyLeft', 'JustifyCenter', 'JustifyRight' ] },
		{ name: 'links', items: [ 'Link', 'Unlink', 'Anchor' ] },
		{ name: 'insert', items: [ 'Image', 'Table', 'HorizontalRule', 'SpecialChar' ] },
		{ name: 'styles', items: [ 'Styles', 'Format', 'Font', 'FontSize' ] },
		{ name: 'colors', items: [ 'TextColor', 'BGColor' ] },
		{ name: 'tools', items: [ 'Maximize' ] },
		{ name: 'about', items: [ 'About' ] }
	];
};

CKEDITOR.on( 'instanceReady', function( ev )
   {
       var tags = ['p', 'ol', 'ul', 'li', 'h1', 'h2', 'h3', 'h4', 'h5'];
       for (var key in tags) {
          ev.editor.dataProcessor.writer.setRules(tags[key],
                 {
                        indent : false,
                        breakBeforeOpen : true,
                        breakAfterOpen : false,
                        breakBeforeClose : false,
                        breakAfterClose : true
                 });
           }
   });
