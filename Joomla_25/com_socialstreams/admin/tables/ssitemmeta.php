<?php
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class SocialStreamsTableSsItemMeta extends JTable{
    
    function __construct(&$db) {
        parent::__construct('#__ss_item_meta', 'id', $db);
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
        if (isset($array['meta']) && is_array($array['meta'])) {
            // Check and prepare the Parameters data
            $registry = new JRegistry();
            $registry->loadArray($array['meta']);
            // Do Stuff
            $array['meta'] = (string) $registry;
        }
        return parent::bind($array, $ignore);
    }

    /**
     * method to store a row
     *
     * @param boolean $updateNulls True to update fields even if they are null.
     */
    function store($updateNulls = false) {
        if (empty($this->id)) {
            // New Row - Store the row
            parent::store($updateNulls);
        } else {
            // Edit existing Row - Get the old row
            $oldrow = JTable::getInstance('ssitemmeta', 'SocialStreamsTable');
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
