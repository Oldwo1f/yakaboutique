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
* @license   http://www.express-mailing.com
*}

<table style="height: 100%; margin: auto auto; text-align: center">
<thead>
	<th style="padding: 0.2em 0em; width: 11em; border: 1px solid #878496; background-color: #878496; color:#FFFFFF; text-align: center;">
		{l s='Recipients' mod='expressmailing'}
	</th>
	<th style="padding: 0.2em 0em; width: 11em; border: 1px solid #878496; background-color: #878496; color:#FFFFFF; text-align: center;">
		{l s='Unit cost' mod='expressmailing'}
	</th>
	<th style="padding: 0.2em 0em; width: 11em; border: 1px solid #878496; background-color: #878496; color:#FFFFFF; text-align: center;">
		{l s='Total cost' mod='expressmailing'}
	</th>
</thead>
<tbody>
	{foreach $count_detail_array as $row}
		<tr >
			<td style="padding: 0.5em; padding-left: .8em; padding-right: .8em; border: 1px solid #E6E6E6;">
				{$row['count_recipients']|intval}
			</td>
			<td style="padding: 0.5em; padding-left: .8em; padding-right: .8em; border: 1px solid #E6E6E6;">
				{$row['count_sms']|intval}
			</td>
			<td style="padding: 0.5em; padding-left: .8em; padding-right: .8em; border: 1px solid #E6E6E6;">
				{($row['count_recipients'] * $row['count_sms'])|intval} sms
			</td>
		</tr>
	{/foreach}
</tbody>
</table>