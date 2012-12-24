<?php

// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
jimport('joomla.application.component.model');

/**
 * Description of Socialcount
 *
 * @author stuart
 */
class SocialstreamsModelFrontMentionCache extends JModel {

    public $_cache = array();

//    function getFacebookCache() {
//        if (empty($this->_cache['facebook']))
//            $this->_cache['facebook'] = $this->getCache('facebook');
//        return count($this->_cache['facebook']);
//    }
//
//    function getTwitterCache() {
//        if (empty($this->_cache['twitter']))
//            $this->_cache['twitter'] = $this->getCache('twitter');
//        return count($this->_cache['twitter']);
//    }
//
//    function getLinkedinCache() {
//        if (empty($this->_cache['linkedin']))
//            $this->_cache['linkedin'] = $this->getCache('linkedin');
//        return count($this->_cache['linkedin']);
//    }
//
//    function getGoogleCache() {
//        if (empty($this->_cache['google']))
//            $this->_cache['google'] = $this->getCache('google');
//        return count($this->_cache['google']);
//    }
    function getTopPages() {
        return $this->getCache(false, true, 10);
    }
    
    function getUrl($url){
        return $this->getCache($url);
    }

    function getCache($url = false, $count = false, $limit = false) {
        $db = & JFactory::getDBO();
        $now = time();
        $query = 'SELECT * FROM ' . $db->nameQuote('#__socialstreammentions');
        if ($url)
            $query.= ' WHERE ' . $db->nameQuote('url') . ' = ' . $db->Quote($url);
//        if($article)
//            $query.= ' AND ' . $db->nameQuote('articleid') . ' = ' . (int) $article;
        if ($count)
            $query.= ' ORDER BY ' . $db->nameQuote('count') . ' DESC';
        else
            $query.= ' ORDER BY ' . $db->nameQuote('date') . ' DESC';
        if ($limit)
            $query.= ' LIMIT ' . $limit;
        $db->setQuery($query);
        $mentions = $db->loadObjectList('url');
        return $mentions;
//        }
//        return $this->_cache[$network];
    }

}

?>
