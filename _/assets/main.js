$(function () {
	$('form#short_it').on('submit', function (e) {
		e.preventDefault();
		var that = $(this);
		if ($('input[name=url]', that).val() == '')
			return;
		$('#short_url #logo #shorten').html('/TeST');
		$('#short_url').addClass('shorten');
	});
});
