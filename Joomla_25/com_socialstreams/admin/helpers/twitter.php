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
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'twitterHelper::setup'));

        self::$client = new SocialStreamsTwitter();
        self::$client->user = $userid;
        self::$client->debug = self::$debug;
        self::$client->debug_http = self::$debug;
        self::$client->server = self::$server;
        self::$client->redirect_uri = JURI::base() . 'index.php?option=com_socialstreams&task=socialstream.setauth&network=twitter';

        $jparams = JComponentHelper::getParams('com_socialstreams');

        if (strlen($jparams->get('twitter_appkey')) == 0 || strlen($jparams->get('twitter_appsecret')) == 0)
            return false;
        self::$client->client_id = $jparams->get('twitter_appkey');
        self::$client->client_secret = $jparams->get('twitter_appsecret');

        if ($success = self::$client->Initialize()) {
            if ($success = self::$client->Process()) {
                if (strlen(self::$client->access_token)) {
                    $success = self::$client->CallAPI(
                            'https://api.twitter.com/1.1/account/verify_credentials.json', 'GET', array(), array('FailOnAccessError' => true), $user);
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

class SocialStreamsTwitter extends SocialStreamsApi {

    private $api_url = 'https://api.twitter.com/1.1/';

    public function getNetwork() {
        return 'twitter';
    }

    public function getProfile($name = null, $id = null) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsTwitter::getProfile'));
        $params = array();
        if ($id)
            $params['user_id'] = $id;
        elseif ($name)
            $params['screen_name'] = $name;
        elseif (isset($this->user) && !empty($this->user))
            $params['screen_name'] = $this->user;
        else
            return false;

        $jparams = JComponentHelper::getParams('com_socialstreams');
        if ($jparams->get('trim_user'))
            $params['include_entities'] = 'false';

        if (strlen($this->access_token)) {
            $success = $this->CallAPI($this->api_url . 'users/show.json', 'GET', $params, array('FailOnAccessError' => true), $user);
        }

        $success = $this->Finalize($success);

        if ($success) {
            $profile = new SocialStreamsTwitterProfile();
            $profile->setProfile($user);
            return $profile;
        }

        return false;
    }

    public function getConnectedProfiles($name = null, $id = null) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsTwitter::getConnectedProfiles'));
        $jparams = JComponentHelper::getParams('com_socialstreams');
        $only_friends = $jparams->get('only_friends');
        $show_blocked = $jparams->get('show_blocked');
        $this->authorization_header = 0;
        $this->url_parameters = 0;
        $stored_connections = $jparams->get('stored_connections');
        $my_followers = array();
        $params = array('cursor' => -1);
        if ($id)
            $params['user_id'] = $id;
        elseif ($name)
            $params['screen_name'] = $name;
        elseif (isset($this->user) && !empty($this->user))
            $params['screen_name'] = $this->user;
        else
            return false;

        // Get the full list of Followers IDs
        while ($params['cursor'] != 0) {
            if (strlen($this->access_token))
                $success = $this->CallAPI($this->api_url . 'followers/ids.json', 'GET', $params, array('FailOnAccessError' => true), $followers);

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

    public function getItems($name = null, $id = null) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsTwitter::getItems'));
        $jparams = JComponentHelper::getParams('com_socialstreams');
        $params = array(
            'trim_user' => $jparams->get('trim_user'),
            'include_rts' => $jparams->get('include_retweets'),
            'include_entities' => $jparams->get('include_entities'),
            'exclude_replies' => $jparams->get('exclude_replies'),
            'contributor_details' => 'true',
            'count' => $jparams->get('stored_tweets')
        );
        if ($id)
            $params['user_id'] = $id;
        elseif ($name)
            $params['screen_name'] = $name;
        elseif (isset($this->user) && !empty($this->user))
            $params['screen_name'] = $this->user;
        else
            return false;

        $this->authorization_header = 0;
        $this->url_parameters = 0;
        if (strlen($this->access_token))
            $success = $this->CallAPI($this->api_url . 'statuses/user_timeline.json', 'GET', $params, array('FailOnAccessError' => true), $tweets);
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => print_r($tweets, true)));

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
    
    public function getStats(){
        
    }

}

class SocialStreamsTwitterProfile extends SocialStreamsProfile {

    public function __construct($wraptag = 'li') {
        $this->network = 'twitter';
        $this->nicename = 'Twitter';
        parent::__construct($wraptag);
    }

    public function setProfile($profile) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsTwitterProfile::setProfile'));
//        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'Profile -> ' . print_r($profile, true)));
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
    
    public function store(){
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
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsTwitterItem::setUpdate'));
//        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'Tweet -> ' . print_r($tweet, true)));

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
