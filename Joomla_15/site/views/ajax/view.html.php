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
class SocialcountViewSocialcount extends JView {

    function display($tpl = null) {
        $model = &$this->getModel();
        $response = "Hello World!";
        $this->assignRef('response', $response);

        parent::display($tpl);
    }

}

?>
