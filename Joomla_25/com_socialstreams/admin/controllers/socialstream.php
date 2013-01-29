<?php

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// import Joomla controllerform library
jimport('joomla.application.component.controllerform');

/**
 * Single default controller 
 */
class SocialStreamsControllerSocialStream extends JControllerForm {

    public function __construct($config = array()) {
        parent::__construct($config);

        $this->registerTask('setauth', 'setAuth');
    }

    /**
     * Name: setAuth
     * callback entry point for remote API OAuth authentication 
     */
    public function setAuth() {
        JLoader::import('components.com_socialstreams.helpers.socialstreams', JPATH_ADMINISTRATOR);
        $jinput = JFactory::getApplication()->input;
        if ($network = $jinput->get('network', false, 'STRING')) {
            // Set the model
            $model = $this->getModel('SocialStream', '', array());

            if (!$success = $model->setAuth($network, $id)) {
                $api = SocialStreamsHelper::getApi($network);
                $message = 'Authenticate failed on Network ' . $network . ' : ' . $api->error;
                $type = 'error';
            } else {
                $message = 'User authenticated on Network ' . $network;
                $type = 'info';
            }
        }

        parent::display();
        $this->setRedirect(JRoute::_('index.php?option=com_socialstreams&view=socialstreams', false), $message, $type);
    }

}

?>
