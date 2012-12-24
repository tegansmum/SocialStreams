<?php

// no direct access

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
jimport('joomla.application.component.view');

/**
 * Description of SocialcountViewSocialcount
 *
 * @author stuart
 */
class SocialstreamsViewSocialStreams extends JView {

    function display($tpl = 'json') {
        $jinput = JFactory::getApplication()->input;
        
        $this->response = new stdClass();
        $this->response->profiles = $jinput->get('profiles', 0, 'INTEGER');
        $this->response->items = $jinput->get('items', 0, 'INTEGER');
        
        parent::display($tpl);
    }

}

?>
