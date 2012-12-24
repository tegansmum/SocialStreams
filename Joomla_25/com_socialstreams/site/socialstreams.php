<?php

// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
// import joomla controller library
jimport('joomla.application.component.controller');
jimport('joomla.error.log');
// Get an instance of the controller prefixed by SocialStreams
$controller = JController::getInstance('SocialStreams');

// Perform the Request task
$input = JFactory::getApplication()->input;
$controller->execute($input->getCmd('task'));

// Redirect if set by the controller
$controller->redirect();
