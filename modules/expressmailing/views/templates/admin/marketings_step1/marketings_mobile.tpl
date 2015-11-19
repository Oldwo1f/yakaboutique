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

	$(function ()
	{
		var $, SmsCounter;
		window.SmsCounter = SmsCounter = (function ()
		{
			function SmsCounter()
			{
			}

			SmsCounter.gsm7bitChars = "@£$¥èéùìòÇ\\nØø\\rÅåΔ_ΦΓΛΩΠΨΣΘΞÆæßÉ !\\\"#¤%&'()*+,-./0123456789:;<=>?¡ABCDEFGHIJKLMNOPQRSTUVWXYZÄÖÑÜ§¿abcdefghijklmnopqrstuvwxyzäöñüà";
			SmsCounter.gsm7bitExChar = "\\^{}\\\\\\[~\\]|€";
			SmsCounter.gsm7bitRegExp = RegExp("^[" + SmsCounter.gsm7bitChars + "]*$");
			SmsCounter.gsm7bitExRegExp = RegExp("^[" + SmsCounter.gsm7bitChars + SmsCounter.gsm7bitExChar + "]*$");
			SmsCounter.gsm7bitExOnlyRegExp = RegExp("^[\\" + SmsCounter.gsm7bitExChar + "]*$");
			SmsCounter.GSM_7BIT = 'GSM_7BIT';
			SmsCounter.GSM_7BIT_EX = 'GSM_7BIT_EX';
			SmsCounter.UTF16 = 'UTF16';

			SmsCounter.messageLength = {
				GSM_7BIT: 160,
				GSM_7BIT_EX: 160,
				UTF16: 70
			};

			SmsCounter.multiMessageLength = {
				GSM_7BIT: 153,
				GSM_7BIT_EX: 153,
				UTF16: 67
			};

			SmsCounter.count = function (text)
			{
				var count, encoding, length, messages, per_message, remaining;

				encoding = this.detectEncoding(text);
				length = this.countGsmDictionnary(text, null);

				if (encoding === this.GSM_7BIT_EX) {
					length += this.countGsmDictionnary(text, this.gsm7bitExOnlyRegExp);
				}

				per_message = this.messageLength[encoding];
				if (length > per_message) {
					per_message = this.multiMessageLength[encoding];
				}

				messages = Math.ceil(length / per_message);
				remaining = (per_message * messages) - length;

				return count = {
					encoding: encoding,
					length: length,
					per_message: per_message,
					remaining: remaining,
					messages: messages
				};
			};

			SmsCounter.detectEncoding = function (text)
			{
				switch (false) {
					case text.match(this.gsm7bitRegExp) == null:
						return this.GSM_7BIT;
					case text.match(this.gsm7bitExRegExp) == null:
						return this.GSM_7BIT_EX;
					default:
						return this.UTF16;
				}
			};

			SmsCounter.countGsmDictionnary = function (text, dic)
			{
				var _i, _len, _results;
				_results = [];
				for (_i = 0, _len = text.length; _i < _len; _i++) {
					char2 = text[_i];
					// Bug : Firefox have chr(10) but not chr(13) !!!
					// So we force chr(13) add for each chr(10) found
					if (char2.charCodeAt(0) == 13)		continue;
					else if (char2.charCodeAt(0) == 10)	_results.push("\n");

					if (dic == null)					_results.push(char2);
					else if (char2.match(dic) != null)	_results.push(char2);
				}
				return _results.length;
			};

			return SmsCounter;

		})();

		if (typeof jQuery !== "undefined" && jQuery !== null)
		{
			$ = jQuery;

			$.fn.countSms = function (target)
			{
				var count_sms, input;
				input = this;
				target = $(target);

				count_sms = function ()
				{
					var count, k, v, _results;
					count = SmsCounter.count(input.val());
					_results = [];
					for (k in count) {
						v = count[k];
						_results.push(target.find("." + k).text(v));
					}
					return _results;
				};
				this.on('keyup', count_sms);
				return count_sms();
			};
		}

		updateValue();

		$('textarea').keyup(function ()
		{
			updateValue();
		});

		function updateValue()
		{
			var countSMS = SmsCounter.count($("#text_sms")[0].value);
			if ($("#text_sms")[0].value == '')
			{
				countSMS.length = 0;
				countSMS.remaining = 160;
				countSMS.messages = 0;
			}

			$("#count_character").text(countSMS.length);
			$("#count_remaining").text(countSMS.remaining);
			$("#count_messages").text(countSMS.messages);

			if (countSMS.length > 1)
				$("#text_characters").text("{l s='characters' mod='expressmailing'}");
			else
				$("#text_characters").text("{l s='character' mod='expressmailing'}");

			if (countSMS.remaining > 1)
				$("#remaining_characters").text("{l s='characters' mod='expressmailing'}");
			else
				$("#remaining_characters").text("{l s='character' mod='expressmailing'}");

			if (countSMS.messages > 1)
				$("#text_credits").text("{l s='credits' mod='expressmailing'}");
			else
				$("#text_credits").text("{l s='credit' mod='expressmailing'}");

		}

	});

</script>

<div style="margin: auto; width: 263px; height:463px; background-image: url(../modules/expressmailing/views/img/phoneskin.png); background-repeat: no-repeat; background-size: cover;">
	<textarea  id="text_sms" name="campaign_text" style="position: relative; top: 70px; left: 27px; height: 305px; width: 211px;">{$campaign_text|escape:'htmlall':'UTF-8'}</textarea>
</div>

<div style="margin: auto; padding: 1em 0em 1em 1em; text-align: left;">
	<div style="max-width: 17em; margin-left: auto; margin-right: auto;">
		<div>
			<span style="font-weight:bold; width: 90px; display: inline-block">{l s='Sms length :' mod='expressmailing'}</span> <span id="count_character"></span> <span id="text_characters"></span>
		</div>
		<div>
			<span style="font-weight:bold; width: 90px; display: inline-block">{l s='To the end :' mod='expressmailing'}</span> <span id="count_remaining"></span> <span id="remaining_characters"></span>
		</div>
		<div>
			<span style="font-weight:bold; width: 90px; display: inline-block">{l s='Cost :' mod='expressmailing'}</span> <span id="count_messages" style="color: red;"></span> <span id="text_credits" style="color: red;"></span> <span style="color:red;">sms</span>
		</div>
	</div>
</div>