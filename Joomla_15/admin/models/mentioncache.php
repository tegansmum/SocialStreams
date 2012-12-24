<?php

// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
jimport('joomla.application.component.model');

require_once JPATH_COMPONENT_ADMINISTRATOR . DS . 'lib' . DS . 'social_connections.php';
require_once JPATH_COMPONENT_ADMINISTRATOR . DS . 'lib' . DS . 'jcurl.php';

/**
 * Description of SocialstreamsModelMentions
 *
 * @author stuart
 */
class SocialstreamsModelMentionCache extends JModel {

    public $_mentions = array();
    private $facebook = null;
    private $twitter = null;
    private $linkedin = null;
    private $googleplus = null;

    function __construct() {
        parent::__construct();
    }

    function setCache($network, $mention) {
        $now = time();
        $mention['date'] = $now;
        if ($cached_item = $this->getItem($network, $mention['url'], $mention['articleid'])) {
            $this->updateMention($network, $mention);
        } else {
            $this->insertMention($network, $mention);
        }
        $this->_mentions[$network][$mention['url']] = $mention;
//        uasort($this->_items[$network], array('SocialstreamsModelItemcache', 'sortByDate'));
    }

    function cycleCache($url, $networks, $id=null) {
        foreach ($networks as $network) {
            $update_method = 'update' . ucfirst($network) . 'MentionCache';
            $this->$update_method($url, $id);
        }
        return $network_caches;
    }

    function insertMention($network, $mention) {
        $db = & JFactory::getDBO();
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialstreamsModelItemcache::insertItems'));
        $query = 'INSERT INTO ' . $db->nameQuote('#__socialstreammentions');
        $query.= ' (' . $db->nameQuote('network') . ', ' . $db->nameQuote('url') . ', ';
        $query.= $db->nameQuote('articleid') . ', ' . $db->nameQuote('count') . ', ';
        $query.= $db->nameQuote('date') . ') ';
        $query.= 'VALUES ';
        $query.= '(' . $db->Quote($network) . ', ' . $db->Quote($mention['url']) . ', ';
        if ($mention['articleid'])
            $query.= $mention['articleid'] . ', ';
        $query.= $mention['count'] . ', ' . $db->Quote($mention['date']) . ')';
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => $query));
        $db->setQuery($query);
        if ($db->query())
            return $db->insertid();
        return false;
    }

    function updateMention($network, $mention) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialstreamsModelItemcache::updateItem'));
        $db = & JFactory::getDBO();
        $query = 'UPDATE ' . $db->nameQuote('#__socialstreammentions');
        $query.= ' SET ' . $db->nameQuote('count') . ' = ' . $mention['count'] . ', ';
        $query.= $db->nameQuote('date') . ' = ' . $mention['date'];
        $query.= ' WHERE ' . $db->nameQuote('network') . ' = ' . $db->Quote($mention['network']);
        $query.= ' AND ' . $db->nameQuote('url') . ' = ' . $db->Quote($mention['url']);
        $query.= ' OR ' . $db->nameQuote('articleid') . ' = ' . $mention['articleid'];
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => $query));
        $db->setQuery($query);
        return $db->query();
    }

    function getItem($network, $url, $articleid = null) {
        // If the profile is already cached locally, return that
        if (isset($this->_mentions[$network][$url]))
            return $this->_items[$network][$url];
        $db = & JFactory::getDBO();
        $query = 'SELECT * FROM ' . $db->nameQuote('#__socialstreammentions');
        $query.= ' WHERE ' . $db->nameQuote('network') . ' = ' . $db->Quote($network);
        $query.= ' AND ' . $db->nameQuote('url') . ' = ' . $db->Quote($url);
        if ($articleid)
            $query.= ' OR ' . $db->nameQuote('articleid') . ' = ' . $db->Quote($articleid);
        $db->setQuery($query);

        if (!$stored_item = $db->loadObject())
            return false;
        return $stored_item;
    }

    function facebookConnect() {
        if (!$this->facebook)
            $this->facebook = new appsolFacebookApi();
        return $this->facebook->user;
    }

    function fetchFacebookMentions($url) {
//        SELECT object_id, post_id, user_id FROM like WHERE object_id IN (SELECT id FROM object_url WHERE url = "http://developers.facebook.com/")
        if (!$this->facebookConnect())
            return false;
        try {
            $query = new stdClass();
            $query->link_stats = 'SELECT url, normalized_url, share_count, like_count, comment_count, total_count, commentsbox_count, comments_fbid, click_count FROM link_stat WHERE url="http://www.countrymanfairs.co.uk/"';
            $query->comments = 'SELECT post_fbid, fromid, object_id, text, time, likes FROM comment WHERE object_id IN (SELECT comments_fbid FROM #link_stats)';
            dump($query, 'SQL Query');
            $mentions = $this->facebook->api('/fql?q=' . urlencode(json_encode($query)));
            dump($mentions, 'Mentions');
        } catch (FacebookApiException $e) {
            $session = & JFactory::getSession();
            $msg = '<strong>Error:</strong>' . $e->__toString();
            $session->set('facebook_last_msg', $msg, 'socialstreams');
            return false;
        }
        return $mentions;
    }

    function updateFacebookMentionCache($url, $id=null) {
        if ($fbmentions = $this->fetchFacebookMentions()) {
            $mention = array(
                'network' => 'facebook',
                'articleid' => $id,
                'url' => '',
                'count' => -1
            );
            foreach ($fbmentions['data'] as $result)
                if ($result['name'] == 'link_stats' && isset($result['fql_result_set'][0]['total_count'])) {
                    $mention['count'] = $result['fql_result_set'][0]['total_count'];
                    $mention['url'] = $result['fql_result_set'][0]['url'];
                }
            if ($mention['count'] > -1)
                $this->setCache('facebook', $mention);
            return true;
        }
        return false;
    }

    function twitterConnect() {
        if (!$this->twitter)
            $this->twitter = new appsolTwitterApi();
        return $this->twitter->user;
    }

    function fetchTwitterMentions() {
//        http://search.twitter.com/search.json?q=bbc.co.uk&include_entities=true
        if (!$this->twitterConnect())
            return false;
        $url = 'http://urls.api.twitter.com/1/urls/count.json';
        $params = array(
            'url' => 'www.countrymanfairs.co.uk/kelmarsh-game-and-country-fair'
        );
        $mentions = $this->twitter->get($url, $params);
        return $mentions;
    }

    function updateTwitterMentionCache($url, $id=null) {
        if ($twmentions = $this->fetchTwitterMentions()) {
            $mention = array(
                'network' => 'twitter',
                'articleid' => $id,
                'url' => '',
                'count' => -1
            );
            if (isset($twmentions->count)) {
                $mention['count'] = $twmentions->count;
                $mention['url'] = $twmentions->url;
            }
            if ($mention['count'] > -1)
                $this->setCache('twitter', $mention);
            return true;
        }
        return false;
    }

    function fetchStumbleuponMentions() {
//        http://www.stumbleupon.com/services/1.01/badge.getinfo?url=http://www.treehugger.com/
        $apiurl = 'http://www.stumbleupon.com/services/1.01/badge.getinfo?url=';
        $response = 0;
        try {
            $curl = JCurl::getAdapter($apiurl . 'http://www.countrymanfairs.co.uk/');
        } catch (Exception $e) {
            $session = & JFactory::getSession();
            $msg = '<strong>Error:</strong>' . $e->__toString();
            $session->set('stumbleupon_last_msg', $msg, 'socialstreams');
            return false;
        }
        $curl->setOptions(array(CURLOPT_FOLLOWLOCATION => FALSE));
        try {
            $response = $curl->fetch();
        } catch (Exception $e) {
            $session = & JFactory::getSession();
            $msg = '<strong>Error:</strong>' . $e->__toString();
            $session->set('stumbleupon_last_msg', $msg, 'socialstreams');
            return false;
        }
        if ($response && $response->http_code == 200)
            return json_decode($response->body);
        return $response;
    }

    function updateStumbleuponMentionCache($url, $id=null) {
        if ($sumentions = $this->fetchStumbleuponMentions()) {
            $mention = array(
                'network' => 'facebook',
                'articleid' => $id,
                'url' => '',
                'count' => -1
            );
            if ($sumentions->success && isset($sumentions->result)) {
                $mention['count'] = $sumentions->views;
                $mention['url'] = $sumentions->url;
            }
            if ($mention['count'] > -1)
                $this->setCache('stumbleupon', $mention);
            return true;
        }
        return false;
    }

}

?>
