<?php

// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
jimport('joomla.application.component.model');

/**
 * Description of SocialstreamsModelItems
 *
 * @author stuart
 */
class SocialstreamsModelTwitterItems extends SocialstreamsModelItems {

    public $_items = array();

    function getNetworkCache($network) {
        $db = & JFactory::getDBO();
        $query = 'SELECT * FROM ' . $db->nameQuote('#__socialstreamitems');
        $query.= ' WHERE ' . $db->nameQuote('network') . ' = ' . $db->Quote($network);
        $db->setQuery($query);
        $this->_oauth[$network] = $db->loadObject();
        if (!$this->_oauth[$network]) {
            JError::raiseError(404, 'Invalid Social Network');
        }

        return $this->_oauth[$network];
    }

}

?>
