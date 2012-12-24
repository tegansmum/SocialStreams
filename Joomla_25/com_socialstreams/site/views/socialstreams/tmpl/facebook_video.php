<?php
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
<?php if ($post->name): ?>
            <span class="stream-item-title fb-post-name"><?php echo $post->name; ?></span>
            <?php endif; ?>
        switch ($post->type) {
            case 'link':
                if ($post->picture) {
                    $fb_text.= '<span class="stream-item-link stream-item-photo fb-post-link">';
                    $fb_text.= '<a class="stream-item-photo-image-link" href="' . $post->link . '" target="_blank" rel="nofollow"><img src="' . $post->picture . '" alt="' . $post->caption . '" /></a>';
                    if ($post->caption)
                        $fb_text.= '<span class="stream-item-photo-caption">' . $post->caption . '</span>';
                }else {
                    $fb_text.= '<span class="stream-item-link fb-post-link">';
                    $fb_text.= '<a href="' . $post->link . '" target="_blank" rel="nofollow">' . $post->name . '</a>';
                }
                $fb_text.= '</span>';
                break;
            case 'photo':
                $fb_text.= '<span class="stream-item-link stream-item-photo fb-post-photo">';
                $fb_text.= '<a href="' . $post->link . '" target="_blank" rel="nofollow"><img src="' . $post->picture . '" alt="' . $post->name . '" /></a>';
                $fb_text.= '</span>';
                if ($post->story)
                    $fb_text.= $post->story;
                break;
            case 'video':
                $fb_text.= '<span class="stream-item-link stream-item-video fb-post-video">';
                $fb_text.= '<a href="' . $post->link . '" target="_blank" rel="nofollow">';
                $fb_text.= '<img src="' . $post->picture . '" alt="' . $post->name . '" />';
                $fb_text.= '</a>';
                $fb_text.= '</span>';
                break;
        }
        $fb_text.= $post->description . ' ' . $post->message;