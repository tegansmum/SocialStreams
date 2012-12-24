<?php

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once(dirname(__FILE__) . '/socialstreams.php');

class twitterHelper {

    public static $client;
    public static $server = 'Twitter';
    public static $redirect_uri = '';
    public static $last_error = '';
    public static $debug = 1;

    public static function setup($userid = null) {
        self::$client = new SocialStreamsTwitter();
        self::$client->user = $userid;
        self::$client->debug = self::$debug;
        self::$client->debug_http = self::$debug;
        self::$client->server = self::$server;
        self::$client->redirect_uri = JURI::base() . 'index.php?option=com_socialstreams&task=socialstream.setauth&network=twitter';

        if (strlen(SocialStreamsHelper::getParameter('twitter_appkey')) == 0 || strlen(SocialStreamsHelper::getParameter('twitter_appsecret')) == 0)
            return false;
        self::$client->client_id = SocialStreamsHelper::getParameter('twitter_appkey');
        self::$client->client_secret = SocialStreamsHelper::getParameter('twitter_appsecret');

        $success = self::$client->Bootstrap();

        return $success ? self::$client : false;
    }

}

class SocialStreamsTwitter extends SocialStreamsApi {

    private $api_url = 'https://api.twitter.com/1.1/';

    public function getNetwork() {
        return 'twitter';
    }

    public function getTokenLifetime() {
        return 0;
    }

    public function getProfile($id = null, $name = null) {
        $params = array();
        if ($id)
            $params['user_id'] = $id;
        elseif ($name)
            $params['screen_name'] = $name;
        elseif (isset($this->user) && !empty($this->user))
            $params['user_id'] = $this->user;

        if (SocialStreamsHelper::getParameter('trim_user'))
            $params['include_entities'] = 'false';

        if (strlen($this->access_token)) {
            SocialStreamsHelper::log($params);
            if (!isset($params['user_id']) && !isset($params['screen_name']))
                $success = $this->CallAPI(
                        $this->api_url .'account/verify_credentials.json', 'GET', array(), array('FailOnAccessError' => true), $user);
            else
                $success = $this->CallAPI($this->api_url . 'users/show.json', 'GET', $params, array('FailOnAccessError' => true), $user);
            SocialStreamsHelper::log($user);
        }

        $success = $this->Finalize($success);

        if ($success) {
            $profile = new SocialStreamsTwitterProfile();
            $profile->setProfile($user);
            return $profile;
        }

        return false;
    }

    public function getConnectedProfiles($id = null, $name = null) {
        $only_friends = SocialStreamsHelper::getParameter('only_friends');
        $show_blocked = SocialStreamsHelper::getParameter('show_blocked');
        $this->authorization_header = 0;
        $this->url_parameters = 0;
        $stored_connections = SocialStreamsHelper::getParameter('stored_connections');
        $my_followers = array();
        $params = array('cursor' => -1);
        if ($id)
            $params['user_id'] = $id;
        elseif ($name)
            $params['screen_name'] = $name;
        elseif (isset($this->user) && !empty($this->user))
            $params['user_id'] = $this->user;
        else
            return false;

        // Get the full list of Followers IDs
        while ($params['cursor'] != 0) {
            if (strlen($this->access_token))
                $success = $this->CallAPI($this->api_url . 'followers/ids.json', 'GET', $params, array('FailOnAccessError' => true), $followers);
            SocialStreamsHelper::log($followers);
            $success = $this->Finalize($success);
            if ($success) {
                $params['cursor'] = $followers->next_cursor;
                $my_followers = array_merge($my_followers, $followers->ids);
            } else {
                break;
            }
        }
        $follower_total = count($my_followers);

        if ($only_friends) {
            // Get a list of Twitter users the user follows

            $my_friends = array();
            $params['cursor'] = -1;
            while ($params['cursor'] != 0) {
                if (strlen($this->access_token))
                    $success = $this->CallAPI($this->api_url . 'friends/ids.json', 'GET', $params, array('FailOnAccessError' => true), $friends);

                $success = $this->Finalize($success);
                if ($success) {
                    $params['cursor'] = $friends->next_cursor;
                    $my_friends = array_merge($my_friends, $friends->ids);
                } else {
                    break;
                }
            }
            // Only add those who are in the friend list
            $my_followers = array_intersect($my_followers, $my_friends);
        }

        if (!$show_blocked) {
            // Remove followers who are Blocked by the authenticating user
            $params = array('cursor' => -1);
            $blocked_ids = array();
            while ($params['cursor'] != 0) {
                if (strlen($this->access_token))
                    $success = $this->CallAPI($this->api_url . 'blocks/ids.json', 'GET', $params, array('FailOnAccessError' => true), $blocked);

                $success = $this->Finalize($success);
                if ($success) {
                    $params['cursor'] = $blocked->next_cursor;
                    $blocked_ids = array_merge($blocked_ids, $blocked->ids);
                } else {
                    break;
                }
            }
            $my_followers = array_diff($my_followers, $blocked_ids);
        }
        // Process the follower ID list
        $show_followers = array();
        if (count($my_followers) < $stored_connections) {
            $show_followers = implode(',', $my_followers);
        } else {
            shuffle($my_followers);
            $show_followers = implode(',', array_slice($my_followers, 0, $stored_connections));
        }
        // Reset my_followers
        $my_followers = array();

        $params = array(
            'user_id' => $show_followers,
            'include_entities' => 'false'
        );
        $this->authorization_header = 1;

        if (strlen($this->access_token))
            $success = $this->CallAPI($this->api_url . 'users/lookup.json', 'POST', $params, array('FailOnAccessError' => true), $followers);

        $success = $this->Finalize($success);
        if (!is_array($followers))
            if ($followers = json_decode($followers))
                $success = true;

        if ($success) {
            foreach ($followers as $follower) {
                $profile = new SocialStreamsTwitterProfile();
                $profile->setProfile($follower);
                $my_followers[$profile->networkid] = $profile;
            }
        }

        return $my_followers;
    }

    public function getItems($id = null, $name = null) {
        $params = array(
            'trim_user' => SocialStreamsHelper::getParameter('trim_user'),
            'include_rts' => SocialStreamsHelper::getParameter('include_retweets'),
            'include_entities' => SocialStreamsHelper::getParameter('include_entities'),
            'exclude_replies' => SocialStreamsHelper::getParameter('exclude_replies'),
            'contributor_details' => 'true',
            'count' => SocialStreamsHelper::getParameter('stored_tweets')
        );
        if ($id)
            $params['user_id'] = $id;
        elseif ($name)
            $params['screen_name'] = $name;
        elseif (isset($this->user) && !empty($this->user))
            $params['user_id'] = $this->user;
        else
            return false;

        $this->authorization_header = 0;
        $this->url_parameters = 0;
        if (strlen($this->access_token))
            $success = $this->CallAPI($this->api_url . 'statuses/user_timeline.json', 'GET', $params, array('FailOnAccessError' => true), $tweets);
        SocialStreamsHelper::log($tweets);

        $success = $this->Finalize($success);

        if (!is_array($tweets))
            if ($tweets = json_decode($tweets))
                $success = true;

        if ($success) {
            $my_tweets = array();
            foreach ($tweets as $tweet) {
                $item = new SocialStreamsTwitterItem();
                $item->setUpdate($tweet);
                $my_tweets[$item->networkid] = $item;
            }
            return $my_tweets;
        }

        return false;
    }

    public function getStats() {
        
    }

}

class SocialStreamsTwitterProfile extends SocialStreamsProfile {

    public function __construct($wraptag = 'li') {
        $this->network = 'twitter';
        $this->nicename = 'Twitter';
        parent::__construct($wraptag);
    }

    public function setProfile($profile) {
        $this->networkid = $profile->id_str;
        if (isset($profile->name)) {
            $this->user = $profile->screen_name;
            $this->name = $profile->name;
            $this->url = 'http://twitter.com#!/' . $profile->screen_name;
            $this->image = str_ireplace(array('_bigger', '_mini', '_original'), '_normal', $profile->profile_image_url);
            if (isset($profile->profile))
                $this->profile = json_decode($profile->profile);
            elseif (is_object($profile))
                $this->profile = $profile;
        }
    }

    public function store() {
        return get_object_vars($this);
    }

}

class SocialStreamsTwitterItem extends SocialStreamsItem {

    public function __construct($wraptag = 'li') {
        $this->network = 'twitter';
        $this->nicename = 'Twitter';
        parent::__construct($wraptag);
    }

    function setUpdate($tweet) {
        $this->networkid = $tweet->id_str;
        // Is this a stored Tweet from the DB or from the API?
        if (isset($tweet->item)) {
            $this->item = json_decode($tweet->item);
            if (isset($tweet->profile)) {
                $this->profile = new SocialStreamsTwitterProfile();
                $this->profile->setProfile($tweet->profile);
            }
        } elseif (is_object($tweet)) {
            $this->item = $tweet;
            $this->profile = new SocialStreamsTwitterProfile();
            $this->profile->setProfile(isset($post->profile) ? $post->profile : $tweet->user);
        }
        $this->published = JFactory::getDate(strtotime($tweet->created_at))->toMySQL();
    }

    public function store() {
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
        $used_hashtags = array();
        $used_urls = array();
        $used_users = array();
        $tweet_text = $this->item->text;
        if (is_array($this->item->entities->hashtags)) {
            foreach ($this->item->entities->hashtags as $hashtag) {
                if (!in_array($hashtag->text, $used_hashtags)) {
                    $tweet_text = str_ireplace('#' . $hashtag->text, '<a class="stream-item-link tw-hashtag" href="http://twitter.com/#!/search?q=%23' . $hashtag->text . '" rel="nofollow">#' . $hashtag->text . '</a>', $tweet_text);
                    $used_hashtags[] = $hashtag->text;
                }
            }
        }
        if (is_array($this->item->entities->urls)) {
            foreach ($this->item->entities->urls as $url) {
                if (!in_array($url->url, $used_urls)) {
                    $target = empty($url->expanded_url) ?
                            $url->url : $url->expanded_url;
                    $tweet_text = str_ireplace($url->url, '<a class="stream-item-link tw-link" href="' . $target . '" rel="nofollow">' . $url->url . '</a>', $tweet_text);
                    $used_urls[] = $url->url;
                }
            }
        }
        if (is_array($this->item->entities->user_mentions)) {
            foreach ($this->item->entities->user_mentions as $user) {
                if (!in_array($user->screen_name, $used_users)) {
                    $tweet_text = str_ireplace('@' . $user->screen_name, '<a class="stream-item-link tw-user" href="http://twitter.com/#!/' . $user->screen_name . '" title="' . $user->name . '" rel="nofollow">' . '@' . $user->screen_name . '</a>', $tweet_text);
                    $used_users[] = $user->screen_name;
                }
            }
        }
        return $tweet_text;
    }

    function getUpdateActions() {
        $tweet_text = substr('RT @' . $this->profile->user . ' ' . $this->item->text, 0, 140);
        $tally = '';
        $actions = array();
        if ($this->item->retweet_count > 0) {
            $name = $this->item->retweet_count > 1 ? 'retweets' : 'retweet';
            $tally = '<span class="tally"><span class="count">' . $this->item->retweet_count . '</span> ' . $name . '</span>';
        }
        $actions['retweet'] = '<a class="stream-item-action retweet" target="_blank" rel="nofollow" href="http://twitter.com/share?text=' . $tweet_text . '&via=' . $this->profile->user . '">Retweet</a>';
        return $actions;
    }

}

?>
