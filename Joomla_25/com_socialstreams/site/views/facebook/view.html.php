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
class SocialStreamsViewFacebook extends JView {

    function display($tpl = null) {
        
        $script = '<script src="//connect.facebook.net/en_GB/all.js"></script>';
        $this->assignRef('script', $script);

        parent::display($tpl);
    }

}

?>
