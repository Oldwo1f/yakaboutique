{*
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
*}

<script type="text/javascript">
	$(function () {
		function expandAll($element)
		{
			$element.find("label.tree-toggler").each(
				function()
				{
					$(this).parent().children(".icon-folder-close")
						.removeClass("icon-folder-close")
						.addClass("icon-folder-open");
					$(this).parent().parent().children("ul.tree").show();
				}
			);

			return $($element);
		}
		expandAll($('#categories-products-treeview'));
		$('#collapse-all-categories-products-treeview').show();
		$('#expand-all-categories-products-treeview').hide();
		$('.tree-panel-heading-controls').parent().css("padding-bottom", "0px").css("margin-bottom", "0px");
		$('.tree-panel-heading-controls').removeClass('tree-panel-heading-controls').addClass('panel-heading');
		$('.tree-actions').hide();
	});
</script>