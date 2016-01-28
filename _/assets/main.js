$(function () {
	$('.ui.captcha.modal').modal({
		blurring: true,
	});

	$('.ui.shorting.modal').modal({
		blurring: true,
		closable: false
	});

	$('form#short_it').on('submit', function (e) {
		e.preventDefault();
		var that = $(this);
		if ($('input[name=url]', that).val() == '')
			return;
		$('.ui.captcha.modal').modal('show');
	});
});

var recaptchaCallback = function (token) {
	var form = $('form#short_it');
	// $('.ui.shorting.modal').modal('show');
	$('input[name=captchaToken]', form).val(token);
	$.get(
		'_/api/short_it.php',
		form.serialize(),
		function (data) {
			if (data.status == 'ok') {
				// $('.ui.shorting.modal').modal('hide all');
				$('.ui.captcha.modal').modal('hide');
				$('#short_url #logo #shorten').html('/' + data.code);
				$('#short_url').addClass('shorten');
			}
		},
		'json'
	);
}
