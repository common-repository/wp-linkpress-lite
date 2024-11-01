jQuery(document).ready(function ($) {

	function isMobileDevice() {
        return (typeof window.orientation !== "undefined") || (navigator.userAgent.indexOf('IEMobile') !== -1);
	};

	var isMobile = isMobileDevice();
	
	// Get the user agent string
	var deviceAgent = navigator.userAgent;
	// Set var to0 iOS device name or null
	var ios = deviceAgent.toLowerCase().match(/(iphone|ipod|ipad)/);
	
	window.onunload = refreshParent;
    function refreshParent() {
        window.opener.location.reload();
    }

	function getParameterByName(name, url) {
        if (!url) url = window.location.href;
        name = name.replace(/[\[\]]/g, "\\$&");
        var regex = new RegExp("[?&]" + name + "(=([^&#]*)|&|#|$)"),
            results = regex.exec(url);
        if (!results) return null;
        if (!results[2]) return '';
        return decodeURIComponent(results[2].replace(/\+/g, " "));
    }

	var currentPage = window.location.href;

	if(currentPage.indexOf("code") !== -1) {
		var code = getParameterByName('code');

		$.ajax({
			url: wplinkpress.ajaxurl,
			type: "POST",
			data: {action: 'authorize_linkedin_oauth','authorize_code':code},
			success: function(returned) {
				//unload the current window
				if(isMobile || ios) {
					window.location.href = returned.data;
				} else {
					self.close();
				}
			}
		});
	}

	function checkButtonState(val) {
		if(val == '') {
			$('#post_comment').attr('disabled', 'disabled');
			$('#authorize_comment').attr('disabled', 'disabled');
		} else {
			$('#post_comment').removeAttr('disabled');
			$('#authorize_comment').removeAttr('disabled');
		}
	}
	var val = $('#wplinkpress-comment-text').val();
	checkButtonState(val);

	$('#wplinkpress-comment-text').on('change keyup paste', function() {
		var val  = $(this).val();
		checkButtonState(val);
	});

	$("#post_comment").click(function(e){
		e.preventDefault(); 
		$('#post_comment').attr('disabled', 'disabled');
		var profileid = $('#current-comment-profileid').val();
		var text = $('#wplinkpress-comment-text').val();
		var firstname = $('#current-comment-firstname').val();
		var lastname = $('#current-comment-lastname').val();
		var imageurl = $('#current-comment-imageurl').val();
		var postid = $('#wplinkpress-post-id').val();
		var self = $('input[type=checkbox]#toggle-linkedin-feed');

		var feed;
		if($(self).is(':checked')) {
			feed='yes';
		}else{
			feed='no';
		}
		if(text.length > 0 && (firstname.length > 0 || lastname.length > 0) ){
			$.ajax({
				url: wplinkpress.ajaxurl,
				type: "POST",
				data: {action: 'post_comment','text':text, 'profileid':profileid, 'firstname':firstname, 'lastname':lastname, 'imageurl': imageurl,'feed': feed, 'post_id': postid },
				success: function(returned) {
					var data = JSON.parse(returned);
						$('#add-wplinkpress-comment').after(data);
						$('#wplinkpress-comment-text').val('');
						$('#post_comment').removeAttr('disabled');
				}
			});
		}  

});

	$('#wplinkpress-logout').click(function(e){
		e.preventDefault();
		var text = $('#wplinkpress-comment-text').val();
		var self = $('input[type=checkbox]#toggle-linkedin-feed');
		var feed;
		if($(self).is(':checked')) {
			feed='yes';
		}else{
			feed='no';
		}
		$.ajax({
			url: wplinkpress.ajaxurl,
			type: "GET",
			data: {action: 'wplinkpress_logout', 'comment_text' : text, 'feed': feed, 'linkedin-logout': 'true'},
			success: function(returned){
				window.location.reload();
			}
		})
	});
	$('#authorize_comment').click(function(e){
		e.preventDefault();
		$('#authorize_comment').attr('disabled', 'disabled');
		var link = $(this).attr('href');
		var authorize_url = $('#wplinkpress-authorize-url').val();
		var text = $('#wplinkpress-comment-text').val();
		var self = $('input[type=checkbox]#toggle-linkedin-feed');
		var current_url = $(location).attr("href");
		var feed;
		if($(self).is(':checked')) {
			feed='yes';
		}else{
			feed='no';
		}
		if(text.length > 0){
			$.ajax({
				url: wplinkpress.ajaxurl,
				type: "POST",
				data: {action: 'add_comment_session', 'comment_text': text, 'feed': feed, 'current_url': current_url},
				success: function(returned) {
					//window.location.href = authorize_url;
					if(isMobile || ios)
					window.location.href = authorize_url;
					else
					window.open(authorize_url,"Linkedin Authorization","width=600,height=400,scrollbars=no");
				}
			});
		}
	});

	
});