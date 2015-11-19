/**
* 2014-2015 (c) Axalone France - Express-Mailing
*
* This file is a commercial module for Prestashop
* Do not edit or add to this file if you wish to upgrade PrestaShop or
* customize PrestaShop for your needs please refer to
* http://www.express-mailing.com for more information.
*
* @author    Axalone France <info@express-mailing.com>
* @copyright 2014-2015 (c) Axalone France
* @license   http://opensource.org/licenses/GPL-3.0  GNU General Public License, version 3 (GPL-3.0)
*/

jQuery(document).ready(function()
{
	/* Override de "function tinySetup(config)" du fichier /js/tinymce.inc.js */
	/* Evite l'inclusion des fonctionnalités "filemanager" problématiques pour la sécurité */

	tinySetup = function(config)
	{
		tinyMCE.init(config);
		$('.AdminMarketingEStep3 iframe').each(function(i, elem){
			$(elem).css('height', '300px');
		});
	};

	/* Config du TinyMCE pour Express-Mailing */

	newsletter_config = {

		editor_selector : "autoload_rte",
		selector : ".autoload_rte",
		skin: "prestashop",

		toolbar1 : "save,|,newdocument,|,cut,copy,paste,pasteword,|,undo,redo,|,searchreplace,|,bullist,numlist,|,outdent,indent,|,search,replace,|,table,|,insertdate,inserttime,|,hr,charmap",
		toolbar2 : "bold,italic,underline,strikethrough,|,superscript,subscript,blockquote,|,alignleft,aligncenter,alignright,alignjustify,rtl,|,link,unlink,anchor,image,emoticons,|,visualchars,visualblocks,|,preview,fullscreen,code",
        toolbar3 : "styleselect,|,formatselect,|,fontselect,|,fontsizeselect,|,colorpicker,forecolor",

		plugins : "fullpage visualblocks preview tabfocus fullscreen visualchars directionality style searchreplace insertdatetime charmap hr colorpicker anchor code link autolink image paste table lists advlist contextmenu filemanager textcolor emoticons save",

		menubar : false,
		statusbar : true,
		resizing : false,
		height : "300",
		blocks : true,
		browser_spellcheck : true,
		cleanup: false,

		force_p_newlines : false,
		force_br_newlines : true,
		forced_root_block : false,
		object_resizing : true,
		visual : true,
		custom_undo_redo_levels : 15,

		convert_urls : false,	/* place an http front of each link */
		relative_urls : false,	/* place an http front of each link */
		remove_script_host : true,
		fontsize_formats : "8pt 9pt 10pt 11pt 12pt 13pt 14pt 18pt 24pt",

		entity_encoding : "raw",
		image_advtab : true,
		language : tinyLanguage ? tinyLanguage : "en",

		insertdatetime_formats: ["%A %d %B %Y", "%d/%m/%Y", "%Y.%m.%d", "%Hh%M", "%d/%m/%Y - %Hh%M"],

		valid_elements : '*[*]',
		extended_valid_elements : "img[id|dir|usemap|style|class|src|border|alt|title|hspace|vspace|width|height|align]",

		menu: {
			edit: {title: 'Edit', items: 'undo redo | cut copy paste | selectall'},
			insert: {title: 'Insert', items: 'media image link | pagebreak'},
			view: {title: 'View', items: 'visualaid'},
			format: {title: 'Format', items: 'bold italic underline strikethrough superscript subscript | formats | removeformat'},
			table: {title: 'Table', items: 'inserttable tableprops deletetable | cell row column'},
			tools: {title: 'Tools', items: 'code'}
		}

	};

	/* Chargement graphique de l'éditeur HTML */

	tinySetup(newsletter_config);

});