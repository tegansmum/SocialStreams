/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
jQuery.noConflict()
jQuery(document).ready(function($){
    $('#jform_request_access_token_network_connect').click(function(){
	switch($('#jform_network').val()){
	    // Facebook Connect
	    case 'facebook':
		if(typeof requestUrls.facebook != 'undefined'){
		    $('#jform_request_access_token_network_connect').attr('href', requestUrls.facebook)
		}
		    
		// Facebook Login
		function FbLogin() {
		    FB.login(function(response) {
			if (response.authResponse) {
			    // connected
			    $('#jform_access_token_id').val(response.authResponse.accessToken)
			    FbSuccessMsg()
			} else {
			// cancelled
			    FbFailMsg(response)
			}
		    }, {scope: 'read_stream,publish_stream'});
		}
		// Test
		function FbSuccessMsg() {
		    FB.api('/me', function(response) {
			$('#jform_message').val('Authenticated with Facebook as ' + response.name + '.');
		    });
		}
		function FbFailMsg(response) {
			$('#jform_message').val('Facebook Authentication aborted ' + response.name + '.');
		}
		break;
	    case 'twitter':
	    
		break;
	    case 'linkedin':
	    
		break;
	    case 'google':
	    
		break;
	    default:
	    
	}
    })
})

