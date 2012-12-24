<?php

// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
jimport('joomla.application.component.model');

require_once JPATH_COMPONENT_ADMINISTRATOR . DS . 'lib' . DS . 'social_connections.php';

/**
 * Description of SocialstreamsModelItems
 *
 * @author stuart
 */
class SocialstreamsModelItemcache extends JModel {

    public $_items = array();
    private $facebook = null;
    private $twitter = null;
    private $linkedin = null;
    private $googleplus = null;

    function __construct() {
        parent::__construct();
    }

    function setCache($network, $items) {
        $updates = array();
        $inserts = array();
        $cache = array();
        $item_class = 'appsol' . ucfirst($network) . 'Item';
        foreach ($items as $networkid => $item) {
            $item = json_decode(json_encode($item));
            $streamitem = new $item_class();
            $streamitem->setUpdate($item);
            $cache[$streamitem->id] = $streamitem;
            if ($cached_item = $this->getItem($network, $networkid)) {
                $updates[$networkid] = array('raw' => $item, 'item' => $streamitem);
            } else {
                $inserts[$networkid] = array('raw' => $item, 'item' => $streamitem);
            }
        }
        if (count($inserts))
            $this->insertItems($network, $inserts);
        if (count($updates))
            foreach ($updates as $networkid => $item)
                $this->updateItem($network, $networkid, $item);
        $this->clearCache($network);
        $this->_items[$network] = $cache;
//        uasort($this->_items[$network], array('SocialstreamsModelItemcache', 'sortByDate'));
    }

    function clearCache($network, $id = false) {
        $db = & JFactory::getDBO();
        $now = time();
        $query = 'DELETE FROM ' . $db->nameQuote('#__socialstreamitems');
        $query.= ' WHERE ' . $db->nameQuote('network') . ' = ' . $db->Quote($network);
        if ($id) {
            $query.= ' AND ' . $db->nameQuote('networkid') . ' = ' . $db->Quote($id);
        } else {
            $query.= ' AND ' . $db->nameQuote('expires') . ' < ' . (int) $now;
        }

        $db->setQuery($query);
        $result = $db->query();
        if ($result && $id && isset($this->_items[$network][$id]))
            unset($this->_items[$network][$id]);
        if ($result && !$id)
            $this->_items[$network] = null;
        return $result;
    }
    
    function cycleCaches($networks) {
        JModel::addIncludePath(JPATH_BASE . DS . 'components' . DS . 'com_socialstreams' . DS . 'models');
        $frontmodel = JModel::getInstance('FrontItemCache', 'SocialstreamsModel');
        if (!is_array($networks))
            $networks = array($networks);
        $network_caches = array();
        foreach ($networks as $network)
            $network_caches[$network] = 0;
        foreach ($network_caches as $network => $status) {
            $update_method = 'update' . ucfirst($network) . 'ItemCache';
            
            $items = $frontmodel->getCache($network, false);
            if (!count($items))
                if ($this->$update_method()){
                    $this->clearCache($network);
                    $network_caches[$network] = $frontmodel->getCache($network, false);;
                }
        }
        return $network_caches;
    }

    function insertItems($network, $items) {
        $db = & JFactory::getDBO();
        $now = time();
        $jparams = JComponentHelper::getParams('com_socialstreams');
        $cache_expires = $now + $jparams->get('item_period');
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialstreamsModelItemcache::insertItems'));
        $query = 'INSERT INTO ' . $db->nameQuote('#__socialstreamitems');
        $query.= ' (' . $db->nameQuote('network') . ', ' . $db->nameQuote('networkid') . ', ';
        $query.= $db->nameQuote('user') . ', ' . $db->nameQuote('date') . ', ';
        $query.= $db->nameQuote('item') . ', ' . $db->nameQuote('expires') . ') ';
        $query.= 'VALUES ';
        foreach ($items as $networkid => $item) {
            /**
             * @todo implement install check to see if >= PHP5.2 &json_encode available, otherwise use serialize
             */
            $stored_item = json_encode($item['raw']);
            $query.= '(' . $db->Quote($network) . ', ' . $db->Quote($networkid) . ', ';
            $query.= $db->Quote($item['item']->profile->id) . ', ' . $item['item']->id . ',';
            $query.= $db->Quote($stored_item) . ', ' . $cache_expires . '),';
        }
        $query = rtrim($query, ',');
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => $query));
        $db->setQuery($query);
        if ($db->query())
            return $db->insertid();
        return false;
    }

    function updateItem($network, $networkid, $item) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialstreamsModelItemcache::updateItem'));
        $db = & JFactory::getDBO();
        $now = time();
        $jparams = JComponentHelper::getParams('com_socialstreams');
        $cache_expires = $now + $jparams->get('item_period');
        $stored_item = json_encode($item['raw']);
        $query = 'UPDATE ' . $db->nameQuote('#__socialstreamitems');
        $query.= ' SET ' . $db->nameQuote('item') . ' = ' . $db->Quote($stored_item) . ', ';
        $query.= $db->nameQuote('expires') . ' = ' . $cache_expires;
        $query.= ' WHERE ' . $db->nameQuote('networkid') . ' = ' . $db->Quote($networkid);
        $query.= ' AND ' . $db->nameQuote('network') . ' = ' . $db->Quote($network);
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => $query));
        $db->setQuery($query);
        return $db->query();
    }

    function getItem($network, $networkid) {
        // If the profile is already cached locally, return that
        if (isset($this->_items[$network][$networkid]))
            return $this->_items[$network][$networkid];
        $db = & JFactory::getDBO();
        $query = 'SELECT * FROM ' . $db->nameQuote('#__socialstreamitems');
        $query.= ' WHERE ' . $db->nameQuote('network') . ' = ' . $db->Quote($network);
        $query.= ' AND ' . $db->nameQuote('networkid') . ' = ' . $db->Quote($networkid);
        $db->setQuery($query);

        if (!$stored_item = $db->loadObject())
            return false;

        $item_class = 'appsol' . ucfirst($network) . 'Item';
        /**
         * @todo implement install check to see if >= PHP5.2 &json_encode available, otherwise use serialize
         */
        $stream_item = new $item_class();
        if ($item = json_decode($stored_item->item))
            $stream_item->setUpdate($item);
        return $stream_item;
    }

    function facebookConnect() {
        if (!$this->facebook)
            $this->facebook = new appsolFacebookApi();
        return $this->facebook->user;
    }

    function updateFacebookItemCache() {
        if ($posts = $this->fetchFacebookPosts()) {
            $this->setCache('facebook', $posts);
            return true;
        }
        return false;
    }

    function fetchFacebookPosts($type = 'feed') {
        if (!$this->facebookConnect())
            return false;
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialstreamsModelItemcache::fetchFacebookPosts'));
        try {
            $feed = '/me/' . $type;
            $posts = $this->facebook->api($feed);
        } catch (FacebookApiException $e) {
            $session = & JFactory::getSession();
            $msg = '<strong>Error:</strong>' . $e->__toString();
            $session->set('facebook_last_msg', $msg, 'socialstreams');
            return false;
        }
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => print_r($posts, true)));
        $my_posts = array();
        foreach ($posts['data'] as $post){
            if(isset($post['privacy']) && $post['privacy']['value'] == 'EVERYONE')
                $my_posts[$post['id']] = $post;
        }
        return $my_posts;
    }

    function twitterConnect() {
        if (!$this->twitter)
            $this->twitter = new appsolTwitterApi();
        return $this->twitter->user;
    }

    function updateTwitterItemCache() {
        $registry = & JFactory::getConfig();
        $username = $registry->getValue('socialstreams.twitter_username');
        if ($tweets = $this->fetchTwitterTweets($username)) {
            $this->setCache('twitter', $tweets);
            return true;
        }
        return false;
    }

    function fetchTwitterTweets($username) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        if (!$this->twitterConnect())
            return false;
        $tw_trim_user = 1;
        $tw_include_rts = 1;
        $tw_include_entities = 1;
        $registry = & JFactory::getConfig();
        $jparams = JComponentHelper::getParams('com_socialstreams');
        $url = 'statuses/user_timeline';
        $params = array(
            'screen_name' => $username,
            'trim_user' => $jparams->get('trim_user'),
            'include_rts' => $jparams->get('include_retweets'),
            'include_entities' => $jparams->get('include_entities'),
            'exclude_replies' => $jparams->get('exclude_replies'),
            'count' => $jparams->get('stored_tweets')
        );
        $tweets = $this->twitter->get($url, $params);
        $my_tweets = array();
        foreach ($tweets as $tweet)
            $my_tweets[$tweet->id_str] = $tweet;
        return $my_tweets;
    }

}

?>
