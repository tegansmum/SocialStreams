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
    public static $scope = 'read_stream,publish_stream,manage_pages';
    public static $last_error = '';
    public static $debug = 1;

    public static function setup($userid = null) {
        self::$client = new SocialStreamsFacebook();
        self::$client->user = $userid;
        self::$client->debug = self::$debug;
        self::$client->server = self::$server;
        self::$client->scope = self::$scope;
        self::$client->redirect_uri = JURI::base() . 'index.php?option=com_socialstreams&task=socialstream.setauth&network=facebook';

        if (strlen(SocialStreamsHelper::getParameter('facebook_appkey')) == 0 || strlen(SocialStreamsHelper::getParameter('facebook_appsecret')) == 0)
            return false;
        self::$client->client_id = SocialStreamsHelper::getParameter('facebook_appkey');
        self::$client->client_secret = SocialStreamsHelper::getParameter('facebook_appsecret');

        $user = self::$client->Bootstrap();

        // Look for managed pages, but only if this is not a page
        if($user && (isset($user->profile->first_name) || isset($user->profile->last_name)))
            self::$client->getPages();

        return $success ? self::$client : false;
    }

}

class SocialStreamsFacebook extends SocialStreamsApi {

    private $api_url = 'https://graph.facebook.com/';

    public function getNetwork() {
        return 'facebook';
    }

    public function getTokenLifetime() {
        return 60 * 60 * 24 * 60;
    }

    public function getProfile($id = 'me') {
        if (strlen($this->access_token))
            $success = $this->CallAPI($this->api_url . $id, 'GET', array(), array('FailOnAccessError' => true), $user);
        SocialStreamsHelper::log($user);
        $success = $this->Finalize($success);

        if ($success) {
            $profile = new SocialStreamsFacebookProfile();
            $profile->setProfile($user);
            return $profile;
        }
        return false;
    }

    public function getConnectedProfiles() {
        $my_friends = array();
        if (strlen($this->access_token))
            $success = $this->CallAPI($this->api_url . $this->user . '/friends', 'GET', array(), array('FailOnAccessError' => true), $friends);
        SocialStreamsHelper::log($friends);
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
        $my_feed = array();
        if (strlen($this->access_token))
            $success = self::CallAPI($this->api_url . $this->user . '/feed', 'GET', array(), array('FailOnAccessError' => true), $feed);
        SocialStreamsHelper::log($feed);
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

    public function getPages() {
        if (strlen($this->access_token))
            $success = $this->CallAPI($this->api_url . 'me/accounts', 'GET', array(), array('FailOnAccessError' => true), $pages);
        SocialStreamsHelper::log($pages);
        $success = $this->Finalize($success);
        if ($success) {
            foreach ($pages->data as $page) {
                if($page->category == 'Application')
                    continue;
                $data = array(
                    'network' => $this->getNetwork(),
                    'clientid' => $page->id,
                    'access_token' => $page->access_token,
                    'access_token_secret' => '',
                    'expires' => JFactory::getDate(time() + (60 * 60 * 24 * 60))->toMySQL(),
                    'params' => array(
                        'type' => 'page',
                        'name' => $page->name,
                        'category' => $page->category,
                        'permissions' => $page->perms),
                    'state' => 1
                );
                SocialStreamsHelper::storeAuth($data);
            }
        }
    }

    public function getStats() {
        
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

    public function store() {
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
