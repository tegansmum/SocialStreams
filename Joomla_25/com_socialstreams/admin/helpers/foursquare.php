<?php

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once(dirname(__FILE__) . '/socialstreams.php');

class foursquareHelper {

    public static $client;
    public static $server = 'Foursquare';
    public static $redirect_uri = '';
    public static $scope = '';
    public static $last_error = '';

    public static function setup($userid = null) {
        self::$client = new SocialStreamsFoursquare();
        self::$client->user = $userid;
        self::$client->server = self::$server;
        self::$client->scope = self::$scope;
        self::$client->redirect_uri = SocialStreamsHelper::getAuthRedirectUrl() . '&network=foursquare';

        if (strlen(SocialStreamsHelper::getParameter('foursquare_appkey')) == 0 || strlen(SocialStreamsHelper::getParameter('foursquare_appsecret')) == 0)
            return false;
        self::$client->client_id = SocialStreamsHelper::getParameter('foursquare_appkey');
        self::$client->client_secret = SocialStreamsHelper::getParameter('foursquare_appsecret');

        $user = self::$client->Bootstrap();

        return $user ? self::$client : false;
    }

}

class SocialStreamsFoursquare extends SocialStreamsApi {

    private $api_url = 'https://api.foursquare.com/v2/';

    public function getNetwork() {
        return 'foursquare';
    }

    public function getTokenLifetime() {
        return 0;
    }

    public function getProfile($id = 'self') {
        if (strlen($this->access_token))
            $success = $this->CallAPI($this->api_url . 'users/' . $id, 'GET', array(), array('FailOnAccessError' => true), $user);
        SocialStreamsHelper::log($user);

        $success = $this->Finalize($success);

        if ($success) {
            $profile = new SocialStreamsFoursquareProfile();
            $profile->setProfile($user);
            return $profile;
        }
        return false;
    }

    public function getConnectedProfiles(&$friend_count) {
        $my_friends = array();
        if (strlen($this->access_token))
            $success = $this->CallAPI($this->api_url . 'users/' . $this->user . '/friends', 'GET', array(), array('FailOnAccessError' => true), $friends);
        SocialStreamsHelper::log($friends);

        $success = $this->Finalize($success);
        if ($success) {
            $friend_count = $friends->response->friends->count;
            $stored_connections = SocialStreamsHelper::getParameter('stored_connections');
            $show_friends = $friends->response->friends->items;
            shuffle($show_friends);
            $show_friends = $friend_count > $stored_connections ?
                    array_slice($show_friends, 0, $stored_connections) : $show_friends;
            foreach ($show_friends as $friend) {
                $profile = new SocialStreamsFoursquareProfile();
                $profile->setProfile($friend);
                $my_friends[$profile->networkid] = $profile;
            }
        }
        return $my_friends;
    }

    public function getItems() {
        $my_feed = array();
        if (strlen($this->access_token))
            $success = self::CallAPI($this->api_url . 'users/' . $this->user . '/checkins', 'GET', array(), array('FailOnAccessError' => true), $feed);
        SocialStreamsHelper::log($feed);

        $success = $this->Finalize($success);
        if ($success) {
            foreach ($feed->response->checkins->items as $checkin) {
                if (!isset($checkin->private)) {
                    $item = new SocialStreamsFoursquareItem();
                    $item->setUpdate($checkin);
                    $my_feed[$item->networkid] = $item;
                }
            }
            return $my_feed;
        }

        return false;
    }

}

/**
 * Coomon interface to a Foursquare Profile object 
 */
class SocialStreamsFoursquareProfile extends SocialStreamsProfile {

    public function __construct($wraptag = 'li') {
        $this->network = 'foursquare';
        $this->nicename = 'Foursquare';
        parent::__construct($wraptag);
    }

    public function getConnectVerb() {
        return 'follow';
    }

    public function setProfile($profile, $short = false) {
        if (isset($profile->response->user))
            $profile = $profile->response->user;
        $profile_image = isset($profile->photo->prefix) ?
                $profile->photo->prefix . '100x100' . $profile->photo->suffix : $profile->photo;
        $this->networkid = $profile->id;
        $this->user = isset($profile->username) ? $profile->username : '';
        $this->name = $profile->firstName . ' ' . $profile->lastName;
        $this->url = isset($profile->contact->twitter) ? 'https://foursquare.com/' . $profile->contact->twitter : 'https://foursquare.com/user/' . $profile->id;
        $this->image = $profile_image;
        if (!$short) {
            if (isset($profile->profile))
                $this->profile = json_decode($profile->profile);
            elseif (is_object($profile))
                $this->profile = $profile;
        }
    }

    public function getStats() {
        if (isset($this->profile->friends)) {
            $connections = $this->profile->friends->count;
        } elseif (isset($this->profile->followers)) {
            $connections = $this->profile->followers->count;
        } else {
            $connections = $this->profile->connections;
        }
        return array('name' => 'friends', 'count' => $connections);
    }

}

class SocialStreamsFoursquareItem extends SocialStreamsItem {

    public function __construct($wraptag = 'li') {
        $this->network = 'foursquare';
        $this->nicename = 'Foursquare';
        parent::__construct($wraptag);
    }

    public function getPromoteVerb() {
        return 'follow';
    }

    function setUpdate($checkin, $user = null) {

        $this->networkid = $checkin->id;
        // Is this a stored Post from the DB or from the API?
        if (isset($checkin->item)) {
            // Stored Post
            $this->item = json_decode($checkin->item);
            if (isset($checkin->profile)) {
                $this->profile = new SocialStreamsFoursquareProfile();
                $this->profile->setProfile($checkin->profile, true);
            }
        } elseif (is_object($checkin)) {
            // Fresh Post from API
            $this->item = $checkin;
            $this->profile = new SocialStreamsFoursquareProfile();
            $this->profile->setProfile(isset($checkin->profile) ? $checkin->profile : $checkin->user, true);
        }
        $this->published = date('Y-m-d H:i:s', intval($checkin->createdAt));
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

