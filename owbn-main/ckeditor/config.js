/**
 * @license Copyright (c) 2003-2023, CKSource Holding sp. z o.o. All rights reserved.
 * For licensing, see https://ckeditor.com/legal/ckeditor-oss-license
 */


CKEDITOR.editorConfig = function( config ) {
	// Define changes to default configuration here.
	// For complete reference see:
	// https://ckeditor.com/docs/ckeditor4/latest/api/CKEDITOR_config.html

	// The toolbar groups arrangement, optimized for two toolbar rows.
	


    // Remove formatting when pasting with Ctrl+V

	
	config.toolbarGroups = [
		{ name: 'clipboard',   groups: [ 'clipboard', 'undo' ] },
		{ name: 'editing',     groups: [ 'find', 'selection', 'spellchecker' ] },
		{ name: 'links' },
		{ name: 'insert' },
		{ name: 'forms' },
		{ name: 'tools' },
		{ name: 'document',	   groups: [ 'mode', 'document', 'doctools' ] },
		{ name: 'others' },
		'/',
		{ name: 'basicstyles', groups: [ 'basicstyles', 'cleanup' ] },
		{ name: 'paragraph',   groups: [ 'list', 'indent', 'blocks', 'align', 'bidi' ] },
		{ name: 'styles' },
		// { name: 'color' },
		{ name: 'colors', groups: ['TextColor', 'BGColor'] }, 
		{ name: 'about' }
	];

	// Remove some buttons provided by the standard plugins, which are
	// not needed in the Standard(s) toolbar.

	config.removeButtons = 'Underline,Subscript,Superscript';
	config.extraPlugins = 'panelbutton';
	config.extraPlugins = 'colorbutton';
	// Set the most common block elements.
	config.format_tags = 'p;h1;h2;h3;pre';

	// Simplify the dialog windows.
	config.removeDialogTabs = 'image:advanced;link:advanced';



	 // Force pasting as plain text
	 config.forcePasteAsPlainText = true;
    
	 // Disable the "Paste from Word" plugin
	 config.removePlugins = 'pastefromword';
 
	 // Add custom context menu item
	//  CKEDITOR.on('instanceReady', function (ev) {
	// 	 var editor = ev.editor;
 
	// 	 console.log(ev,"ev")
	// 	 // Add a new menu group
	// 	 editor.addMenuGroup('customClipboard');
 
	// 	 // Add a new menu item
	// 	 editor.addMenuItem('pastePlainText', {
	// 		 label: 'Paste as Plain Text',
	// 		 command: 'paste',
	// 		 group: 'customClipboard',
	// 		 order: 1
	// 	 });
 
	// 	 // Add the new menu item to the context menu
	// 	 editor.contextMenu.addListener(function () {
	// 		 console.log(CKEDITOR.TRISTATE_OFF,"CKEDITOR.TRISTATE_OFF")
	// 		 return { pastePlainText: CKEDITOR.TRISTATE_OFF };
	// 	 });
 
	
	//  });



	CKEDITOR.on("instanceReady", function(event) {
		event.editor.on("beforeCommandExec", function(event) {
			// Show the paste dialog for the paste buttons and right-click paste
			if (event.data.name == "paste") {
				event.editor._.forcePasteDialog = true;
			}
			// Don't show the paste dialog for Ctrl+Shift+V
			if (event.data.name == "pastetext" && event.data.commandData.from == "keystrokeHandler") {
				event.cancel();
			}
		})
	});
	
	
};












//  Detect `Ctrl+Shift+V` and force plain text
CKEDITOR.on('instanceReady', function (event) {
	var editor = event.editor;

	//  Detect Keydown for `Ctrl+Shift+V`
	editor.document.on('keydown', function (evt) {
		if (evt.data.$.ctrlKey && evt.data.$.shiftKey && evt.data.$.key.toLowerCase() === 'v') {
			editor.forcePlainTextPaste = true;
		} else {
			editor.forcePlainTextPaste = false;
		}
	});

	//  Handle Paste Event (Fixes Clipboard Paste)
	editor.on('paste', function (evt) {
		if (editor.forcePlainTextPaste) {
			alert(" Ctrl+Shift+V Detected - Pasting as Plain Text");
			var plainText = evt.data.dataValue.replace(/<[^>]*>/g, ""); //  Remove all HTML tags
			evt.data.dataValue = decodeHTMLEntities(plainText); //  Convert HTML entities
		}
	});

	//  Enable Right-Click "Paste" and "Paste as Plain Text"
	editor.contextMenu.addListener(function () {
		return {
			paste: CKEDITOR.TRISTATE_OFF, // Normal Paste
			pastePlainText: CKEDITOR.TRISTATE_OFF // Paste as Plain Text
		};
	});

	//  Add "Paste as Plain Text" Command
	editor.addMenuItem('pastePlainText', {
		label: 'Paste as Plain Text',
		command: 'pastePlainTextCommand',
		group: 'clipboard',
		order: 1
	});

	//  Fix Right-Click "Paste"
	editor.addMenuItem('paste', {
		label: 'Paste',
		command: 'pasteCommand',
		group: 'clipboard',
		order: 0
	});

	//  Fix Right-Click "Paste" (Insert HTML Instead of Plain Text)
	editor.addCommand('pasteCommand', {
		exec: async function (editor) {
			try {
				if (navigator.clipboard && navigator.clipboard.read) {
					const clipboardItems = await navigator.clipboard.read();
					for (let item of clipboardItems) {
						for (let type of item.types) {
							if (type === "text/html") {
								const blob = await item.getType(type);
								const html = await blob.text();
								console.log(" Pasting HTML Content:", html);
								editor.insertHtml(html); //  Insert HTML correctly!
								return;
							}
						}
					}
				}
				console.warn(" HTML not found in clipboard, falling back to plain text.");
				const text = await navigator.clipboard.readText();
				editor.insertText(text);
			} catch (error) {
				console.error(" Clipboard error:", error);
				//alert(" Your browser blocks clipboard access. Use Ctrl+V instead.");
			}
		}
	});

	//  Fix Right-Click "Paste as Plain Text"
	editor.addCommand('pastePlainTextCommand', {
		exec: function (editor) {
			if (navigator.clipboard) {
				navigator.clipboard.readText().then(function (clipboardText) {
					clipboardText = clipboardText.replace(/<[^>]*>/g, ""); //  Strip all HTML tags
					clipboardText = decodeHTMLEntities(clipboardText); //  Convert HTML entities
					editor.insertText(clipboardText); //  Now Inserts Plain Text properly!
				}).catch(function (err) {
					console.error("Clipboard read error:", err);
					//alert(" Clipboard access denied. Use Ctrl+Shift+V instead.");
				});
			} else {
				//alert(" Your browser does not support clipboard pasting. Use Ctrl+Shift+V instead.");
			}
		}
	});
});

//  Function to Decode HTML Entities (like `&lt;` → `<`)
function decodeHTMLEntities(text) {
	var doc = new DOMParser().parseFromString(text, "text/html");
	return doc.documentElement.textContent;
}










// CKEDITOR.editorConfig = function (config) {
//     // Force pasting as plain text
//     config.forcePasteAsPlainText = true;
    
//     // Disable the "Paste from Word" plugin
//     config.removePlugins = 'pastefromword';

//     // Add custom context menu item
//     CKEDITOR.on('instanceReady', function (ev) {
//         var editor = ev.editor;

// 		console.log(ev,"ev")
//         // Add a new menu group
//         editor.addMenuGroup('customClipboard');

//         // Add a new menu item
//         editor.addMenuItem('pastePlainText', {
//             label: 'Paste as Plain Text',
//             command: 'paste',
//             group: 'customClipboard',
//             order: 1
//         });

//         // Add the new menu item to the context menu
//         editor.contextMenu.addListener(function () {
// 			console.log(CKEDITOR.TRISTATE_OFF,"CKEDITOR.TRISTATE_OFF")
//             return { pastePlainText: CKEDITOR.TRISTATE_OFF };
//         });

// 		CKEDITOR.on('instanceReady', function (ev) {
// 			ev.editor.on('paste', function (evt) {
// 				evt.data.dataValue = evt.data.dataValue.replace(/(<([^>]+)>)/gi, ""); // Strip HTML
// 			});
// 		});
//     });
// };


// CKEDITOR.editorConfig = function( config ) {
   
// };
