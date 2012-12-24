<?php

/*
 * Fields:
 * Network: string safe social network name
 * User: username on the social network
 * Name: nice name on the social network
 * Image: profile image url
 * Url: public profile url
 * Profile: json encoded profile data
 * Expires: date when due to be refreshed
 * Created: date when last refreshed
 * NetworkId: internal ID for profile on social network
 */

class SocialStreamsTableSsProfiles extends JTable {

    function __construct(&$db) {
        parent::__construct('#__ss_profiles', 'id', $db);
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
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsTableSsProfiles::bind'));
        if (isset($array['profile'])) {
            // Check and prepare the Parameters data
            $registry = new JRegistry();
            if (is_array($array['profile']))
                $registry->loadArray($array['profile']);
            elseif (is_object($array['profile']))
                $registry->loadObject($array['profile']);
            // Do Stuff
            $array['profile'] = (string) $registry;
        }
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
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsTableSsProfiles::store'));
        if (empty($this->id)) {
            // New Row - Store the row
            parent::store($updateNulls);
        } else {
            // Edit existing Row - Get the old row
            $oldrow = JTable::getInstance('ssprofiles', 'SocialStreamsTable');
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
