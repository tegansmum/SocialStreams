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
class SocialStreamsControllerItems extends JControllerAdmin {

    /**
     * Proxy for getModel.
     * @since	2.5
     */
    public function getModel($name = 'Item', $prefix = 'SocialStreamsModel') {
        $model = parent::getModel($name, $prefix, array('ignore_request' => true));
        return $model;
    }

    public function refreshcache() {

        // Check for request forgeries
        JSession::checkToken() or jexit(JText::_('JINVALID_TOKEN'));

        // Initialise variables.
        $user = JFactory::getUser();

        if (!$user->authorise('core.edit', 'com_socialstreams.item')) {
            JError::raiseNotice(403, JText::_('You are not allowed to update the Profile Cache'));
        } else {
            $jinput = JFactory::getApplication()->input;
            $force = $jinput->get('force', TRUE, 'BOOLEAN');
            $model = $this->getModel();
            $model->refresh($force);
        }

        $this->setRedirect('index.php?option=com_socialstreams&view=items');
    }

}

?>
