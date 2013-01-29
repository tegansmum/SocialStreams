<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */


function appsol_social_streams_gallery_create_meta() {
    add_meta_box('appsol_social_streams_gallery', __('Gallery'), 'appsol_social_streams_gallery_meta', 'social_gallery', 'normal', 'high');
}

function appsol_social_streams_gallery_save_meta($post_id) {
    if (isset($_POST['appsol_social_streams_gallery_network']))
        update_post_meta($post_id, '_appsol_social_streams_gallery_network', strip_tags($_POST['appsol_social_streams_gallery_network']));
    if (isset($_POST['appsol_social_streams_gallery_user']))
        update_post_meta($post_id, '_appsol_social_streams_gallery_user', strip_tags($_POST['appsol_social_streams_gallery_user']));
    if (isset($_POST['appsol_social_streams_gallery_album'])) {
        update_post_meta($post_id, '_appsol_social_streams_gallery_album', strip_tags($_POST['appsol_social_streams_gallery_album']));
        $network = get_post_meta($post_id, '_appsol_social_streams_gallery_network', true);
        $user = get_post_meta($post_id, '_appsol_social_streams_gallery_user', true);
        $gallery = get_post_meta($post_id, '_appsol_social_streams_gallery_album', true);
        switch ($network) {
            case 'fb':
                $cachetype = 'appsolFacebookAlbumCache';
                break;
            case 'gp':
                $cachetype = 'appsolGoogleplusAlbumCache';
                break;
            case 'fk':
                $cachetype = 'appsolFlickrAlbumCache';
                break;
            default:
                $cachetype = null;
        }
        $album = new $cachetype(array($network . '_user_id' => $user, $network . '_album_id' => $gallery));
        foreach ($album->cache->images as $image) {
            if ($image->id == $album->cache->data['cover_photo']) {
                $wp_upload_dir = wp_upload_dir();
                $large_image_dim = array(
                    'height' => get_option('large_size_h'),
                    'width' => get_option('large_size_w'),
                );
                // Check the filetype
                $headers = get_headers($image->data['source'], 1);
                switch (strtolower($headers['Content-Type'])) {
                    case 'image/png':
                        $suffix = 'png';
                        break;
                    case 'image/gif':
                        $suffix = 'gif';
                        break;
                    case 'image/jpg':
                    case 'image/jpeg':
                        $suffix = 'jpg';
                        break;
                    default:
                        $suffix = 'jpg';
                        break;
                }
                $temp_filename = $wp_upload_dir['path'] . $network . '-' . $gallery . '-' . $image->id . '.' . $suffix;
                file_put_contents($temp_filename, file_get_contents($image->data['source']));
                $filename = image_resize($temp_filename, $large_image_dim['width'], $large_image_dim['height'], false, null, $wp_upload_dir['path']);
                $filetype = wp_check_filetype(basename($filename), null);
                $attachment = array(
                    'guid' => $wp_upload_dir['baseurl'] . _wp_relative_upload_path($filename),
                    'post_mime_type' => $filetype['type'],
                    'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
                    'post_content' => '',
                    'post_status' => 'inherit'
                );
                $attach_id = wp_insert_attachment($attachment, $filename, $post_id);
                // you must first include the image.php file
                // for the function wp_generate_attachment_metadata() to work
                require_once(ABSPATH . 'wp-admin/includes/image.php');
                $attach_data = wp_generate_attachment_metadata($attach_id, $filename);
                wp_update_attachment_metadata($attach_id, $attach_data);
                update_post_meta($post_id, '_thumbnail_id', $attach_id);
            }
        }
    }
}

function appsol_social_streams_gallery_meta($post) {
    wp_nonce_field(plugin_basename(__FILE__), 'appsol_social_streams_gallery_nonce');
    $network = get_post_meta($post->ID, '_appsol_social_streams_gallery_network', true);
    $user = get_post_meta($post->ID, '_appsol_social_streams_gallery_user', true);
    $gallery = get_post_meta($post->ID, '_appsol_social_streams_gallery_album', true);
    ?>
    <script type="text/javascript"></script>
    <table class="form-table">
        <tbody>
            <tr>
                <th scope="row"><?php _e('Social Media Network', 'appsol_social_streams'); ?></th>
                <td>
                    <label for="appsol_social_streams_gallery_network_fb"><input type="radio" id="appsol_social_streams_gallery_network_fb" name="appsol_social_streams_gallery_network" value="fb"<?php if ($network == 'fb') echo ' checked="checked"'; ?> /><?php _e('Facebook', 'appsol_social_streams'); ?></label><br />
                    <label for="appsol_social_streams_gallery_network_fk"><input disabled="disabled" type="radio" id="appsol_social_streams_gallery_network_fk" name="appsol_social_streams_gallery_network" value="fk"<?php if ($network == 'fk') echo ' checked="checked"'; ?> /><?php _e('Flickr', 'appsol_social_streams'); ?></label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="appsol_social_streams_gallery_user"><?php _e('Social Media User', 'appsol_social_streams'); ?></label>
                </th>
                <td>
                    <select id="appsol_social_streams_gallery_user" name="appsol_social_streams_gallery_user">
                        <option value="0">Please Select</option>
                        <?php if ($network): ?>
                            <?php $users = get_option('appsol_social_streams_' . $network . '_users'); ?>
                            <?php foreach ($users as $user_id => $user_name): ?>
                                <option value="<?php echo $user_id; ?>"<?php if ($user_id == $user) echo ' selected="selected"'; ?>><?php echo $user_name; ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="appsol_social_streams_gallery_album"><?php _e('Social Media Gallery', 'appsol_social_streams'); ?></label>
                </th>
                <td>
                    <select id="appsol_social_streams_gallery_album" name="appsol_social_streams_gallery_album" <?php if (!$user) echo 'disabled="disabled"' ?>>
                        <option value="0">Please Select</option>
                        <?php if ($user): ?>
                            <?php $cachetype = $network == 'fb' ? 'appsolFacebookAlbumsCache' : 'appsolFlickrAlbumsCache'; ?>
                            <?php $cache = new $cachetype(array($network . '_user_id' => $user)); ?>
                            <?php if (!$cache->cache) $cache->updateCache(); ?>
                            <?php
                            ?>
                            <?php if (is_array($cache->cache)): ?>
                                <?php foreach ($cache->cache as $album): ?>
                                    <option value="<?php echo $album['id']; ?>"<?php if ($album['id'] == $gallery) echo ' selected="selected"'; ?>><?php echo $album['name']; ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        <?php endif; ?>
                    </select>
                </td>
            </tr>
            <tr id="appsol_social_streams_gallery_album_preview">
                <th scope="row">Images</th>
                <td>
                    <?php if ($gallery): ?>
                        <div class="gallery-preview" id="<?php echo $gallery; ?>">
                            <?php
                            $cachetype = $network == 'fb' ? 'appsolFacebookAlbumCache' : 'appsolFlickrAlbumCache';
                            $album = new $cachetype(array($network . '_user_id' => $user, $network . '_album_id' => $gallery));
                            if (!$album->cache)
                                $album->updateCache();
                            $html.= $album->cache->message;
                            foreach ($album->cache->images as $image)
                                $image_html.= $image->message;
                            $html = str_replace('[GALLERY]', $image_html, $html);
                            echo $html;
                            ?>
                        </div>
                    <?php endif; ?>
                </td>
            </tr>
        </tbody>
    </table>
    <?php
}

function appsol_social_streams_add_gallery_filter($content) {
    global $post;
    if ($post->post_type != 'social_gallery')
        return $content;

    $network = get_post_meta($post->ID, '_appsol_social_streams_gallery_network', true);
    $user = get_post_meta($post->ID, '_appsol_social_streams_gallery_user', true);
    $gallery = get_post_meta($post->ID, '_appsol_social_streams_gallery_album', true);
    switch ($network) {
        case 'fb':
            $cachetype = 'appsolFacebookAlbumCache';
            break;
        case 'gp':
            $cachetype = 'appsolGoogleplusAlbumCache';
            break;
        case 'fk':
            $cachetype = 'appsolFlickrAlbumCache';
            break;
        default:
            $cachetype = null;
    }
    if ($cachetype && $user) {
        $album = new $cachetype(array($network . '_user_id' => $user, $network . '_album_id' => $gallery));
        $html = '<div class="appsol-social-stream-gallery" id="' . $gallery . '">';
        $html.= '<span class="gallery-user" id="' . $user . '"></span><span class="gallery-network" id="' . $network . '"></span>';
        $html.= $album->cache->message;
        foreach ($album->cache->images as $image)
            $image_html.= $image->message;
        $html = str_replace('[GALLERY]', $image_html, $html);
        $html.= '</div>';
        $content = $content . $html;
        return $content;
    }
}

function appsol_social_streams_add_social_gallery_post_type_to_query($request) {
    if (!is_admin()) {
        $query = new WP_Query();
        $query->parse_query($request);
        $post_types = get_post_types(array('public' => true));
        $post_types = array_merge($post_types, array('social_gallery'));
        $request['post_type'] = $post_types;
    }
    return $request;
}

add_action('add_meta_boxes', 'appsol_social_streams_gallery_create_meta');
add_action('save_post', 'appsol_social_streams_gallery_save_meta');
//add_action('pre_get_posts', 'appsol_social_streams_add_social_gallery_post_type_to_query');
add_filter('request', 'appsol_social_streams_add_social_gallery_post_type_to_query');
add_filter('the_content', 'appsol_social_streams_add_gallery_filter');
?>
