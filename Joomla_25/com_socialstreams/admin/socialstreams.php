<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * Main entry point for the administration for Social Streams
 */

// Access check.
if (!JFactory::getUser()->authorise('core.manage', 'com_socialstreams')) {
    return JError::raiseWarning(404, JText::_('JERROR_ALERTNOAUTHOR'));
}

// require helper file
JLoader::register('SocialStreamsHelper', dirname(__FILE__) . DS . 'helpers' . DS . 'socialstreams.php');

// import joomla controller library
jimport('joomla.application.component.controller');
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
$controller->redirect();
?>
