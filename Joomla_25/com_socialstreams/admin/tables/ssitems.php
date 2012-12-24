<?php

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class SocialStreamsTableSsItems extends JTable {

    function __construct(&$db) {
        parent::__construct('#__ss_items', 'id', $db);
    }

    /**
     * Overloaded check function
     * Checks the class properties before binding
     *
     * @return	boolean
     * @see		JTable::check
     * @since	1.5
     */
    function check() {
            return true;
    }

    /**
     * Overloaded bind function
     *
     * @param	array		$hash named array
     * @return	null|string	null is operation was satisfactory, otherwise returns an error
     * @see JTable:bind
     * @since 1.5
     */
    public function bind($array, $ignore = array()) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsTableSsItems::bind'));
        
        if (isset($array['item'])) {
            // Check and prepare the Parameters data
            $registry = new JRegistry();
            if (is_array($array['item']))
                $registry->loadArray($array['item']);
            elseif (is_object($array['item']))
                $registry->loadObject($array['item']);
            // Do Stuff
            $array['item'] = (string) $registry;
        }
        $profile = JTable::getInstance('ssprofiles', 'SocialStreamsTable');
        if (!empty($array['profile_id'])) {
            if ($profile->load($array['profile_id']))
                $array['profile'] = $profile;
        } else {
            if ($profile->load(array('network' => $array['network'], 'networkid' => $array['profile']->id)))
                $array['profile'] = $profile;
        }

        $ignore = array_merge($ignore, array('profile'));
        return parent::bind($array, $ignore);
    }

    /**
     * method to store a row
     *
     * @param boolean $updateNulls True to update fields even if they are null.
     */
    function store($updateNulls = false) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsTableSsItems::store'));

        if (empty($this->id)) {
            // New Row - Store the row
            parent::store($updateNulls);
        } else {
            // Edit existing Row - Get the old row
            $oldrow = JTable::getInstance('ssitems', 'SocialStreamsTable');
            if (!$oldrow->load($this->id) && $oldrow->getError()) {
                $this->setError($oldrow->getError());
            }

            // Store the row
            parent::store($updateNulls);
        }
        return count($this->getErrors()) == 0;
    }

}

?>
