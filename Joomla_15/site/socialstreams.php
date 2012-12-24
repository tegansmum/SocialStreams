<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
// import joomla controller library
jimport('joomla.application.component.controller');
// Require the base controller
$registry = & JFactory::getConfig();
// import JFile
jimport('joomla.filesystem.file');

// Load the registry file
if (JFile::exists(JPATH_COMPONENT_ADMINISTRATOR . DS . 'socialstreams.ini', 'INI', 'socialstreams'))
    $registry->loadFile(JPATH_COMPONENT_ADMINISTRATOR . DS . 'socialstreams.ini', 'INI', 'socialstreams');

// Get an instance of the controller prefixed by VisitManager
$controller = JController::getInstance('SocialStreams');

// Perform the Request task
$input = JFactory::getApplication()->input;

$controller->execute($input->getCmd('task'));

$ini = $registry->toString('INI', 'socialstreams');
// save INI file
jimport('joomla.filesystem.file');
JFile::write(JPATH_COMPONENT_ADMINISTRATOR . DS . 'socialstreams.ini', $ini);
// Redirect if set by the controller
$controller->redirect();
