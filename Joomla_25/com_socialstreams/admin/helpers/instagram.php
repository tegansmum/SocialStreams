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
    public static $scope = 'relationships';
    public static $last_error = '';

    public static function setup($userid = null) {
        self::$client = new SocialStreamsInstagram();
        self::$client->user = $userid;
        self::$client->server = self::$server;
        self::$client->scope = self::$scope;
        self::$client->redirect_uri = SocialStreamsHelper::getAuthRedirectUrl() . '&network=instagram';

        if (strlen(SocialStreamsHelper::getParameter('instagram_appkey')) == 0 || strlen(SocialStreamsHelper::getParameter('instagram_appsecret')) == 0)
            return false;
        self::$client->client_id = SocialStreamsHelper::getParameter('instagram_appkey');
        self::$client->client_secret = SocialStreamsHelper::getParameter('instagram_appsecret');

        $user = self::$client->Bootstrap();

        return $user ? self::$client : false;
    }

}

class SocialStreamsInstagram extends SocialStreamsApi {

    private $api_url = 'https://api.instagram.com/v1/';

//    public function Initialize() {
//        $this->oauth_version = '2.0';
//        $this->request_token_url = '';
//        $this->dialog_url = 'https://api.instagram.com/oauth/authorize/?client_id={CLIENT_ID}&redirect_uri={REDIRECT_URI}&state={STATE}&response_type=code';
//        $this->append_state_to_redirect_uri = '';
//        $this->access_token_url = 'https://api.instagram.com/oauth/access_token';
//        $this->authorization_header = true;
//        $this->url_parameters = true;
//        return true;
//    }

    public function getNetwork() {
        return 'instagram';
    }

    public function getTokenLifetime() {
        return 60 * 60 * 24 * 60;
    }

    public function getProfile($id = 'self') {
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

    public function getConnectedProfiles(&$friend_count) {
        $my_friends = array();
        if (strlen($this->access_token))
            $success = $this->CallAPI($this->api_url . 'users/' . $this->user . '/followed-by', 'GET', array(), array('FailOnAccessError' => true), $friends);
        SocialStreamsHelper::log($friends);
        $success = $this->Finalize($success);
        if ($success) {
            $friend_count = count($friends->data);
            $stored_connections = SocialStreamsHelper::getParameter('stored_connections');
            $show_friends = $friends->data;
            shuffle($show_friends);
            $show_friends = $friend_count > $stored_connections ?
                    array_slice($show_friends, 0, $stored_connections) : $show_friends;
            foreach ($show_friends as $friend) {
                if ($friend = $this->getProfile($friend->id))
                    $my_friends[$friend->networkid] = $friend;
            }
        }
        return $my_friends;
    }

    public function getItems() {

        $my_feed = array();
        if (strlen($this->access_token))
            $success = self::CallAPI($this->api_url . 'users/' . $this->user . '/media/recent', 'GET', array(), array('FailOnAccessError' => true), $feed);
        SocialStreamsHelper::log($feed);

        $success = $this->Finalize($success);
        if ($success) {
            foreach ($feed->data as $post) {
                $item = new SocialStreamsInstagramItem();
                $item->setUpdate($post);
                $my_feed[$item->networkid] = $item;
            }
            return $my_feed;
        }

        return false;
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

    public function getConnectVerb() {
        return 'follow';
    }

    public function setProfile($profile, $short = false) {
        if (isset($profile->data))
            $profile = $profile->data;
        $this->networkid = $profile->id;
        $this->user = $profile->username;
        $this->name = isset($profile->full_name) ? $profile->full_name : '';
        $this->url = isset($profile->website) ? $profile->website : '';
        $this->image = $profile->profile_picture;
        if (!$short) {
            if (isset($profile->profile))
                $this->profile = json_decode($profile->profile);
            elseif (is_object($profile))
                $this->profile = $profile;
        }
    }

    public function getStats() {
        $connections = isset($this->profile->counts->followed_by) ? $this->profile->counts->followed_by : $this->profile->connections;
        return array('name' => 'followers', 'count' => $connections);
    }

}

class SocialStreamsInstagramItem extends SocialStreamsItem {

    public function __construct($wraptag = 'li') {
        $this->network = 'instagram';
        $this->nicename = 'Instagram';
        parent::__construct($wraptag);
    }

    public function getPromoteVerb() {
        return 'like';
    }

    function setUpdate($post, $user = null) {
        if (isset($post->data))
            $post = $post->data;
        $id = explode('_', $post->id);
        $this->networkid = $id[0];

        // Is this a stored Post from the DB or from the API?
        if (isset($post->item)) {
            // Stored Post
            $this->item = json_decode($post->item);
            if (isset($post->profile)) {
                $this->profile = new SocialStreamsInstagramProfile();
                $this->profile->setProfile($post->profile, true);
            }
        } elseif (is_object($post)) {
            // Fresh Post from API
            $this->item = $post;
            $this->profile = new SocialStreamsInstagramProfile();
            $this->profile->setProfile(isset($post->profile) ? $post->profile : $post->user, true);
        }
        $this->published = date('Y-m-d H:i:s', intval($post->created_time));
    }

    function styleUpdate() {
        $html = array();
        $likers = '';
        $caption = isset($this->item->caption) ? $this->item->caption->text : $this->item->location->name;
        if ($this->item->location->name)
            $html[] = '<span class="stream-item-title in-location-name">' . $this->item->location->name . '</span>';
        if (isset($this->item->likes->count) && $this->item->likes->count > 0) {
            $likers = 'Liked by ';
            foreach ($this->item->likes->data as $like)
                $likers.= $like->full_name . ', ';
        }
        switch ($this->item->type) {
            case 'image':

                $html[] = '<span class="stream-item-link stream-item-photo in-image">' .
                        '<a href="' . $this->item->link . '" target="_blank" rel="nofollow">' .
                        '<img src="' . $this->item->images->thumbnail->url . '" alt="' . $caption . '" />' .
                        '</a></span>';
                if (isset($this->item->caption))
                    $html[] = '<span class="stream-item-photo-caption">' . $this->item->caption->text . '</span>';
//                if ($this->item->comments->count) {
//                    $html[] = '<div class="stream-item-comments in-comments">';
//                    $html[] = '<span class="stream-item-label">Comments</span>';
//                    $html[] = '<ul>';
//                    foreach ($this->item->comments->data as $comment) {
//                        $html[] = '<li class="stream-item-comment in-comment">';
//                        $html[] = $comment->text;
//                        $html[] = '<span class="stream-item-attribution">' .
//                                '<img class="profile-image" src="' . $comment->from->profile_picture . '" alt="' . $comment->from->full_name . '" title="' . $comment->from->full_name . '" />' .
//                                '<span class="age">' . $this->getAge($comment->created_time) . '</span>';
//                        $html[] = '</span></li>';
//                    }
//                    $html[] = '</ul></div>';
//                }
                break;
        }

        return trim(implode("\n", $html));
    }

    function getUpdateActions() {
        $actions = array();
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
