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
class SocialstreamsCache extends JModel {

    function getFacebookCache() {
        $db =& JFactory::getDBO();
        $now = time();
        if (!$this->_socialcount) {
            $query = 'SELECT * FROM ' . $db->nameQuote('#__socialcount');
            $query.= ' WHERE ' . $db->nameQuote('name') . ' = ' . $db->Quote('fb_posts');
            $query.= ' AND ' . $db->nameQuote('expires') . ' > ' . $db->Quote($now);
            $this->_socialcount = $this->_getList($query, 0, 0);
        }
        return $this->_socialcount;
    }

}

?>
