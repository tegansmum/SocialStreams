<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class appsolSocialStreamItem {

    public $id = 0;
    public $source = '';
    public $message = '';
    public $image = '';
    public $display_date = '';
    public $profile = array();
    public $profile_link = '';
    public $actions = array();
    protected $wraptag = 'li';
    protected $sources = array(
        'facebook' => array(
            'name' => 'Facebook',
            'class' => 'facebook'
        ),
        'twitter' => array(
            'name' => 'Twitter',
            'class' => 'twitter'
        ),
        'linkedin' => array(
            'name' => 'LinkedIn',
            'class' => 'linkedin'
        ),
        'gplus' => array(
            'name' => 'Google+',
            'class' => 'gplus'
        )
    );
    protected $url_pattern = '/\b((https?|ftp|file):\/\/|(www|ftp)\.)[-A-Z0-9+&@#\/%?=~_|$!:,.;]*/i';
    private $raw = null;

    function __construct($datetime, $wraptag = 'li') {
        $this->id = $datetime;
        $this->wraptag = $wraptag;
    }

    function setUpdate() {
        
    }

    function styleUpdate() {
        
    }

    function setUpdateActions() {
        
    }

    function display() {
        $html = '<' . $this->wraptag . ' class="stream-item ' . $this->source['class'] . '">';
        $html.= '<span class="profile-image"><a href="' . $this->profile->link . '" rel="nofollow" target="_blank" title="' . $this->profile->name . '\'s ' . $this->source['name'] . ' Profile' . '"><img width="48px" height="48px" src="' . $this->profile->image . '" /></a></span>';
        $html.= '<span class="message">' . $this->message . '</span>';
        $html.= '<span class="meta">Posted <span class="post-date">' . $this->display_date . '</span> by <a class="profile-name" href="' . $this->profile->link . '" rel="nofollow">' . $this->profile->name . '</a></span>';
        $html.= '<span class="actions">';
        foreach ($this->actions as $action)
            $html.= '<span class="action">' . $action . '</span>';
        $html.= '</span>';
        $html.= '</' . $this->wraptag . '>';
        return $html;
    }

    protected function getAge($date) {
        $datetime = is_numeric($date) ? $date : strtotime($date);
        $now = time();
        $age = $now - $datetime;
        $age_str = 'ago';
        if (floor($age / 60 / 60 / 24 / 7) > 0) {
            $weeks = floor($age / 60 / 60 / 24 / 7);
            $week_str = $weeks == 1 ? 'week' : 'weeks';
            $age_str = $weeks . ' ' . $week_str . ' ' . $age_str;
        } elseif (floor($age / 60 / 60 / 24) > 0) {
            $days = floor($age / 60 / 60 / 24);
            $day_str = $days == 1 ? 'day' : 'days';
            $age_str = $days . ' ' . $day_str . ' ' . $age_str;
        } elseif (floor($age / 60 / 60) > 0) {
            $hours = floor($age / 60 / 60);
            $hour_str = $hours == 1 ? 'hour' : 'hours';
            $age_str = $hours . ' ' . $hour_str . ' ' . $age_str;
        } else {
            $minutes = floor($age / 60);
            $minute_str = $minutes == 1 ? 'minute' : 'minutes';
            $age_str = $minutes . ' ' . $minute_str . ' ' . $age_str;
        }
        return $age_str;
    }

    protected function shortenLink($link) {
        $shortener = 'http://is.gd/create.php?';
        $short_format = 'format=simple';
        $long_url = 'url=' . $link;
        $fh = fopen($shortener . $short_format . '&' . $long_url, 'r');
        if ($fh) {
            $short_url = '';
            while (!feof($fh)) {
                $chunk = fgets($fh);
                $short_url.= $chunk;
            }
            fclose($fh);
            return $short_url;
        } else {
            return $link;
        }
    }

    protected function findLinks($text) {
        $urls = array();
        $match_count = preg_match_all($this->url_pattern, $text, $matches, PREG_SET_ORDER);
        if (!$match_count)
            return $urls;
        foreach ($matches as $match)
            $urls[] = $match[0];
        return $urls;
    }

}

class appsolFacebookWallItem extends appsolSocialStreamItem {

    function setUpdate($post, $fb_user) {
        $this->source = $this->sources['facebook'];
        if (isset($post['actions']))
            $this->setUpdateActions($post);
        $this->profile = new appsolFacebookProfile();
        $this->profile->setProfile($fb_user);
        $this->message = $this->styleUpdate($post);
        $this->display_date = $this->getAge($post['created_time']);
    }

    function styleUpdate($post) {
        $fb_text = '';
        $update = array(
            'type' => '',
            'name' => '',
            'message' => '',
            'icon' => '',
            'description' => '',
            'link' => '',
            'picture' => '',
            'story' => '',
            'caption' => '',
            'source' => '',
            'likes' => ''
        );
        $update = wp_parse_args($post, $update);
        if ($update['name'])
            $fb_text.= '<span class="stream-item-title fb-post-name">' . $update['name'] . '</span>';
        switch ($update['type']) {
            case 'link':
                if ($update['picture']) {
                    $fb_text.= '<span class="stream-item-link stream-item-photo fb-post-link">';
                    $fb_text.= '<a class="stream-item-photo-image-link" href="' . $update['link'] . '" target="_blank" rel="nofollow"><img src="' . $update['picture'] . '" alt="' . $update['caption'] . '" /></a>';
                    if ($update['caption'])
                        $fb_text.= '<span class="stream-item-photo-caption">' . $update['caption'] . '</span>';
                }else {
                    $fb_text.= '<span class="stream-item-link fb-post-link">';
                    $fb_text.= '<a href="' . $update['link'] . '" target="_blank" rel="nofollow">' . $update['name'] . '</a>';
                }
                $fb_text.= '</span>';
                break;
            case 'photo':
                $fb_text.= '<span class="stream-item-link stream-item-photo fb-post-photo">';
                $fb_text.= '<a href="' . $update['link'] . '" target="_blank" rel="nofollow"><img src="' . $update['picture'] . '" alt="' . $update['name'] . '" /></a>';
                $fb_text.= '</span>';
                if ($update['story'])
                    $fb_text.= $update['story'];
                break;
            case 'video':
                $fb_text.= '<span class="stream-item-link stream-item-video fb-post-video">';
                $fb_text.= '<a href="' . $update['link'] . '" target="_blank" rel="nofollow">';
                $fb_text.= '<img src="' . $update['picture'] . '" alt="' . $update['name'] . '" />';
                $fb_text.= '</a>';
                $fb_text.= '</span>';
                break;
        }
        $fb_text.= $update['description'] . ' ' . $update['message'];
        return trim($fb_text);
    }

    function setUpdateActions($post) {
        foreach ($post['actions'] as $action) {
            $tally = '';
            $actions = strtolower($action['name']) . 's';
            if (isset($post[$actions])) {
                $name = $post[$actions]['count'] > 1 ? $action['name'] . 's' : $action['name'];
                $tally = '<span class="tally"><span class="count">' . $post[$actions]['count'] . '</span> ' . $name . '</span>';
            }
            $this->actions[strtolower($action['name'])] = $tally . '<a class="stream-item-action ' . strtolower($action['name']) . '" href="' . $action['link'] . '" rel="nofollow" target="_blank">' . $action['name'] . '</a>';
        }
    }

}

class appsolFacebookGalleryItem extends appsolSocialStreamItem {

    public $images = array();

    function setUpdate($album, $fb_user) {
        $this->source = $this->sources['facebook'];
        $this->setUpdateActions($album);
        $this->profile = new appsolFacebookProfile();
        $this->profile->setProfile($fb_user);
        $this->id = $album['id'];
        if (is_array($album['photos']))
            foreach ($album['photos']['data'] as $image) {
                $datetime = strtotime($image['created_time']);
                $wraptag = $this->wraptag == 'dl' ? 'dd' : 'li';
                $this->images[$image['position']] = new appsolFacebookPhotoItem($datetime, $wraptag);
                $this->images[$image['position']]->setUpdate($image, $fb_user);
                if($image['id'] == $album['cover_photo'])
                    $this->image = $image['position'];
            }
        $this->data = array(
            'type' => '',
            'name' => '',
            'location' => '',
            'id' => '',
            'description' => '',
            'link' => '',
            'cover_photo' => '',
            'count' => ''
        );
        $this->data = wp_parse_args($album, $this->data);
        $this->message = $this->styleUpdate($album);
        $this->display_date = $this->getAge($album['created_time']);
    }

    function styleUpdate($album) {
        $html = '<div class="stream-item stream-item-gallery fb-gallery">';
        if ($this->data['name'])
            $html.= '<h3 class="stream-item-title fb-gallery-name">' . $this->data['name'] . '</h3>';
        $html.= '<' . $this->wraptag . '>[GALLERY]</' . $this->wraptag . '>';
        if (!empty($this->data['description']))
            $html.= '<p>' . $this->data['description'] . '</p>';
        $html.= '<p>';
        if (!empty($this->data['location']))
            $html.= 'Location: ' . $this->data['location'];
        $html.= '<span class="stream-item-link fb-gallery-link">';
        $html.= '<a href="' . $this->data['link'] . '" target="_blank" rel="nofollow">View on Facebook</a>';
        $html.= '</span></p>';
        $html.= '</div>';
        return trim($html);
    }
    
    function display(){
        $html = '';
        foreach ($this->images as $image)
            $html.= $image->display();
        return str_replace('[GALLERY]', $html, $this->message);
    }

    function setUpdateActions($album) {
        $this->actions['view-original'] = '<span class="stream-item-action view-original"><a class="stream-item-link fb-gallery-link" href="' . $album['link'] . '" rel="nofollow" target="_blank">' . $album['name'] . '</a></span>';
    }

}

class appsolFacebookPhotoItem extends appsolSocialStreamItem {

    public $thumbnail = '';

    function setUpdate($image, $fb_user) {
        $this->source = $this->sources['facebook'];
        $this->setUpdateActions($image);
        $this->profile = new appsolFacebookProfile();
        $this->profile->setProfile($fb_user);
        $this->id = $image['id'];
//        $this->thumbnail = $image['picture'];
        $this->data = array(
            'name' => '',
            'position' => '',
            'id' => '',
            'picture' => '',
            'source' => '',
            'height' => '',
            'width' => '',
            'link' => ''
        );
        $this->data = wp_parse_args($image, $this->data);
        $this->message = $this->styleUpdate($image);
        $this->display_date = $this->getAge($image['created_time']);
    }

    function styleUpdate($image) {
        $html = '';
        if ($this->wraptag == 'dd')
            $html.= '<dt class="stream-item-title fb-photo-name">' . $this->data['name'] . '</dt>';
        $html.= '<' . $this->wraptag . ' class="stream-item stream-item-photo fb-photo">';
        if ($this->wraptag != 'dd')
            $html.= '<span class="stream-item-title fb-photo-name">' . $this->data['name'] . '</span>';
        $html.= '<span class="stream-item-link stream-item-photo fb-photo-link">';
        $html.= '<a class="stream-item-photo-image-link" href="' . $this->data['source'] . '" title="' . $this->data['name'] . '" target="_blank" rel="nofollow"><img src="' . $this->data['picture'] . '" alt="' . $this->data['name'] . '" /></a>';
        $html.= '</span>';
        return trim($html);
    }

    function setUpdateActions($image) {
        $actions = array('like', 'comment');
        foreach ($actions as $action) {
            $tally = '';
            $actions = strtolower($action) . 's';
            if (isset($image[$actions])) {
                $count = count($image[$actions]['data']);
                $name = $count > 1 ? $action . 's' : $action;
                $tally = '<span class="tally"><span class="count">' . $count . '</span> ' . $name . '</span>';
            }
            $this->actions[$action] = $tally . '<a class="stream-item-action ' . $action . '" href="' . $image['link'] . '" rel="nofollow" target="_blank">' . $action . '</a>';
        }
    }

}

class appsolTweetItem extends appsolSocialStreamItem {

    function setUpdate($tweet) {
        $this->source = $this->sources['twitter'];
        $this->profile = new appsolTwitterProfile();
        $this->profile->setProfile($tweet->user);
        $this->setUpdateActions($tweet);
        $this->message = $this->styleUpdate($tweet);
        $this->display_date = $this->getAge($tweet->created_at);
    }

    function styleUpdate($tweet) {
        $used_hashtags = array();
        $used_urls = array();
        $used_users = array();
        $tweet_text = $tweet->text;
        if (is_array($tweet->entities->hashtags)) {
            foreach ($tweet->entities->hashtags as $hashtag) {
                if (!in_array($hashtag->text, $used_hashtags)) {
                    $tweet_text = str_ireplace('#' . $hashtag->text, '<a class="stream-item-link tw-hashtag" href="http://twitter.com/#!/search?q=%23' . $hashtag->text . '" rel="nofollow">#' . $hashtag->text . '</a>', $tweet_text);
                    $used_hashtags[] = $hashtag->text;
                }
            }
        }
        if (is_array($tweet->entities->urls)) {
            foreach ($tweet->entities->urls as $url) {
                if (!in_array($url->url, $used_urls)) {
                    $target = empty($url->expanded_url) ?
                            $url->url : $url->expanded_url;
                    $tweet_text = str_ireplace($url->url, '<a class="stream-item-link tw-link" href="' . $target . '" rel="nofollow">' . $url->url . '</a>', $tweet_text);
                    $used_urls[] = $url->url;
                }
            }
        }
        if (is_array($tweet->entities->user_mentions)) {
            foreach ($tweet->entities->user_mentions as $user) {
                if (!in_array($user->screen_name, $used_users)) {
                    $tweet_text = str_ireplace('@' . $user->screen_name, '<a class="stream-item-link tw-user" href="http://twitter.com/#!/' . $user->screen_name . '" title="' . $user->name . '" rel="nofollow">' . '@' . $user->screen_name . '</a>', $tweet_text);
                    $used_users[] = $user->screen_name;
                }
            }
        }
        return $tweet_text;
    }

    function setUpdateActions($tweet) {
        $tweet_text = substr('RT @' . $this->profile->user . ' ' . $tweet->text, 0, 140);
        $tally = '';
        if ($tweet->retweet_count > 0) {
            $name = $tweet->retweet_count > 1 ? 'retweets' : 'retweet';
            $tally = '<span class="tally"><span class="count">' . $tweet->retweet_count . '</span> ' . $name . '</span>';
        }
        $this->actions['retweet'] = '<a class="stream-item-action retweet" target="_blank" rel="nofollow" href="http://twitter.com/share?text=' . $tweet_text . '&via=' . $this->profile->user . '">Retweet</a>';
    }

}

class appsolLinkedinUpdateItem extends appsolSocialStreamItem {

    function setUpdate($update, $profile = null) {
        $this->source = $this->sources['linkedin'];
        $this->profile = new appsolLinkedinProfile();
        $this->profile->setProfile($update->updateContent->person);
        $this->setUpdateActions($update);
        $this->message = $this->styleUpdate($update);
        $this->display_date = $this->getAge(intval(substr($update->timestamp, 0, 10)));
    }

    function styleUpdate($update) {
        $li_text = '';
        if (isset($update->updateContent->person->headline))
            $li_text.= '<span class="stream-item-title li-headline">' . $update->updateContent->person->headline . '</span>';
        switch ($update->updateType) {
            case 'SHAR':
                if (isset($update->updateContent->person->currentShare->content)) {
                    $li_text.= '<span class="stream-item-link li-share-link">';
                    $li_text.= '<a href="' . $update->updateContent->person->currentShare->content->submittedUrl . '" target="_blank" rel="nofollow">' . $update->updateContent->person->currentShare->content->title . '</a>';
                    $li_text.= '</span>';
                    if (isset($update->updateContent->person->currentShare->content->submittedUrl)) {
                        if (isset($update->updateContent->person->currentShare->content->thumbnailUrl)) {
                            $li_text.= '<span class="stream-item-link stream-item-photo li-update-photo">';
                            $li_text.= '<a href="' . $update->updateContent->person->currentShare->content->submittedUrl . '" target="_blank" rel="nofollow"><img src="' . $update->updateContent->person->currentShare->content->thumbnailUrl . '" alt="' . $update->updateContent->person->currentShare->content->title . '" /></a>';
                            $li_text.= '</span>';
                        }
                    }
                    $share_text = '';
                    if (isset($update->updateContent->person->currentShare->content->description)) {
                        $share_text = $update->updateContent->person->currentShare->content->description;
                    }
                } elseif (isset($update->updateContent->person->currentShare->comment)) {
                    $share_text = $update->updateContent->person->currentShare->comment;
                }
                $urls = $this->findLinks($share_text);
                foreach ($urls as $url)
                    $share_text = str_ireplace($url, '<a class="stream-item-link li-link" href="' . $url . '" rel="nofollow">' . $url . '</a>', $share_text);
                $li_text.= $share_text;

                break;
            case 'STAT':
                $status_text = $update->updateContent->person->currentStatus;
                $urls = $this->findLinks($status_text);
                foreach ($urls as $url)
                    $status_text = str_ireplace($url, '<a class="stream-item-link li-link" href="' . $url . '" rel="nofollow">' . $url . '</a>', $status_text);
                $li_text.= $status_text;
                break;
            case 'VIRL':
                break;
            default:
        }

        return $li_text;
    }

    function setUpdateActions($update) {
        $comment_tally = '';
        $like_tally = '';
        if (isset($update->updateComments)) {
            $name = $update->updateComments->_total > 1 ? 'comments' : 'comment';
            $comment_tally = '<span class="tally"><span class="count">' . $update->updateComments->_total . '</span> ' . $name . '</span>';
        }
        if (isset($update->likes)) {
            $name = $update->likes->_total > 1 ? 'likes' : 'like';
            $like_tally = '<span class="tally"><span class="count">' . $update->likes->_total . '</span> ' . $name . '</span>';
        }
        $this->actions['comment'] = $comment_tally;
        $this->actions['like'] = $like_tally;
    }

}

class appsolGooglePlusActivityItem extends appsolSocialStreamItem {

    function setUpdate($activity) {
        $this->source = $this->sources['gplus'];
        $this->profile = new appsolGooglePlusProfile();
        $this->profile->setProfile($activity['actor']);
        $this->setUpdateActions($activity);
        $this->message = $this->styleUpdate($activity);
        $this->display_date = $this->getAge($activity['updated']);
    }

    function styleUpdate($activity) {
        $activity_text = '';
        $attachment = null;
        if (isset($activity['object']['attachments']) && count($activity['object']['attachments'])) {
            $attachment = array(
                'title' => '',
                'content' => '',
                'url' => '',
                'image' => ''
            );
            foreach ($activity['object']['attachments'] as $gplus_attachment) {
                switch ($gplus_attachment['objectType']) {
                    case 'article':
                        $attachment['title'] = $gplus_attachment['displayName'];
                        if (isset($gplus_attachment['content']))
                            $attachment['content'] = $gplus_attachment['content'];
                        $attachment['url'] = $gplus_attachment['url'];
                        break;
                    case 'photo':
                        $attachment['image'] = $gplus_attachment['image']['url'];
                        if (isset($gplus_attachment['url']))
                            $attachment['url'] = $gplus_attachment['url'];
                        if (isset($gplus_attachment['content']))
                            $attachment['content'] = $gplus_attachment['content'];
                        break;
                    default:
                        break;
                }
            }
            $activity_text.= '<span class="stream-item-link gp-activity-link">';
            $activity_text.= '<a href="' . $activity['url'] . '" target="_blank" rel="nofollow">' . $activity['title'] . '</a>';
            $activity_text.= '</span>';
            if ($attachment) {
                if ($attachment['image'] != '') {
                    $activity_text.= '<span class="stream-item-link stream-item-photo gp-activity-photo">';
                    $activity_text.= '<a href="' . $attachment['url'] . '" target="_blank" rel="nofollow"><img src="' . $attachment['image'] . '" alt="' . $attachment['title'] . '" /></a>';
                    $activity_text.= '</span>';
                    if ($attachment['content'] != '')
                        $activity_text.= '<span class="stream-item-photo-caption ">' . $attachment['content'] . '</span>';
                } else {
                    if ($attachment['content'] != '') {
                        $activity_text.= '<span class="stream-item-link gp-attachment-link">';
                        $activity_text.= '<a href="' . $attachment['url'] . '" target="_blank" rel="nofollow">' . $attachment['content'] . '</a>';
                        $activity_text.= '</span>';
                    }
                }
            }
        }
        $activity_text.= $activity['object']['content'];
        return $activity_text;
    }

    function setUpdateActions($activity) {
        $share_tally = '';
        $plusone_tally = '';
        $comment_tally = '';
        if ($activity['object']['resharers']['totalItems'] > 0) {
            $name = $activity['object']['resharers']['totalItems'] > 1 ? 'shares' : 'share';
            $share_tally = '<span class="tally"><span class="count">' . $activity['object']['resharers']['totalItems'] . '</span> ' . $name . '</span>';
        }
        if ($activity['object']['plusoners']['totalItems'] > 0) {
            $name = $activity['object']['plusoners']['totalItems'] > 1 ? '+1s' : '+1';
            $plusone_tally = '<span class="tally"><span class="count">' . $activity['object']['plusoners']['totalItems'] . '</span> ' . $name . '</span>';
        }
        if ($activity['object']['replies']['totalItems'] > 0) {
            $name = $activity['object']['replies']['totalItems'] > 1 ? 'comments' : 'comment';
            $comment_tally = '<span class="tally"><span class="count">' . $activity['object']['replies']['totalItems'] . '</span> ' . $name . '</span>';
        }
        $this->actions['share'] = $share_tally;
        $this->actions['plusone'] = $plusone_tally;
        $this->actions['reply'] = $comment_tally;
    }

}

?>
