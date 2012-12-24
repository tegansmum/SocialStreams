/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
jQuery(document).ready(function(){
    if(jQuery('.single-social_gallery').length)
    {
        initColorBox();
    }
    jQuery(window).bind('social_gallery_updated', function(){
        initColorBox();
    });
});
function initColorBox(){
    	jQuery.getScript("/wp-content/plugins/appsol_social_streams/js/jquery.colorbox-min.js", function(){
        jQuery('.appsol-social-stream-gallery .stream-item-photo-image-link').colorbox(
            {
                rel: '.appsol-social-stream-gallery .stream-item-photo-image-link',
                maxWidth: "90%",
                maxHeight: "90%",
                opacity: 0.6
            });
	});

}
appsolSocialStreamsUpdate = function(widgetId){
    jQuery.post(
	appsolSocialStreams.ajaxurl,
	{
	    'action' : 'update_social_streams_posts',
	    'postid' : appsolSocialStreams.postid,
	    'widgetid' : widgetId
	},
	function(data){
	    if(data){
		jQuery('#' + widgetId + ' .stream ul' ).fadeOut('fast', function(){
		    jQuery(this).html(data).fadeIn('fast')
		    
		})
	    }
	}
	);
    jQuery.post(
	appsolSocialStreams.ajaxurl,
	{
	    'action' : 'update_social_streams_connections',
	    'postid' : appsolSocialStreams.postid,
	    'widgetid' : widgetId
	},
	function(data){
	    if(data){
		jQuery('#' + widgetId + ' .connections ul' ).fadeOut('fast', function(){
		    jQuery(this).html(data).fadeIn('fast')
		    
		})
	    }
	}
	);
    jQuery.post(
	appsolSocialStreams.ajaxurl,
	{
	    'action' : 'update_social_streams_profiles',
	    'postid' : appsolSocialStreams.postid,
	    'widgetid' : widgetId
	},
	function(data){
	//	    alert(data);
	}
	);
    jQuery('.appsol-social-stream-gallery').each(function(){
	if(!jQuery(this).find('.stream-item-gallery').length)
	    jQuery(this).append('<img class="loader" src="' + appsolSocialStreams.pluginurl + 'images/ajax-loader.gif" style="display:block;margin:32px auto;" />')
	jQuery.post(
	    appsolSocialStreams.ajaxurl,
	    {
		'action' : 'update_social_streams_galleries',
		'user' : jQuery(this).find('.gallery-user').attr('id'),
		'network' : jQuery(this).find('.gallery-network').attr('id'),
		'gallery' : jQuery(this).attr('id')
	    },
	    function(data){
		if(data && !jQuery('.appsol-social-stream-gallery').find('.stream-item-gallery').length){
		    jQuery('.appsol-social-stream-gallery').remove('.gallery-user,.gallery-network,.loader').html(data).hide().fadeIn();
                    jQuery(window).trigger("social_gallery_updated");
		}
	    }
	    );
    })
    
    return false;
}