/**
 * 
 * @author alexhermann
 *
 */
jQuery(document).ready(function($){
	$('#awd_fcbk_option_send_notifications').live('submit', function(e){
		e.preventDefault();
		var $button = $('#awd_fcbk_option_send_notifications #send_notifications');
		$('#send_notifications_success').html('').hide();
		$('#send_notifications_errors').html('').hide();
		$.post(awd_fcbk.ajaxurl, $('#awd_fcbk_option_send_notifications').serialize()+"&action=send_notifications", function(data){
			$button.button('reset');
			if(data.success == 1){
				$('#send_notifications_success').html(data.message);
				$('#send_notifications_success').slideDown('normal', function(){
					$(this).delay(6000).fadeOut('normal', function(){
						$(this).html('');
					});
				});
			}else{
				$('#send_notifications_errors').html(data.error);
				$('#send_notifications_errors').slideDown('normal', function(){
					$(this).delay(6000).fadeOut('normal', function(){
						$(this).html('');
					});
				});
			}
		},"json");
	});
	$('#awd_fcbk_option_send_notifications #send_notifications').live('click',function(e){
		e.preventDefault();
		$(this).button('loading');
		$('#awd_fcbk_option_send_notifications').submit();
	});
});