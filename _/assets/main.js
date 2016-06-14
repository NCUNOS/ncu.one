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

	var formErrors = null;
	$('form#short_it').form({
		on: 'submit',
		revalidate: false,
		keyboardShortcuts: false,
		fields: {
			url: {
				identifier: 'url',
				rules: [
					{
						type: 'empty',
						prompt: '請輸入要縮短的網址'
					},
					{
						type: 'url',
						prompt: '請輸入有效的網址'
					},
					{
						type: 'minLength[18]', // https://ncu.one/_ => 17
						prompt: '太短的網址就不能再縮小囉 >~<'
					}
				]
			}
		},
		onFailure: function (errors, fields) {
			formErrors = errors;
		},
		onSuccess: function (e, fields) {
			formErrors = null;
		}
	});

	$('form#short_it').on('submit', function (e) {
		e.preventDefault();
		var that = $(this);
		var regex = /^https?:\/\//;
		var url = $('input[name=url]', that).val();
		if (!regex.test(url))
			$('input[name=url]', that).val('http://' + url);

		that.form('validate form');
		if (!that.form('is valid')) {
			alert(formErrors[0]);
			return;
		}
		$('.ui.captcha.modal').modal('show');
	});

	new Clipboard('#short_url .clipboard.icon', {
		text: function(trigger) {
			return 'https://ncu.one' + $('#short_url #logo #shorten').text();
		}
	}).on('success', function(e) {
		$('#short_url .clipboard.icon').popup('change content', 'Copied!');
	});

	// start shorting if url queried
	var url = URI(window.location.search).query(true).url;
	if (url != undefined) {
		var that = $('form#short_it');
		$('input[name=url]', that).val(url);
		that.submit();
	}
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

						// send url to callback if callback queried
						var callback = URI(window.location.search).query(true).callback;
						if (callback != undefined) {
							window.location.href = callback + '?short_url=' + URI.encode(data.url);
						}
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
