<?php

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * Attraction View
 */
class SocialStreamsViewSocialStream extends JView {

    /**
     * Attraction view display method
     * @return void
     */
    function display($tpl = null) {
        // get the Data
        $jinput = JFactory::getApplication()->input;
        $this->auth = $this->get('Auth');
        $this->network = $jinput->get('network', '', 'STRING');
        $this->success = $jinput->get('success', false, 'BOOL');
        $this->function = $jinput->get('function', false, 'STRING');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode('<br />', $errors));
            return false;
        }

        // Display the template
        parent::display($tpl);
    }

}

?>
