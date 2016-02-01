$(function () {
	$('.ui.modal').modal({
		blurring: true,
		closable: false
	});

	$('#short_url .clipboard.icon').popup({
		onShow: function () {
			$('#short_url .clipboard.icon').popup('change content', 'Copy');
		}
	});

	$('form#short_it').on('submit', function (e) {
		e.preventDefault();
		var that = $(this);
		if ($('input[name=url]', that).val() == '')
			return;
		var regex = /^https?:\/\//;
		var url = $('input[name=url]', that).val();
		if (!regex.test(url))
			$('input[name=url]', that).val('http://' + url);
		$('.ui.captcha.modal').modal('show');
	});

	new Clipboard('#short_url .clipboard.icon', {
		text: function(trigger) {
			return 'https://ncu.one' + $('#short_url #logo #shorten').text();
		}
	}).on('success', function(e) {
		$('#short_url .clipboard.icon').popup('change content', 'Copied!');
	});
});

var recaptchaCallback = function (token) {
	var form = $('form#short_it');
	$('.ui.shorting.modal')
	.modal({
		blurring: true,
		closable: false,
		onVisible: function () {
			grecaptcha.reset();
			$('input[name=captchaToken]', form).val(token);
			$.post(
				'_/api/short_it.php',
				form.serialize(),
				function (data) {
					if (data.status == 'ok') {
						$('.ui.shorting.modal').modal('hide');
						$('#short_url #logo #shorten').html('/' + data.code);
						$('#short_url').addClass('shorten');
					}
					if (data.status == 'failed') {
						alert("縮網址失敗！請確定您輸入的是有效的網址（http, https），或稍後再重試。\n請注意已經夠短的網址就不能再縮囉 >.0b");
						$('.ui.shorting.modal').modal('hide');
					}
				},
				'json'
			);
		}
	})
	.modal('show');
}
