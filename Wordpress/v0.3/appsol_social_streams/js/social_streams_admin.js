/* 
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
jQuery(document).ready(function(){
    jQuery('#appsol_social_streams_gallery input[name=appsol_social_streams_gallery_network]:not(:checked)').click(function(){
	jQuery.post(
	    ajaxurl,
	    {
		'action' : 'appsol_social_streams_users',
		'network' : jQuery(this).val()
	    },
	    function(data){
		if(data){
		    var options = '<option value="0">Please Select</option>'
		    for(user in data.users){
			options+= '<option value="' + user + '">' + data.users[user] + '</option>'
		    }
		    jQuery('#appsol_social_streams_gallery #appsol_social_streams_gallery_user option' ).fadeOut('fast', function(){
			jQuery(this).parent().html(options).fadeIn('fast')
		    })
		    
		}
	    }
	    );
    })
    jQuery('#appsol_social_streams_gallery #appsol_social_streams_gallery_user').change(function(){
	jQuery.post(
	    ajaxurl,
	    {
		'action' : 'appsol_social_streams_cache',
		'network' : jQuery('#appsol_social_streams_gallery input[name=appsol_social_streams_gallery_network]:checked').val(),
		'cache' : 'albums',
		'user' : jQuery(this).val(),
		'update' : true
	    },
	    function(data){
		if(data.cache){
		    var options = '<option value="0">Please Select</option>'
		    //		    var galleryStore = jQuery('#appsol_social_streams_gallery').append('<div id="appsol_social_streams_gallery_store"></div>').find('#appsol_social_streams_gallery_store').hide()
		    for(album in data.cache){
			options+= '<option value="' + data.cache[album].id + '">' + data.cache[album].name + '</option>'
		    //			var albumHtml = data.cache[album].message
		    //			var imageHtml = ''
		    //			for(imagePosition in data.cache[album].images){
		    //			    imageHtml += data.cache[album].images[imagePosition].message
		    //			}
		    //			jQuery('#appsol_social_streams_gallery_album_preview td').append('<div class="gallery-preview" id="' + data.cache[album].id + '">' + albumHtml.replace('[GALLERY]', imageHtml) + '</div>').find('#' + data.cache[album].id).hide()
		    }
		    jQuery('#appsol_social_streams_gallery #appsol_social_streams_gallery_album option' ).fadeOut('fast', function(){
			jQuery(this).parent().removeAttr('disabled').html(options).fadeIn('fast')
		    })
		}
	    })
	return false
    })
    jQuery('#appsol_social_streams_gallery #appsol_social_streams_gallery_album').change(function(){
	var albumId = jQuery(this).val()
	if(jQuery('#appsol_social_streams_gallery_album_preview td #' + albumId).length){
	    jQuery('#appsol_social_streams_gallery_album_preview td .gallery-preview:visible').fadeOut(function(){
		jQuery('#appsol_social_streams_gallery_album_preview td #' + albumId).fadeIn()
	    })
	    
	} else {
	    if(jQuery('#appsol_social_streams_gallery_album_preview td .gallery-preview:visible').length){
		jQuery('#appsol_social_streams_gallery_album_preview td .gallery-preview:visible').fadeOut(function(){
		    jQuery(this).parent('td').append('<img class="loader" src="' + '/wp-content/plugins/appsol_social_streams/images/ajax-loader.gif" style="display:block;margin:32px auto;" />')
		})
	    } else {
		jQuery('#appsol_social_streams_gallery_album_preview td').append('<img class="loader" src="' + '/wp-content/plugins/appsol_social_streams/images/ajax-loader.gif" style="display:block;margin:32px auto;" />')
	    }
	    
	    jQuery.post(
		ajaxurl,
		{
		    'action' : 'appsol_social_streams_cache',
		    'network' : jQuery('#appsol_social_streams_gallery input[name=appsol_social_streams_gallery_network]:checked').val(),
		    'cache' : 'album',
		    'user' : jQuery('#appsol_social_streams_gallery #appsol_social_streams_gallery_user').val(),
		    'album' : jQuery(this).val(),
		    'update' : true
		},
		function(data){
		    if(data.cache){
			var albumHtml = data.cache.message
			var imageHtml = ''
			for(imagePosition in data.cache.images){
			    imageHtml += data.cache.images[imagePosition].message
			}
			jQuery('#appsol_social_streams_gallery_album_preview td').append('<div class="gallery-preview" id="' + data.cache.id + '">' + albumHtml.replace('[GALLERY]', imageHtml) + '</div>').find('#' + data.cache.id).hide()
			jQuery('#appsol_social_streams_gallery_album_preview td .loader').fadeOut(function(){
			    jQuery(this).remove();
			    jQuery('#appsol_social_streams_gallery_album_preview td #' + data.cache.id).fadeIn()
			})
			
		    }
		})
	}
    })
})
