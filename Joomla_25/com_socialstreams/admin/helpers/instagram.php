<?php

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once(dirname(__FILE__) . '/socialstreams.php');

class instagramHelper {

    public static $client;
    public static $server = 'Instagram';
    public static $redirect_uri = '';
    public static $scope = 'read_stream,publish_stream';
    public static $last_error = '';
    public static $debug = 1;

    public static function setup($userid = null) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'instagramHelper::setup'));
        self::$client = new SocialStreamsInstagram();
        self::$client->user = $userid;
        self::$client->debug = self::$debug;
        self::$client->server = self::$server;
        self::$client->scope = self::$scope;
        self::$client->redirect_uri = JURI::base() . 'index.php?option=com_socialstreams&task=socialstream.setauth&network=instagram';

        if (strlen(SocialStreamsHelper::getParameter('instagram_appkey')) == 0 || strlen(SocialStreamsHelper::getParameter('instagram_appsecret')) == 0)
            return false;
        self::$client->client_id = SocialStreamsHelper::getParameter('instagram_appkey');
        self::$client->client_secret = SocialStreamsHelper::getParameter('instagram_appsecret');

        if ($success = self::$client->Initialize()) {
            if ($success = self::$client->Process()) {
                if (strlen(self::$client->access_token)) {
                    $success = self::$client->CallAPI(
                            'https://api.instagram.com/v1/users/self/feed', 'GET', array(), array('FailOnAccessError' => true), $user);
                }
            }
            $success = self::$client->Finalize($success);
        }
        if (self::$client->exit) {
            $app = JFactory::getApplication();
            $app->close();
        }
        if ($success)
            return self::$client;
        return false;
    }

}

class SocialStreamsInstagram extends SocialStreamsApi {

    private $api_url = 'https://api.instagram.com/v1/';

    public function Initialize() {
        $this->oauth_version = '2.0';
        $this->request_token_url = '';
        $this->dialog_url = 'https://api.instagram.com/oauth/authorize/?client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&state={STATE}&response_type=code';
        $this->append_state_to_redirect_uri = '';
        $this->access_token_url = 'https://api.instagram.com/oauth/access_token';
        $this->authorization_header = true;
        $this->url_parameters = false;
    }

    public function getNetwork() {
        return 'instagram';
    }

    public function getProfile($id = 'me') {
        if (strlen($this->access_token))
            $success = $this->CallAPI($this->api_url . 'users/' . $id, 'GET', array(), array('FailOnAccessError' => true), $user);

        $success = $this->Finalize($success);

        if ($success) {
            $profile = new SocialStreamsInstagramProfile();
            $profile->setProfile($user);
            return $profile;
        }
        return false;
    }

    public function getConnectedProfiles() {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsInstagram::getConnectedProfiles'));
        $my_friends = array();
        if (strlen($this->access_token))
            $success = $this->CallAPI($this->api_url . 'users/' . $this->user . '/followed-by', 'GET', array(), array('FailOnAccessError' => true), $friends);

        $success = $this->Finalize($success);
        if ($success) {
            $show_friends = array();
            $friend_total = count($friends->data);
            $stored_connections = SocialStreamsHelper::getParameter('stored_connections');
            $show_friends = $friend_total > $stored_connections ?
                    array_rand($friends->data, $stored_connections) : array_keys($friends->data);
            foreach ($show_friends as $friend_id) {
                $friend = $this->getProfile($friends->data[$friend_id]->id);
                $my_friends[$friend->networkid] = $friend;
            }
        }
        return $my_friends;
    }

    public function getItems() {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsInstagram::getItems'));
        $my_feed = array();
        if (strlen($this->access_token))
            $success = self::CallAPI($this->api_url . 'users/' . $this->user . '/media/recent', 'GET', array(), array('FailOnAccessError' => true), $feed);
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => print_r($feed, true)));

        $success = $this->Finalize($success);
        if ($success) {
            foreach ($feed->data as $post) {
                if (isset($post->privacy) && $post->privacy->value == 'EVERYONE') {
                    $item = new SocialStreamsInstagramItem();
                    $item->setUpdate($post);
                    $my_feed[$item->networkid] = $item;
                }
            }
            return $my_feed;
        }

        return false;
    }

    public function getStats() {
        
    }

}

/**
 * Coomon interface to a Instagram Profile object 
 */
class SocialStreamsInstagramProfile extends SocialStreamsProfile {

    public function __construct($wraptag = 'li') {
        $this->network = 'instagram';
        $this->nicename = 'Instagram';
        parent::__construct($wraptag);
    }

    public function setProfile($profile) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsInstagramProfile::setProfile'));
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => print_r($profile, true)));
        if (isset($profile->data))
            $profile = $profile->data;
        $this->networkid = $profile->id;
        $this->user = $profile->username;
        $this->name = isset($profile->full_name) ? $profile->full_name : '';
        $this->url = isset($profile->website) ? $profile->website : '';
        $this->image = $profile->profile_picture;
        if (isset($profile->profile))
            $this->profile = json_decode($profile->profile);
        elseif (is_object($profile))
            $this->profile = $profile;
    }

    public function store() {
        return get_object_vars($this);
    }

}

class SocialStreamsInstagramItem extends SocialStreamsItem {

    public function __construct($wraptag = 'li') {
        $this->network = 'instagram';
        $this->nicename = 'Instagram';
        parent::__construct($wraptag);
    }

    function setUpdate($post, $user = null) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsInstagramItem::setUpdate'));
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'Post -> ' . print_r($post, true)));

        if (isset($post->data))
            $post = $post->data;
        $this->networkid = $post->id;
        // Is this a stored Post from the DB or from the API?
        if (isset($post->item)) {
            // Stored Post
            $this->item = json_decode($post->item);
            if (isset($post->profile)) {
                $this->profile = new SocialStreamsInstagramProfile();
                $this->profile->setProfile($post->profile);
            }
        } elseif (is_object($post)) {
            // Fresh Post from API
            $this->item = $post;
            $this->profile = new SocialStreamsInstagramProfile();
            $this->profile->setProfile(isset($post->profile) ? $post->profile : $post->user);
        }
        $this->published = JFactory::getDate(intval($post->created_time))->toMySQL();
    }

    function store() {
        $array = array(
            'network' => $this->network,
            'profile' => $this->profile,
            'networkid' => $this->networkid,
            'published' => $this->published,
            'item' => $this->item
        );
        return $array;
    }

    function styleUpdate() {
        $html = array();
        $likers = '';
        if ($this->item->location->name)
            $html[] = '<span class="stream-item-title in-location-name">' . $this->item->location->name . '</span>';
        if(isset($this->item->likes->count) && $this->item->likes->count > 0){
            $likers = 'Liked by ';
            foreach ($this->item->likes->data as $like)
                $likers.= $like->full_name . ', ';
        }
        switch ($this->item->type) {
            case 'image':
                $html[] = '<span class="stream-item-link stream-item-photo in-image">' .
                    '<a href="' . $this->item->link . '" target="_blank" rel="nofollow">' .
                    '<img src="' . $this->item->images->low_resolution->url . '" alt="' . $this->item->location->name . '" />' .
                    '</a></span>';
                if ($this->item->comments->count){
                    $html[] = '<div class="stream-item-comments in-comments">';
                    $html[] = '<span class="stream-item-label>Comments</span>';
                    $html[] = '<ul>';
                    foreach($this->item->comments->data as $comment){
                        $html[] = '<li class="stream-item-comment in-comment">';
                        $html[] = $comment->text;
                        $html[] = '<span class="stream-item-attribution">' .
                                '<img class="profile-image" src="' . $comment->from->profile_picture . '" alt="' . $comment->from->full_name . '" title="' . $comment->from->full_name . '" />' .
                                '<span class="age">' . $this->getAge($comment->created_time) . '</span>';
                        $html[] = '</span></li>';
                    }
                    $html[] = '</ul></div>';
                }
                break;
        }
        
        return trim(implode("\n", $html));
    }

    function getUpdateActions() {
//        jimport('joomla.error.log');
//        $errorLog = & JLog::getInstance();
//        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'appsolInstagramItem::setUpdateActions'));
        $actions = array('like', 'comment');
        // Likes
        $count = isset($this->item->likes->count) ? intval($this->item->likes->count) : 0;
        $name = $count > 1 ? 'likes' : 'like';
        $tally = '<span class="tally"><span class="count">' . $count . '</span> ' . $name . '</span>';
        $actions['like'] = $tally . '<a class="stream-item-action like" href="' . $this->item->link . '" rel="nofollow" target="_blank">like</a>';
        // Comments
        $count = isset($this->item->comments->count) ? intval($this->item->comments->count) : 0;
        $name = $count > 1 ? 'comments' : 'comment';
        $tally = '<span class="tally"><span class="count">' . $count . '</span> ' . $name . '</span>';
        $actions['comment'] = $tally . '<a class="stream-item-action comment" href="' . $this->item->link . '" rel="nofollow" target="_blank">comment</a>';
        return $actions;
    }

}

?>
