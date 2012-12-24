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
class SocialstreamsViewStream extends JView {

    function display($tpl = null) {

        if(!$this->get(ucfirst($this->network) . 'Cache')){
            $adminmodel = &$this->getModel();
        }
        $this->assignRef('cache', $cache);
        parent::display($tpl);
    }

}

?>
