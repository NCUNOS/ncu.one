$(function () {
	$('#long_url button.icon.circle-right').on('click', function () {
		if ($('#long_url input').val() == '')
			return;
		$('#short_url #logo #shorten').html('/TeST');
		$('#short_url').addClass('shorten');
	});
});
