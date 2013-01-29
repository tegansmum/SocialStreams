<?php
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

jimport('joomla.application.component.helper');
if (JComponentHelper::isEnabled('com_socialstreams')) {
    // include the helper file
    require_once(dirname(__FILE__) . DS . 'helper.php');
    $accounts = $params->get('active_accounts', array());
    $limit = $params->get('connection_count', 8);
    // Set the refresh cache script
    $document = & JFactory::getDocument();
    $document->addStyleSheet(modSocialStreamsHelper::getBaseCss(), 'text/css', 'screen,projection');
    if($script = modSocialStreamsHelper::getCacheCycleScript($accounts, $limit))
        $document->addScriptDeclaration($script);
    
    // Get the item stream
    $stream = modSocialStreamsHelper::stream($accounts);
    // Get the Account Profiles
    $profiles = modSocialStreamsHelper::profiles($accounts);
    // If required get the connections
    if ($params->get('show_connections'))
        $connections = modSocialStreamsHelper::connections($accounts, $limit);

    // Load the template
    require(JModuleHelper::getLayoutPath('mod_socialstreams'));
}
?>
