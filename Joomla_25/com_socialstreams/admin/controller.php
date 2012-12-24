<?php

// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
jimport('joomla.application.component.controller');

class SocialStreamsController extends JController {

    function __construct($cachable = false) {
        // load the helper class
        JLoader::import('components.com_socialstreams.helpers.socialstreams', JPATH_ADMINISTRATOR);
//        require_once JPATH_COMPONENT . '/helpers/socialstreams.php';
        // set default view if not set
        $view = JRequest::getCmd('view', 'SocialStreams');
        JRequest::setVar('view', $view);
        
        SocialStreamsHelper::addSubmenu($view);
        parent::__construct($cachable);
    }

}

?>
