<?php

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once(dirname(__FILE__) . '/socialstreams.php');

class facebookHelper {

    public static $client;
    public static $server = 'Facebook';
    public static $redirect_uri = '';
    public static $scope = 'read_stream,publish_stream';
    public static $last_error = '';
    public static $debug = 1;

    public static function setup($userid = null) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'facebookHelper::setup'));
        self::$client = new SocialStreamsFacebook();
        self::$client->user = $userid;
        self::$client->debug = self::$debug;
        self::$client->server = self::$server;
        self::$client->scope = self::$scope;
        self::$client->redirect_uri = JURI::base() . 'index.php?option=com_socialstreams&task=socialstream.setauth&network=facebook';

        $jparams = JComponentHelper::getParams('com_socialstreams');

        if (strlen($jparams->get('facebook_appkey')) == 0 || strlen($jparams->get('facebook_appsecret')) == 0)
            return false;
        self::$client->client_id = $jparams->get('facebook_appkey');
        self::$client->client_secret = $jparams->get('facebook_appsecret');

        if ($success = self::$client->Initialize()) {
            if ($success = self::$client->Process()) {
                if (strlen(self::$client->access_token)) {
                    $success = self::$client->CallAPI(
                            'https://graph.facebook.com/me', 'GET', array(), array('FailOnAccessError' => true), $user);
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

class SocialStreamsFacebook extends SocialStreamsApi {

    private $api_url = 'https://graph.facebook.com/';

    public function getNetwork() {
        return 'facebook';
    }

    public function getProfile($id = 'me') {
        if (strlen($this->access_token))
            $success = $this->CallAPI($this->api_url . $id, 'GET', array(), array('FailOnAccessError' => true), $user);

        $success = $this->Finalize($success);

        if ($success) {
            $profile = new SocialStreamsFacebookProfile();
            $profile->setProfile($user);
            return $profile;
        }
        return false;
    }

    public function getConnectedProfiles() {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsFacebook::getConnectedProfiles'));
        $my_friends = array();
        if (strlen($this->access_token))
            $success = $this->CallAPI($this->api_url . $this->user . '/friends', 'GET', array(), array('FailOnAccessError' => true), $friends);

        $success = $this->Finalize($success);
        if ($success) {
            $show_friends = array();
            $friend_total = count($friends->data);
            $jparams = JComponentHelper::getParams('com_socialstreams');
            $stored_connections = $jparams->get('stored_connections');
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
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsFacebook::getItems'));
        $my_feed = array();
        if (strlen($this->access_token))
            $success = self::CallAPI($this->api_url . $this->user . '/feed', 'GET', array(), array('FailOnAccessError' => true), $feed);
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => print_r($feed, true)));

        $success = $this->Finalize($success);
        if ($success) {
            foreach ($feed->data as $post) {
                if (isset($post->privacy) && $post->privacy->value == 'EVERYONE') {
                    $item = new SocialStreamsFacebookItem();
                    $item->setUpdate($post);
                    $my_feed[$item->networkid] = $item;
                }
            }
            return $my_feed;
        }

        return false;
    }
    
    public function getStats(){
        
    }

}

/**
 * Coomon interface to a Facebook Profile object 
 */
class SocialStreamsFacebookProfile extends SocialStreamsProfile {

    public function __construct($wraptag = 'li') {
        $this->network = 'facebook';
        $this->nicename = 'Facebook';
        parent::__construct($wraptag);
    }

    public function setProfile($profile) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsFacebookProfile::setProfile'));
//        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => print_r($profile, true)));
        $profile_image = 'http://graph.facebook.com/' . $profile->id . '/picture?type=square';
        $this->networkid = $profile->id;
        $this->user = isset($profile->username) ? $profile->username : '';
        $this->name = $profile->name;
        $this->url = isset($profile->link) ? $profile->link : '';
        $this->image = $profile_image;
        if (isset($profile->profile))
            $this->profile = json_decode($profile->profile);
        elseif (is_object($profile))
            $this->profile = $profile;
    }
    
    public function store(){
        return get_object_vars($this);
    }

}

class SocialStreamsFacebookItem extends SocialStreamsItem {

    public function __construct($wraptag = 'li') {
        $this->network = 'facebook';
        $this->nicename = 'Facebook';
        parent::__construct($wraptag);
    }

    function setUpdate($post, $fb_user = null) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsFacebookItem::setUpdate'));
//        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'Post -> ' . print_r($post, true)));

        $this->networkid = $post->id;
        // Is this a stored Post from the DB or from the API?
        if (isset($post->item)) {
            // Stored Post
            $this->item = json_decode($post->item);
            if (isset($post->profile)) {
                $this->profile = new SocialStreamsFacebookProfile();
                $this->profile->setProfile($post->profile);
            }
        } elseif (is_object($post)) {
            // Fresh Post from API
            $this->item = $post;
            $this->profile = new SocialStreamsFacebookProfile();
            $this->profile->setProfile(isset($post->profile) ? $post->profile : $post->from);
        }
        $this->published = JFactory::getDate(strtotime($post->created_time))->toMySQL();
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
        foreach ($update as $property => $value)
            if (!isset($this->item->$property))
                $this->item->$property = $value;
        if ($this->item->name)
            $fb_text.= '<span class="stream-item-title fb-post-name">' . $this->item->name . '</span>';
        switch ($this->item->type) {
            case 'link':
                if ($this->item->picture) {
                    $fb_text.= '<span class="stream-item-link stream-item-photo fb-post-link">';
                    $fb_text.= '<a class="stream-item-photo-image-link" href="' . $this->item->link . '" target="_blank" rel="nofollow"><img src="' . $this->item->picture . '" alt="' . $this->item->caption . '" /></a>';
                    if ($this->item->caption)
                        $fb_text.= '<span class="stream-item-photo-caption">' . $this->item->caption . '</span>';
                }else {
                    $fb_text.= '<span class="stream-item-link fb-post-link">';
                    $fb_text.= '<a href="' . $this->item->link . '" target="_blank" rel="nofollow">' . $this->item->name . '</a>';
                }
                $fb_text.= '</span>';
                break;
            case 'photo':
                $fb_text.= '<span class="stream-item-link stream-item-photo fb-post-photo">';
                $fb_text.= '<a href="' . $this->item->link . '" target="_blank" rel="nofollow"><img src="' . $this->item->picture . '" alt="' . $this->item->name . '" /></a>';
                $fb_text.= '</span>';
                if ($this->item->story)
                    $fb_text.= $this->item->story;
                break;
            case 'video':
                $fb_text.= '<span class="stream-item-link stream-item-video fb-post-video">';
                $fb_text.= '<a href="' . $this->item->link . '" target="_blank" rel="nofollow">';
                $fb_text.= '<img src="' . $this->item->picture . '" alt="' . $this->item->name . '" />';
                $fb_text.= '</a>';
                $fb_text.= '</span>';
                break;
        }
        $fb_text.= $this->item->description . ' ' . $this->item->message;
        return trim($fb_text);
    }

    function getUpdateActions() {
//        jimport('joomla.error.log');
//        $errorLog = & JLog::getInstance();
//        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'appsolFacebookItem::setUpdateActions'));
        $allowed_actions = array('like', 'comment');
        $actions = array();
        foreach ($this->item->actions as $action) {
            if (!in_array($action->name, $allowed_actions))
                continue;
            $tally = '';
            $action_name = strtolower($action->name) . 's';
            if (isset($this->item->$action_name)) {
                $count = isset($this->item->$action_name->count) ? $this->item->$action_name->count : 0;
                $name = $count > 1 ? $action->name . 's' : $action->name;
                $tally = '<span class="tally"><span class="count">' . $count . '</span> ' . $name . '</span>';
            }
            $actions[strtolower($action->name)] = $tally . '<a class="stream-item-action ' . strtolower($action->name) . '" href="' . $action->link . '" rel="nofollow" target="_blank">' . $action->name . '</a>';
        }
        return $actions;
    }

}

?>
