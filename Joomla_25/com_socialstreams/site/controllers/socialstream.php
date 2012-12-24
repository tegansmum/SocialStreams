<?php

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// import Joomla controllerform library
jimport('joomla.application.component.controllerform');

/**
 * Attraction Controller 
 */
class SocialStreamsControllerSocialStream extends JController {

    public function __construct($config = array()) {
        parent::__construct($config);

//        $this->registerTask('setauth', 'setAuth');
    }

//    public function setAuth() {
//        $jinput = JFactory::getApplication()->input;
//        if ($network = $jinput->get('network', false, 'STRING')) {
//            // use the same models as the back-end
//            $path = JPATH_COMPONENT_ADMINISTRATOR . DS . 'models';
//            $this->addModelPath($path);
//            // Set the model
//            $model = $this->getModel('SocialStream', '', array());
//            $view = $this->getView('SocialStream', 'html');
//            $view->setModel($model, true);
//            $jinput->set('success', $model->setAuth($network));
//        }
//        parent::display();
//    }

}

?>
