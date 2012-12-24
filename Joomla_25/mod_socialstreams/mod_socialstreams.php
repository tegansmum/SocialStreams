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
    $registry = & JFactory::getConfig();
    // import JFile
    jimport('joomla.filesystem.file');
    // Load the registry file before social_connections.php
//    if (JFile::exists(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_socialstreams' . DS . 'socialstreams.ini', 'INI', 'socialstreams'))
//        $registry->loadFile(JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_socialstreams' . DS . 'socialstreams.ini', 'INI', 'socialstreams');
//    require_once JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_socialstreams' . DS . 'lib' . DS . 'social_connections.php';
    // All set up server side, load some assets to the front end
    $document = & JFactory::getDocument();
    $document->addStyleSheet(JURI::base() . 'modules/mod_social_streams/css/default.css', 'text/css', 'screen,projection');
    if($itemscript = modSocialStreamsHelper::getCacheCycleScript('item'))
        $document->addScriptDeclaration($itemscript);
    if($profilescript = modSocialStreamsHelper::getCacheCycleScript('profile'))
        $document->addScriptDeclaration($profilescript);
    // Get the item stream
    $stream = modSocialStreamsHelper::stream($params);
    
    // If required get the connections
    if ($params->get('show_connections'))
        $connections = modSocialStreamsHelper::connections($params);
//    $jparams = JComponentHelper::getParams('com_socialstreams');
//    if ($jparams->get('facebook') && $params->get('facebook_include')){
//        $facebook = modSocialStreamsHelper::getNetwork('facebook');
//        $facebook_friends = $registry->getValue('socialstreams.facebook_friends');
//        if($registry->getValue('socialstreams.facebook_page_id')){
//            $facebook_user = modSocialStreamsHelper::getFacebookPage($registry->getValue('socialstreams.facebook_page_id'));
//        }else{
//            $facebook_user = $facebook->user;
//        }
//    }
//    if ($jparams->get('twitter') && $params->get('twitter_include')){
//        $twitter = modSocialStreamsHelper::getNetwork('twitter');
//        $twitter_followers = $registry->getValue('socialstreams.twitter_followers');
//    }
//    if ($jparams->get('linkedin') && $params->get('linkedin_include')){
//        $linkedin = modSocialStreamsHelper::getNetwork('linkedin');
//        $linkedin_connections = $registry->getValue('socialstreams.linkedin_connections');
//    }
//    if ($jparams->get('google') && $params->get('google_include')){
//        $google = modSocialStreamsHelper::getNetwork('google');
//        $google_circles = $registry->getValue('socialstreams.google_circles');
//    }
    // Load the template
    require(JModuleHelper::getLayoutPath('mod_social_streams'));
}
?>
