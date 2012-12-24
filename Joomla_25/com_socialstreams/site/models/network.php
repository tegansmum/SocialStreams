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
class ModelSocialcountNetwork extends JModel {
    public $_network = null;
    public $_socialcount = null;
    
    function __construct() {
        parent::__construct();
        $network = JRequest::getVar('network', '');
        $this->_network = $network;
    }

    function getByNetwork() {
        if (!$this->_socialcount) {
            $query = 'SELECT * FROM #__socialcount WHERE network = "' . $this->_network . '"';
            $this->_db->setQuery($query);
            $this->_socialcount = $this->_db->loadObject();
            if(!$this->_socialcount->id){
                JError::raiseError(404, 'Invalid Social Network');
            }
        }
        return $this->_socialcount;
    }

}

?>
