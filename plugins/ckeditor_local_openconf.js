// CKEditor default config for OpenConf

CKEDITOR.editorConfig = function( config ) {
        config.toolbar = "OPENCONF";
		config.removePlugins = 'elementspath';
        config.toolbar_OPENCONF = [
                { name: 'basicstyles', items : [ 'Bold','Italic','Underline','Subscript','Superscript' ] },
                { name: 'paragraph', items : [ 'NumberedList','BulletedList','-','Outdent','Indent','-','Blockquote' ] },
                { name: 'styles', items : [ 'Styles','Format','Font','FontSize'] },
                { name: 'colors', items : [ 'TextColor','BGColor' ] },
                { name: 'clipboard', items : [ 'Copy','Paste','PasteText','PasteFromWord','-','Undo','Redo' ] },
                { name: 'editing', items : [ 'Find','SelectAll','SpellChecker' ] },
                { name: 'insert', items : [ 'Image','Table','HorizontalRule','SpecialChar' ] },
                { name: 'links', items : [ 'Link','Unlink','Anchor' ] },
                { name: 'document', items : [ 'Source' ] }
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
