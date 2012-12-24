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
class SocialstreamsModelFrontItemCache extends JModel {

    public $_cache = array();

    function getFacebookCache() {
        if (empty($this->_cache['facebook']))
            $this->_cache['facebook'] = $this->getCache('facebook');
        return count($this->_cache['facebook']);
    }

    function getTwitterCache() {
        if (empty($this->_cache['twitter']))
            $this->_cache['twitter'] = $this->getCache('twitter');
        return count($this->_cache['twitter']);
    }

    function getLinkedinCache() {
        if (empty($this->_cache['linkedin']))
            $this->_cache['linkedin'] = $this->getCache('linkedin');
        return count($this->_cache['linkedin']);
    }

    function getGoogleCache() {
        if (empty($this->_cache['google']))
            $this->_cache['google'] = $this->getCache('google');
        return count($this->_cache['google']);
    }

    function getCache($network, $expired = true) {
        $db = & JFactory::getDBO();
        $now = time();
        $query = 'SELECT * FROM ' . $db->nameQuote('#__socialstreamitems');
        $query.= ' WHERE ' . $db->nameQuote('network') . ' = ' . $db->Quote($network);
        if (!$expired)
            $query.= ' AND ' . $db->nameQuote('expires') . ' > ' . (int) $now;
        $query.= ' ORDER BY ' . $db->nameQuote('date') . ' DESC';
        $db->setQuery($query);
        $items = $db->loadObjectList('date');
        $item_cache = array();
        $item_class = 'appsol' . ucfirst($network) . 'Item';
        foreach ($items as $stored_item) {
            $cache_item = new $item_class();
            if ($item = json_decode($stored_item->item))
                $cache_item->setUpdate($item);
            while (isset($item_cache[$cache_item->id]))
                $cache_item->id++;
            $item_cache[$cache_item->id] = $cache_item;
        }
        return $item_cache;
    }

}

?>
