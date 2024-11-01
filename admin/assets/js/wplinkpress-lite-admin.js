jQuery(document).ready(function ($) {

	$('.copy-to-clipboard input').click(function() {
		$(this).focus();
		$(this).select();
		document.execCommand('copy');
		$('.copied').text("Copied to clipboard").show().fadeOut(1200);
	});



});