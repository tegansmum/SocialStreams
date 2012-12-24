<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class SocialStreamsTableSsAuth extends JTable{
    
    function __construct(&$db) {
        parent::__construct('#__ss_auth', 'id', $db);
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
        if (empty($this->created_by)) {
            $user = JFactory::getUser();
            $this->created_by = $user->id;
        }

        if (empty($this->created_by_alias)) {
            $user = JFactory::getUser();
            $this->created_by_alias = $user->name;
        }

        if (empty($this->modified_by)) {
            $user = JFactory::getUser();
            $this->modified_by = $user->id;
        }
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
        if (isset($array['params']) && is_array($array['params'])) {
            // Check and prepare the Parameters data
            $registry = new JRegistry();
            $registry->loadArray($array['params']);
            // Do Stuff
            $array['params'] = (string) $registry;
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
            $this->created = JFactory::getDate()->toMySQL();
            parent::store($updateNulls);
        } else {
            // Edit existing Row - Get the old row
            $this->modified = JFactory::getDate()->toMySQL();
            $oldrow = JTable::getInstance('ssauth', 'SocialStreamsTable');
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
