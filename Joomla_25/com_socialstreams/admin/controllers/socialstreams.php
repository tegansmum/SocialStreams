<?php

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// import Joomla controlleradmin library
jimport('joomla.application.component.controlleradmin');

/**
 * Attractions Controller 
 */
class SocialStreamsControllerSocialStreams extends JControllerAdmin {

    /**
     * Proxy for getModel.
     * @since	2.5
     */
    public function getModel($name = 'SocialStream', $prefix = 'SocialStreamsModel') {
        $model = parent::getModel($name, $prefix, array('ignore_request' => true));
        return $model;
    }

}

?>
