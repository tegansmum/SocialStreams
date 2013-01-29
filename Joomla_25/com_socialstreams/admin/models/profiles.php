<?php

// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
//jimport('joomla.application.component.model');

//require_once JPATH_COMPONENT_ADMINISTRATOR . DS . 'lib' . DS . 'social_connections.php';

/**
 * Description of Socialcount
 *
 * @author stuart
 */

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

class SocialstreamsModelProfiles extends JModelList {

    public $_profiles = array();
//    private $facebook = null;
//    private $twitter = null;
//    private $linkedin = null;
//    private $googleplus = null;
    
    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @since	1.6
     */
    protected function populateState($ordering = null, $direction = null) {
        // Initialise variables.
        $app = JFactory::getApplication('administrator');

        // Load the filter state.
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $state = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
        $this->setState('filter.state', $state);

        $network = $app->getUserStateFromRequest($this->context . '.filter.account', 'filter_account');
        $this->setState('filter.account', $network);

        // Load the parameters.
        $params = JComponentHelper::getParams('com_socialstreams');
        $this->setState('params', $params);

        // List state information.
        parent::populateState('p.name', 'asc');
    }

    /**
     * Method to build an SQL query to load the list data.
     *
     * @return	string	An SQL query
     */
    protected function getListQuery() {
        // Create a new query object.		
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $user = JFactory::getUser();
        // Select some fields
        $query->select($this->getState('list.select', 'p.id AS id, p.network AS network, p.name AS name, p.user AS user, ' .
                        'p.url AS url, p.image AS image, p.expires AS expires, p.created AS created '));
        // From the profiles table
        $query->from($db->quoteName('#__ss_profiles') . ' as p');
        
        if($this->getState('filter.account')){
            $account = $this->getState('filter.account');
            $query->where('p.client_id = ' . $account);
        }

        // Filter by search in title.
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('p.id = ' . (int) substr($search, 3));
            } else {
                $search = $db->Quote('%' . $db->escape($search, true) . '%');
                $query->where('(p.name LIKE ' . $search . ')');
            }
        }

        return $query;
    }

//    function clearCache($network, $user = false) {
//        $db = & JFactory::getDBO();
//        $now = time();
//        $query = 'DELETE FROM ' . $db->nameQuote('#__ss_profiles');
//        $query.= ' WHERE ' . $db->nameQuote('network') . ' = ' . $db->Quote($network);
//        if ($user) {
//            $query.= ' AND ' . $db->nameQuote('user') . ' = ' . $db->Quote($user);
//        } else {
//            $query.= ' AND ' . $db->nameQuote('expires') . ' < NOW()';
//        }
//
//        $db->setQuery($query);
//        $result = $db->query();
//        return $result;
//    }
//
//    function cycleCaches($networks) {
//        if (!is_array($networks))
//            $networks = array($networks);
//        $network_caches = array();
//        foreach ($networks as $network){
//            $network_caches[$network] = 0;
//            $update_method = 'update' . ucfirst($network) . 'ProfileCache';
//                if ($this->$update_method())
//                    $network_caches[$network] = 1;
//            $this->clearCache($network);
//        }
//        return $network_caches;
//    }
//
//    function insertProfiles($network, $profiles) {
//        $db = & JFactory::getDBO();
//        $now = time();
//        $jparams = JComponentHelper::getParams('com_socialstreams');
//        $cache_expires = $now + $jparams->get('profile_period');
//        $query = 'INSERT INTO ' . $db->nameQuote('#__socialstreamprofiles');
//        $query.= ' (' . $db->nameQuote('network') . ', ' . $db->nameQuote('user') . ', ';
//        $query.= $db->nameQuote('profile') . ', ' . $db->nameQuote('expires') . ') ';
//        $query.= 'VALUES ';
//        foreach ($profiles as $user => $profile) {
//            /**
//             * @todo implement install check to see if >= PHP5.2 &json_encode available, otherwise use serialize
//             */
//            $stored_profile = json_encode($profile);
//            $query.= '(' . $db->Quote($network) . ', ' . $db->Quote($user) . ', ';
//            $query.= $db->Quote($stored_profile) . ', ' . $cache_expires . '),';
//        }
//        $query = rtrim($query, ',');
//        $db->setQuery($query);
//        if ($db->query())
//            return $db->insertid();
//        return false;
//    }
//
//    function updateProfile($id, $profile) {
//        $db = & JFactory::getDBO();
//        $now = time();
//        $jparams = JComponentHelper::getParams('com_socialstreams');
//        $cache_expires = $now + $jparams->get('profile_period');
//        $stored_profile = json_encode($profile);
//        $query = 'UPDATE ' . $db->nameQuote('#__socialstreamprofiles');
//        $query.= ' SET ' . $db->nameQuote('profile') . ' = ' . $db->Quote($stored_profile) . ', ';
//        $query.= $db->nameQuote('expires') . ' = ' . $cache_expires;
//        $query.= ' WHERE ' . $db->nameQuote('user') . ' = ' . $id;
//        $db->setQuery($query);
//        return $db->query();
//    }
//
//    function getProfile($network, $user) {
//        // If the profile is already cached locally, return that
//        if (isset($this->_profiles[$network][$user]))
//            return $this->_profiles[$network][$user];
//        JLoader::import('components.com_socialstreams.helpers.socialstreams', JPATH_ADMINISTRATOR);
//        $db = & JFactory::getDBO();
//        $query = 'SELECT * FROM ' . $db->nameQuote('#__socialstreamprofiles');
//        $query.= ' WHERE ' . $db->nameQuote('network') . ' = ' . $db->Quote($network);
//        $query.= ' AND ' . $db->nameQuote('user') . ' = ' . $db->Quote($user);
//        $db->setQuery($query);
//
//        if (!$stored_profile = $db->loadObject())
//            return false;
//        $ssprofile = SocialStreamsHelper::getProfile($network, 'li');
//        if ($profile = json_decode($stored_profile->profile))
//            $ssprofile->setProfile($profile);
//        return $ssprofile;
//    }

//    function facebookConnect() {
//        if (!$this->facebook)
//            $this->facebook = new appsolFacebookApi();
//        return $this->facebook->user;
//    }
//
//    function fetchFacebookProfile($id = 'me') {
//        if (!$this->facebookConnect())
//            return false;
//        try {
//            $profile = $this->facebook->api('/' . $id);
//        } catch (FacebookApiException $e) {
//            $session = & JFactory::getSession();
//            $msg = '<strong>Error:</strong>' . $e->__toString();
//            $session->set('facebook_last_msg', $msg, 'socialstreams');
//            return false;
//        }
//        return array($profile['id'] => $profile);
//    }
//
//    function updateFacebookProfileCache() {
//        $profiles = array();
//        if ($profile = $this->fetchFacebookProfile())
//            $profiles = $profile;
//        if ($friends = $this->fetchFacebookFriends()) {
//            foreach ($friends as $id => $profile)
//                if (!isset($profiles[$id]))
//                    $profiles[$id] = $profile;
//        }
//        if (!count($profiles))
//            return false;
//        $this->setCache('facebook', $profiles);
//        return true;
//    }
//
//    function fetchFacebookFriends() {
//        if (!$this->facebookConnect())
//            return false;
////        jimport('joomla.error.log');
////        $errorLog = & JLog::getInstance();
////        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialstreamsModelProfilecache::fetchFacebookFriends'));
//        try {
//            $friends = $this->facebook->api('/me/friends');
//        } catch (FacebookApiException $e) {
//            $session = & JFactory::getSession();
//            $msg = '<strong>Error:</strong>' . $e->__toString();
////            $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => $msg));
//            $session->set('facebook_last_msg', $msg, 'socialstreams');
//            return false;
//        }
//        
////        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => print_r($friends, true)));
//        jimport('joomla.application.component.helper');
//        $registry = & JFactory::getConfig();
//        $my_friends = array();
//        $show_friends = array();
//        $friend_total = count($friends['data']);
//        $registry->setValue('socialstreams.facebook_friends', $friend_total);
//        $jparams = JComponentHelper::getParams('com_socialstreams');
//        $stored_connections = $jparams->get('stored_connections');
////        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => $jparams->get('stored_connections')));
//        $show_friends = $friend_total > $stored_connections ?
//                array_rand($friends['data'], $stored_connections) : array_keys($friends['data']);
////        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => print_r($show_friends, true)));
//        foreach ($show_friends as $friend_id) {
//            $friend = $this->facebook->api('/' . $friends['data'][$friend_id]['id']);
//            $my_friends[$friend['id']] = $friend;
//        }
//        return $my_friends;
//    }
//
//    function twitterConnect() {
//        if (!$this->twitter)
//            $this->twitter = new appsolTwitterApi();
//        return $this->twitter->user;
//    }
//
//    function fetchTwitterFollowers($screen_name) {
//        if (!$this->twitterConnect())
//            return false;
//        $registry = & JFactory::getConfig();
//        $spam_followers = (array) explode(',', $registry->getValue('socialstreams.twitter_spam_followers'));
//        $jparams = JComponentHelper::getParams('com_socialstreams');
//        $only_friends = $jparams->get('only_friends');
//        $url = 'followers/ids';
//        $params = array(
//            'cursor' => -1,
//            'screen_name' => $screen_name
//        );
//        $followers = $this->twitter->get($url, $params);
//        if ($this->twitter->lastStatusCode() > 200) {
//            $session = & JFactory::getSession();
//            $msg = '<strong>Code:</strong>' . $this->twitter->lastStatusCode();
//            $msg.= '<br /><strong>Request:</strong>' . $this->twitter->lastApiCall();
//            $session->set('twitter_last_msg', $msg, 'socialstreams');
//            return false;
//        }
//        $follower_total = count($followers->ids);
//        $registry->setValue('socialstreams.twitter_followers', $follower_total);
//        $my_followers = array();
//        if ($only_friends) {
//            // Get a list of Twitter users the user follows
//            $url = 'friends/ids';
//            $params = array(
//                'screen_name' => $screen_name,
//                'cursor' => -1
//            );
//            $my_friends = array();
//            while ($params['cursor'] != 0) {
//                $friends = $this->twitter->get($url, $params);
//                $params['cursor'] = $friends->next_cursor;
//                $my_friends = array_merge($my_friends, $friends->ids);
//            }
//            // Only add those who are in the friend list
//            foreach ($followers->ids as $follower_id)
//                if (in_array($follower_id, $my_friends))
//                    $my_followers[] = $follower_id;
//        } else {
//            // Remove followers who are in the Spam List
//            foreach ($followers->ids as $follower_id)
//                if (!in_array($follower_id, $spam_followers))
//                    $my_followers[] = $follower_id;
//        }
//        // Process the follower ID list
//            $followerbatchsize = 50;
//        if (count($my_followers) < $followerbatchsize) {
//            $show_followers = implode(',', $my_followers);
//        } else {
//            $show_followers = implode(',', array_rand($my_followers, $followerbatchsize));
//        }
//        $url = 'users/lookup';
//        $params = array(
//            'user_id' => $show_followers,
//            'include_entities' => '0'
//        );
//        $followers = $this->twitter->get($url, $params);
//        return $this->filterFollowers($followers);
//    }
//
//    function updateTwitterProfileCache() {
//        $registry = & JFactory::getConfig();
//        $user_name = $registry->getValue('socialstreams.twitter_username');
//        $followers = array();
//        if ($followers = $this->fetchTwitterFollowers($user_name)) {
//            $this->setCache('twitter', $followers);
//            return true;
//        }
//        return false;
//    }
//
//    function filterFollowers($followers) {
//        $registry = & JFactory::getConfig();
//        $spamlevel = 2;
//        $my_followers = array();
//        $spam_followers = (array) explode(',', $registry->getValue('socialstreams.twitter_spam_followers'));
//        foreach ($followers as $follower) {
//            $spamscore = 0;
//            if (in_array($follower->id, $spam_followers))
//                $spamscore+= 5;
//            if ($follower->followers_count < 10)
//                $spamscore+= 1;
//            if ($follower->friends_count < 10)
//                $spamscore+= 1;
//            if (intval($follower->screen_name) || $follower->screen_name == '0')
//                $spamscore+= 3;
//            if (intval($follower->name) || $follower->name == '0')
//                $spamscore+= 3;
//            if (stripos($follower->profile_image_url, 'default_profile_images') !== false)
//                $spamscore+= 1;
//            if ($spamscore > $spamlevel) {
//                $this->clearCache('twitter', $follower->id);
//                if (!in_array($follower->id, $spam_followers))
//                    $spam_followers[] = $follower->id;
//                continue;
//            }
//            if (!isset($my_followers[$follower->id]))
//                $my_followers[$follower->id] = $follower;
//        }
//        if (count($spam_followers)) {
//            $registry->setValue('socialstreams.twitter_spam_followers', implode(',', $spam_followers));
//        }
//        return $my_followers;
//    }

}

?>
