<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$appsol_social_streams_caches = array(
    'profile' => array(
        'fb' => array('appsolFacebookProfileCache'),
        'tw' => array('appsolTwitterProfileCache'),
        'li' => array('appsolLinkedinProfileCache'),
        'gp' => array('appsolGooglePlusProfileCache')
    ),
    'stream' => array(
        'fb' => array('appsolFacebookPostCache'),
        'tw' => array('appsolTwitterTweetCache'),
        'li' => array('appsolLinkedinUpdatesCache'),
        'gp' => array('appsolGooglePlusActivitiesCache')
    ),
    'connection' => array(
        'fb' => array('appsolFacebookFriendCache'),
        'tw' => array('appsolTwitterFollowerCache'),
        'li' => array('appsolLinkedinConnectionsCache'),
        'gp' => array('appsolGooglePlusCircleCache')
    ),
    'gallery' => array(
        'fb' => array('appsolFacebookAlbumCache',
            'appsolFacebookAlbumsCache'
        )
    )
);

class appsolSocialStreamCache {

    protected $twitter = null;
    protected $google = null;
    protected $facebook = null;
    protected $linkedin = null;
    protected $instance = array();
    protected $daily_expiry = null;
    protected $hourly_expiry = null;
    public $transient = '';
    public $cache = null;
    public $msg = '';

    function __construct($instance) {
        $this->instance = $instance;
        $this->daily_expiry = 60 * 60 * rand(24, 48);
        $this->hourly_expiry = 60 * rand(30, 60);
    }

    function getCache() {
        if (!$this->cache)
            $this->cache = get_transient($this->transient);
        return $this->cache;
    }

    function setCache($force_update = false) {
        $this->cache = get_transient($this->transient);
        if ($this->cache == false || $force_update) {
            $this->updateCache();
        }
    }

    function purgeCache() {
        delete_transient($this->transient);
    }

    protected function updateCache() {
        return false;
    }

    protected function fetchData() {
        return false;
    }

//    protected function updateCacheLog($expires) {
//        $cache_log = get_option('appsol_social_streams_cache_log', array());
//        if (!is_array($cache_log[$this->widget_id]))
//            $cache_log[$this->widget_id] = array();
//        $cache_log[$this->widget_id][$this->transient] = time() + $expires;
//        update_option('appsol_social_streams_cache_log', $cache_log);
//    }

    function twitterConnect($path, $params, $protocol = 'get') {
        if (!$this->twitter)
            $this->twitter = new appsolTwitterApi($this->instance['tw_user_id']);

        $response = $protocol == 'get' ?
                $this->twitter->get($path, $params) : $this->twitter->post($path, $params);
        if ($this->twitter->lastStatusCode() == 200)
            return $response;
        $this->msg = '<strong>Code:</strong>' . $this->twitter->lastStatusCode();
        $this->msg.= ' <strong>Request:</strong>' . $this->twitter->lastApiCall();
        update_option('appsol_tw_last_msg', $this->msg);
        return false;
    }

    function googleConnect() {
        if (!$this->google)
            $this->google = new appsolGoogleApi($this->instance['gp_user_id']);
        return $this->google->profile;
    }

    function facebookConnect() {
        if (!$this->facebook)
            $this->facebook = new appsolFacebookApi($this->instance['fb_user_id']);
        return $this->facebook->profile;
    }

    function linkedinConnect() {
        if (!$this->linkedin)
            $this->linkedin = new appsolLinkedinApi($this->instance['li_user_id']);
        return $this->linkedin->profile;
    }

}

class appsolTwitterTweetCache extends appsolSocialStreamCache {

    const transient_base = 'tw_tweet_';

    function __construct($instance) {
        parent::__construct($instance);
        $this->transient = self::transient_base . $this->instance['tw_user_id'];
        $this->getCache();
    }

    function updateCache() {
        if (!$this->instance['tw_user_id'])
            return false;
        $tweet_stream = array();
        $tweets = $this->fetchData();
        if ($tweets) {
            delete_transient($this->transient);
            $twitter_user = get_transient('tw_profile_' . $this->instance['tw_user_id']);
            foreach ($tweets as $tweet) {
                $datetime = strtotime($tweet->created_at);
                $tweet_stream[$datetime] = new appsolTweetItem($datetime);
                $tweet_stream[$datetime]->setUpdate($tweet);
                if (!isset($tweet->user->name) && $tweet->user->id == $twitter_user->id)
                    $tweet_stream[$datetime]->profile->setProfile($twitter_user);
            }
//            $expires = 60 * rand(30, 60);
            set_transient($this->transient, $tweet_stream, $this->hourly_expiry);
//            $this->updateCacheLog($expires);
            $this->cache = $tweet_stream;
        }
        return $tweet_stream;
    }

    function fetchData() {
        $url = 'statuses/user_timeline';
        $params = array(
            'user_id' => $this->instance['tw_user_id'],
            'trim_user' => $this->instance['tw_trim_user'],
            'include_rts' => $this->instance['tw_include_rts'],
            'include_entities' => $this->instance['tw_include_entities'],
            'count' => APPSOL_TW_COUNT
        );
        $tweets = $this->twitterConnect($url, $params);
        return $tweets;
    }

}

class appsolTwitterFollowerCache extends appsolSocialStreamCache {

    const transient_base = 'tw_follower_';

    function __construct($instance) {
        parent::__construct($instance);
        $this->transient = self::transient_base . $this->instance['tw_user_id'];
        $this->getCache();
    }

    function updateCache() {
        if (!$this->instance['tw_user_id'])
            return false;
        $twitter_connections = array();
        if ($followers = $this->fetchData()) {
            delete_transient($this->transient);
            foreach ($followers as $follower) {
                $twitter_connections[$follower->id_str] = new appsolTwitterProfile('twitter');
                $twitter_connections[$follower->id_str]->setProfile($follower);
            }
//            $expires = 60 * 60 * rand(24, 48);
            set_transient($this->transient, $twitter_connections, $this->daily_expiry);
//            $this->updateCacheLog($expires);
            $this->cache = $twitter_connections;
        }
        return $twitter_connections;
    }

    function fetchData() {
        $my_followers = $this->fetchFollowers($this->instance['tw_user_id']);
        $follower_totals = get_option('appsol_social_streams_tw_followers', array());
        $follower_totals[$this->instance['tw_user_id']] = count($my_followers);
        update_option('appsol_social_streams_tw_followers', $follower_totals);
        if ($this->instance['tw_show_only_friends']) {
            // Get a list of Twitter users the user follows
            $my_friends = $this->fetchFriends($this->instance['tw_user_id']);
            // Only add those who are in the friend list
            $my_followers = array_intersect($my_followers, $my_friends);
        }
        if (!$this->instance['tw_show_spam_followers']) {
            // Remove any followers who are on the spam list
            $spam_followers = get_option('appsol_social_streams_tw_spam_followers', array());
            $true_followers = array_diff($my_followers, $spam_followers);
            $my_followers = $true_followers;
        }
        $my_followers = $this->fetchUsers($my_followers);
        // If spam filtering is in place then check the follower list for new spam
        if (!$this->instance['tw_show_spam_followers'])
            $my_followers = $this->filterFollowers($my_followers);
        return $my_followers;
    }

    function fetchFollowers($user_id) {
        // Get a list of Twitter users who follow the user
        $my_followers = array();
        $url = 'followers/ids';
        $params = array(
            'cursor' => -1,
            'user_id' => $this->instance['tw_user_id']
        );
        while ($params['cursor'] != 0) {
            $followers = $this->twitterConnect($url, $params);
            $params['cursor'] = $followers->next_cursor;
            $my_followers = array_merge($my_followers, $followers->ids);
        }
        return $my_followers;
    }

    function fetchFriends($user_id) {
        // Get a list of Twitter users the user follows
        $url = 'friends/ids';
        $params = array(
            'user_id' => $user_id,
            'cursor' => -1
        );
        $my_friends = array();
        while ($params['cursor'] != 0) {
            $friends = $this->twitterConnect($url, $params);
            $params['cursor'] = $friends->next_cursor;
            $my_friends = array_merge($my_friends, $friends->ids);
        }
        return $my_friends;
    }

    function fetchUsers($user_ids) {
        $batch_size = 50;
        if (!is_array($user_ids))
            $user_ids = array($user_ids);
        if (count($user_ids) < $batch_size) {
            $user_ids = implode(',', $user_ids);
        } else {
            $user_ids = implode(',', array_rand($user_ids, $batch_size));
        }
        $url = 'users/lookup';
        $params = array(
            'user_id' => $user_ids,
            'include_entities' => '0'
        );
        $users = $this->twitterConnect($url, $params, 'post');
        return $users;
    }

    function filterFollowers($followers) {
        $spamlevel = 2;
        /**
         * @todo allow more control over spam scoring and removal from the spam list
         */
        $my_followers = array();
        $spam_followers = get_option('appsol_social_streams_tw_spam_followers', array());
        foreach ($followers as $follower) {
            $spamscore = 0;
            if ($follower->followers_count < 10)
                $spamscore+= 1;
            if ($follower->friends_count < 10)
                $spamscore+= 1;
            if (intval($follower->screen_name) || $follower->screen_name == '0')
                $spamscore+= 3;
            if (intval($follower->name) || $follower->name == '0')
                $spamscore+= 3;
            if (stripos($follower->profile_image_url, 'default_profile_images') !== false)
                $spamscore+= 1;
            if ($spamscore > $spamlevel) {
                if (!in_array($follower->id, $spam_followers))
                    $spam_followers[] = $follower->id;
                continue;
            }
//            if (!isset($my_followers[$follower->id]))
            $my_followers[] = $follower;
        }
        if (count($spam_followers)) {
            $old_spam_followers = get_option('appsol_social_streams_tw_spam_followers');
            $new_spam_followers = array_merge($old_spam_followers, array_diff($spam_followers, $old_spam_followers));
            update_option('appsol_social_streams_tw_spam_followers', $new_spam_followers);
        }
        return $my_followers;
    }

}

class appsolTwitterProfileCache extends appsolSocialStreamCache {

    const transient_base = 'tw_profile_';

    function __construct($instance) {
        parent::__construct($instance);
        $this->transient = self::transient_base . $this->instance['tw_user_id'];
        $this->getCache();
    }

    function updateCache() {
        if (!$this->instance['tw_user_id'])
            return false;
        $profile = $this->fetchData();
        if ($profile) {
            delete_transient($this->transient);
//            $expires = 60 * 60 * rand(24, 48);
            set_transient($this->transient, $profile, $this->daily_expiry);
//            $this->updateCacheLog($expires);
            $this->cache = $profile;
        }
        return $profile;
    }

    function fetchData() {
        $url = 'users/show';
        $params = array(
            'user_id' => $this->instance['tw_user_id']
        );
        $profile = $this->twitterConnect($url, $params);
        return $profile;
    }

}

class appsolGoogleplusActivitiesCache extends appsolSocialStreamCache {

    const transient_base = 'gp_activities';

    function __construct($instance) {
        parent::__construct($instance);
        $this->transient = self::transient_base . $this->instance['gp_user_id'];
        $this->getCache();
    }

    function updateCache() {
        if (!$this->instance['gp_user_id'])
            return false;
        $activity_stream = array();
        $activities = $this->fetchData();
        if ($activities) {
            delete_transient($this->transient);
            foreach ($activities['items'] as $activity) {
                $datetime = strtotime($activity['published']);
                $activity_stream[$datetime] = new appsolGooglePlusActivityItem($datetime);
                $activity_stream[$datetime]->setUpdate($activity);
            }
//            $expires = 60 * rand(30, 60);
            set_transient($this->transient, $activity_stream, $this->hourly_expiry);
//            $this->updateCacheLog($expires);
            $this->cache = $activity_stream;
        }
        return $activity_stream;
    }

    function fetchData() {
        if (!$this->googleConnect())
            return false;
        $batch_size = 20;
        $params = array('maxResults' => $batch_size);
        $activities = $this->google->activities->listActivities('me', 'public', $params);
        $this->google->refresh_token();
        return $activities;
    }

}

class appsolGoogleplusCircleCache extends appsolSocialStreamCache {

    const transient_base = 'gp_circle_';

    function __construct($instance) {
        parent::__construct($instance);
        $this->transient = self::transient_base . $this->instance['gp_user_id'];
        $this->getCache();
    }

    function updateCache() {
        if (!$this->instance['gp_user_id'])
            return false;
        $circle_connections = array();
        $circles = $this->fetchData();
        if ($circles) {
            delete_transient($this->transient);
            foreach ($circles as $gp_user) {
                $circle_connections[$gp_user['id']] = new appsolGooglePlusProfile();
                $circle_connections[$gp_user['id']]->setProfile($gp_user);
            }
//            $expires = 60 * 60 * rand(24, 48);
            set_transient($this->transient, $circle_connections, $this->daily_expiry);
//            $this->updateCacheLog($expires);
            $this->cache = $circle_connections;
        }
        return $circle_connections;
    }

    function fetchData() {
        if (!$this->googleConnect())
            return false;
        $profile = $this->google->people->get('me');
        $request = wp_remote_get($profile['url']);
        if (!is_wp_error($request)) {
            $result = wp_remote_retrieve_body($request);
            $dom = new DOMDocument();
            $dom->preserveWhiteSpace = false;
            if ($dom->loadHTML($result)) {
                $gp_users = array();
                $content = $dom->getElementById('content');
                $xpath = new DOMXPath($dom);
//                $circle_heading = $xpath->query('//h4[./@class="Pv rla"]', $content);
                $circle_heading = $xpath->query('//h4[contains(text(), "Have ' . $profile['name']['givenName'] . ' in circles")]', $content);
                if ($circle_heading->length > 0) {
                    $circle_text = $circle_heading->item(0)->textContent;
                    $circle_count = intval(substr($circle_text, strpos($circle_text, '(') + 1, (strpos($circle_text, ')') - strpos($circle_text, '('))));
                    $circles_totals = get_option('appsol_social_streams_gp_circles', array());
                    $circles_totals[$this->instance['gp_user_id']] = $circle_count;
                    update_option('appsol_social_streams_gp_circles', $circles_totals);
//                    $gp_user_list = $xpath->query('//h4[./@class="Pv rla"]/../div/div//a', $content);
                    $gp_user_list = $xpath->query('../div/div//a', $circle_heading->item(0));
                    if ($gp_user_list->length > 0) {
                        $len = $gp_user_list->length;
                        for ($i = 0; $i < $len; $i++) {
                            $gp_user_id = $gp_user_list->item($i)->getAttribute('oid');
                            $gp_users[] = $this->google->people->get($gp_user_id);
                        }
                    }
                }
                return $gp_users;
            }
//            preg_match('/<h4 class="Pv rla">([\s\w]*\((\d*)\))<\/h4>/is', $result, $matches);
        }
        return false;
    }

}

class appsolGoogleplusProfileCache extends appsolSocialStreamCache {

    const transient_base = 'gp_profile_';

    function __construct($instance) {
        parent::__construct($instance);
        $this->transient = self::transient_base . $this->instance['gp_user_id'];
        $this->getCache();
    }

    function updateCache() {
        if (!$this->instance['gp_user_id'])
            return false;
        $profile = $this->fetchData();
        if ($profile) {
            delete_transient($this->transient);
//            $expires = 60 * 60 * rand(24, 48);
            set_transient($this->transient, $profile, $this->daily_expiry);
//            $this->updateCacheLog($expires);
            $this->cache = $profile;
        }
        return $profile;
    }

    function fetchData() {
        if (!$this->googleConnect())
            return false;
        $profile = $this->google->people->get('me');
        $this->google->refresh_token();
        return $profile;
    }

}

class appsolFacebookPostCache extends appsolSocialStreamCache {

    const transient_base = 'fb_post_';

    function __construct($instance) {
        parent::__construct($instance);
        $this->transient = self::transient_base . $this->instance['fb_user_id'];
        $this->getCache();
    }

    function updateCache() {
        if (!$this->instance['fb_user_id'])
            return false;
        $post_stream = array();
        $posts = $this->fetchData();
        if ($posts) {
            foreach ($posts['data'] as $post) {
                $profile = ($post['from']['id'] != $this->facebook->profile['id']) ?
                        $this->facebook->api('/' . $post['from']['id']) : $this->facebook->profile;
                $datetime = strtotime($post['created_time']);
                $post_stream[$datetime] = new appsolFacebookWallItem($datetime);
                $post_stream[$datetime]->setUpdate($post, $profile);
            }
//            $expires = 60 * rand(30, 60);
            set_transient($this->transient, $post_stream, $this->hourly_expiry);
//            $this->updateCacheLog($expires);
            $this->cache = $post_stream;
        }
        return $post_stream;
    }

    function fetchData() {
        if (!$this->facebookConnect())
            return false;
        try {
            $feed = '/me/' . $this->instance['fb_feed'];
            $posts = $this->facebook->api($feed);
        } catch (FacebookApiException $e) {
            $msg = '<strong>Error:</strong>' . $e->__toString();
            update_option('appsol_fb_last_msg', $msg);
            return false;
        }
        return $posts;
    }

}

class appsolFacebookFriendCache extends appsolSocialStreamCache {

    const transient_base = 'fb_friend_';

    function __construct($instance) {
        parent::__construct($instance);
        $this->transient = self::transient_base . $this->instance['fb_user_id'];
        $this->getCache();
    }

    function updateCache() {
        if (!$this->instance['fb_user_id'])
            return false;
        $facebook_connections = array();
        $friends = $this->fetchData();
        if ($friends) {
            delete_transient($this->transient);
            foreach ($friends as $friend) {
                $facebook_connections[$friend['id']] = new appsolFacebookProfile('facebook');
                $facebook_connections[$friend['id']]->setProfile($friend);
            }
//            $expires = 60 * 60 * rand(24, 48);
            set_transient($this->transient, $facebook_connections, $this->daily_expiry);
//            $this->updateCacheLog($expires);
            $this->cache = $facebook_connections;
        }
        return $facebook_connections;
    }

    function fetchData() {
        if (!$this->facebookConnect())
            return false;
        $batch_size = 50;
        try {
            $friends = $this->facebook->api('/me/friends');
        } catch (FacebookApiException $e) {
            $msg = '<strong>Error:</strong>' . $e->__toString();
            update_option('appsol_fb_last_msg', $msg);
            return false;
        }
        $my_friends = array();
        $show_friends = array();
//        $friend_total = count($friends['data']);
        $friend_totals = get_option('appsol_social_streams_fb_followers', array());
        $friend_totals[$this->instance['fb_user_id']] = count($friends['data']);
        update_option('appsol_social_streams_fb_followers', $friend_totals);
//        if ($friend_total > $this->instance['show_connections'])
        $show_friends = ($friend_totals[$this->instance['fb_user_id']] > $batch_size) ?
                array_rand($friends['data'], $batch_size) : $friends['data'];
//            $show_friends = array_rand($friends['data'], $this->instance['show_connections']);
        foreach ($show_friends as $friend_id) {
            $my_friends[$friends['data'][$friend_id]['id']] = $this->facebook->api('/' . $friends['data'][$friend_id]['id']);
        }
        return $my_friends;
    }

}

class appsolFacebookProfileCache extends appsolSocialStreamCache {

    const transient_base = 'fb_profile_';

    function __construct($instance) {
        parent::__construct($instance);
        $this->transient = self::transient_base . $this->instance['fb_user_id'];
        $this->getCache();
    }

    function updateCache() {
        if (!$this->instance['fb_user_id'])
            return false;
        $fb_profile = $this->fetchData();
        if ($fb_profile) {
            delete_transient($this->transient);
//            $expires = 60 * 60 * rand(24, 48);
            set_transient($this->transient, $fb_profile, $this->daily_expiry);
//            $this->updateCacheLog($expires);
            $this->cache = $fb_profile;
        }
        return $fb_profile;
    }

    function fetchData() {
        if (!$this->facebookConnect())
            return false;
        try {
            $profile = $this->facebook->api('/' . $this->instance['fb_user_id']);
        } catch (FacebookApiException $e) {
            $msg = '<strong>Error:</strong>' . $e->__toString();
            update_option('appsol_fb_last_msg', $msg);
            return false;
        }
        return $profile;
    }

}

class appsolFacebookAlbumsCache extends appsolSocialStreamCache {

    const transient_base = 'fb_album_';

    function __construct($instance) {
        parent::__construct($instance);
        $this->transient = self::transient_base . $this->instance['fb_user_id'];
        $this->getCache();
    }

    function updateCache() {
        if (!$this->instance['fb_user_id'])
            return false;
        $album_cache = array();
        $albums = $this->fetchData();
        if ($albums) {
            foreach ($albums['data'] as $album) {
                try {
                    $profile = ($album['from']['id'] != $this->facebook->profile['id']) ?
                            $this->facebook->api('/' . $album['from']['id']) : $this->facebook->profile;
                } catch (FacebookApiException $e) {
                    $msg = '<strong>Error:</strong>' . $e->__toString();
                    update_option('appsol_fb_last_msg', $msg);
                    return false;
                }
                $datetime = strtotime($album['created_time']);
                $album_cache[$datetime] = $album;
            }
            set_transient($this->transient, $album_cache, $this->daily_expiry);
            $this->cache = $album_cache;
        }
        return $album_cache;
    }

    function fetchData() {
        if (!$this->facebookConnect())
            return false;
        try {
            $albums = $this->facebook->api('/me/albums');
        } catch (FacebookApiException $e) {
            $msg = '<strong>Error:</strong>' . $e->__toString();
            update_option('appsol_fb_last_msg', $msg);
            _log($e->__toString());
            return false;
        }
        return $albums;
    }

}

class appsolFacebookAlbumCache extends appsolSocialStreamCache {

    const transient_base = 'fb_album_';

    function __construct($instance) {
        parent::__construct($instance);
        $this->transient = self::transient_base . $this->instance['fb_user_id'] . '_' . $this->instance['fb_album_id'];
        $this->getCache();
    }

    function updateCache() {
        if (!$this->instance['fb_user_id'] || !$this->instance['fb_album_id'])
            return false;
        $album_cache = array();
        $album = $this->fetchData();
        if ($album) {
            try {
                $profile = ($album['from']['id'] != $this->facebook->profile['id']) ?
                        $this->facebook->api('/' . $album['from']['id']) : $this->facebook->profile;
            } catch (FacebookApiException $e) {
                $msg = '<strong>Error:</strong>' . $e->__toString();
                update_option('appsol_fb_last_msg', $msg);
                return false;
            }
            $datetime = strtotime($album['created_time']);
            $album_item = new appsolFacebookGalleryItem($datetime, 'ul');
            $album_item->setUpdate($album, $profile);
            set_transient($this->transient, $album_item, $this->daily_expiry);
            $this->cache = $album_item;
        }
        return $album_item;
    }

    function fetchData() {
        if (!$this->facebookConnect())
            return false;
        try {
            $album = $this->facebook->api('/' . $this->instance['fb_album_id']);
        } catch (FacebookApiException $e) {
            $msg = '<strong>Error:</strong>' . $e->__toString();
            update_option('appsol_fb_last_msg', $msg);
            _log($e->__toString());
            return false;
        }
        try {
            $album['photos'] = $this->facebook->api('/' . $album['id'] . '/photos');
        } catch (FacebookApiException $e) {
            $msg = '<strong>Error:</strong>' . $e->__toString();
            update_option('appsol_fb_last_msg', $msg);
            _log($e->__toString());
            return false;
        }
        return $album;
    }

}

class appsolLinkedinUpdatesCache extends appsolSocialStreamCache {

    const transient_base = 'li_updates';

    function __construct($instance) {
        parent::__construct($instance);
        $this->transient = self::transient_base . $this->instance['li_user_id'];
        $this->getCache();
    }

    function updateCache() {
        if (!$this->instance['li_user_id'])
            return false;
        $updates_stream = array();
        $updates = $this->fetchData();
        if ($updates) {
            if ($updates->_total > 0) {
                $profile_cache = new appsolLinkedinProfileCache($this->instance);
                foreach ($updates->values as $update) {
                    $profile = $profile_cache->cache;
                    if ($profile->id != $update->updateContent->person->id)
                        $profile = $this->linkedin->profile($update->updateContent->person->id . ':(id,first-name,last-name,picture-url,public-profile-url)');
                    $datetime = intval(substr($update->timestamp, 0, 10));
                    $updates_stream[$datetime] = new appsolLinkedinUpdateItem($datetime);
                    $updates_stream[$datetime]->setUpdate($update, $profile);
                }
                delete_transient($this->transient);
//                $expires = 60 * rand(30, 60);
                set_transient($this->transient, $updates_stream, $this->hourly_expiry);
//                $this->updateCacheLog($expires);
                $this->cache = $updates_stream;
            }
        }
        return $updates_stream;
    }

    function fetchData() {
        if (!$this->linkedinConnect())
            return false;
        $batch_size = 20;
        $query = '?type=SHAR&type=VIRL&type=STAT&scope=self&count=' . $batch_size;
        $response = $this->linkedin->updates($query);
        if ($response['success'] === TRUE)
            return json_decode($response['linkedin']);
        return false;
    }

}

class appsolLinkedinProfileCache extends appsolSocialStreamCache {

    const transient_base = 'li_profile_';

    function __construct($instance) {
        parent::__construct($instance);
        $this->transient = self::transient_base . $this->instance['li_user_id'];
        $this->getCache();
    }

    function updateCache() {
        if (!$this->instance['li_user_id'])
            return false;
        $li_profile = $this->fetchData();
        if ($li_profile) {
            delete_transient($this->transient);
            $expires = 60 * 60 * rand(24, 48);
            set_transient($this->transient, $li_profile, $expires);
//            $this->updateCacheLog($expires);
            $this->cache = $li_profile;
        }
        return $li_profile;
    }

    function fetchData() {
        $this->linkedinConnect();
        $response = $this->linkedin->profile('~:(id,first-name,last-name,picture-url,num-connections,num-connections-capped,num-recommenders,recommendations-received,relation-to-viewer,public-profile-url)');
        if ($response['success'])
            return json_decode($response['linkedin']);
        return false;
    }

}

class appsolLinkedinConnectionsCache extends appsolSocialStreamCache {

    const transient_base = 'li_connections_';

    function __construct($instance) {
        parent::__construct($instance);
        $this->transient = self::transient_base . $this->instance['li_user_id'];
        $this->getCache();
    }

    function updateCache() {
        if (!$this->instance['li_user_id'])
            return false;
        $linkedin_connections = array();
        $connections = $this->fetchData();
        if ($connections) {
            foreach ($connections as $connection) {
                $linkedin_connections[$connection->id] = new appsolLinkedinProfile();
                $linkedin_connections[$connection->id]->setProfile($connection);
            }
            delete_transient($this->transient);
            $expires = 60 * 60 * rand(24, 48);
            set_transient($this->transient, $linkedin_connections, $expires);
//            $this->updateCacheLog($expires);
            $this->cache = $linkedin_connections;
        }
        return $linkedin_connections;
    }

    function fetchData() {
        if (!$this->linkedinConnect())
            return false;
        $batch_size = 50;
        $query = '~/connections:(id,first-name,last-name,picture-url,public-profile-url)?start=0';
        $response = $this->linkedin->connections($query);
        if ($response['success'] !== TRUE)
            return false;
        $connections = json_decode($response['linkedin']);
        if (isset($connections->_total))
            update_option('appsol_li_connections', $connections->_total);
        $my_connections = array();
        foreach ($connections->values as $connection) {
            if (isset($connection->pictureUrl))
                $my_connections[$connection->id] = $connection;
        }
        $connections = array();
//        if (count($my_connections) > $this->instance['show_connections']) {
        if (count($my_connections) > $batch_size) {
//            $connection_ids = array_rand($my_connections, $this->instance['show_connections']);
            $connection_ids = array_rand($my_connections, $batch_size);
            foreach ($connection_ids as $id)
                $connections[$id] = $my_connections[$id];
            $my_connections = $connections;
        }
        return $my_connections;
    }

}

?>
