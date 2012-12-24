<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
// no direct access
defined('_JEXEC') or die('Restricted access');

JLoader::import('joomla.application.component.model');

class modSocialStreamsHelper {

    function stream(&$params) {
//        JLoader::import( 'frontitemcache', JPATH_BASE . DS . 'components' . DS . 'com_socialstreams' . DS . 'models' );
        JModel::addIncludePath(JPATH_BASE . DS . 'components' . DS . 'com_socialstreams' . DS . 'models');
        $model = JModel::getInstance('FrontItemCache', 'SocialstreamsModel');
        $stream = array();
        $jparams = JComponentHelper::getParams('com_socialstreams');
        if ($jparams->get('facebook'))
            if ($model->getFacebookCache()) {
                // Add the Wall Posts to the Stream
                foreach ($model->_cache['facebook'] as $key => $item) {
                    while (isset($stream[$key]))
                        $key++;
                    $stream[$key] = $item;
                }
            }
        if ($jparams->get('twitter'))
            if ($model->getTwitterCache()) {
                // Add the Tweets to the Stream
                foreach ($model->_cache['twitter'] as $key => $item) {
                    while (isset($stream[$key]))
                        $key++;
                    $stream[$key] = $item;
                }
            }
        if ($jparams->get('linkedin'))
            if ($model->getLinkedinCache()) {
                // Add the LinkedIn Updates to the Stream
                foreach ($model->_cache['linkedin'] as $key => $item) {
                    while (isset($stream[$key]))
                        $key++;
                    $stream[$key] = $item;
                }
            }
        if ($jparams->get('google'))
            if ($model->getGoogleCache()) {
                // Add the Google+ Updates to the Stream
                foreach ($model->_cache['google'] as $key => $item) {
                    while (isset($stream[$key]))
                        $key++;
                    $stream[$key] = $item;
                }
            }
//        dump($model->_cache, 'Item Model Cache');
        krsort($stream, SORT_NUMERIC);
        return $stream;
    }

    function connections(&$params) {
        JModel::addIncludePath(JPATH_BASE . DS . 'components' . DS . 'com_social_streams' . DS . 'models');
        $model = JModel::getInstance('FrontProfileCache', 'SocialstreamsModel');
        $stream = array();
        $jparams = JComponentHelper::getParams('com_socialstreams');
        $connections = array();
        if ($jparams->get('facebook'))
            if ($model->getFacebookCache())
            // Add Friends to Connections
                $connections = array_merge($connections, $model->_cache['facebook']);
        if ($jparams->get('twitter'))
            if ($model->getTwitterCache())
            // Add Followers to Connections
                $connections = array_merge($connections, $model->_cache['twitter']);
        if ($jparams->get('linkedin'))
            if ($model->getLinkedinCache())
            // Add Contacts to Connections
                $connections = array_merge($connections, $model->_cache['linkedin']);
        if ($jparams->get('google'))
            if ($model->getGoogleCache())
            // Add Circlers to Connections
                $connections = array_merge($connections, $model->_cache['google']);
//        dump($model->_cache, 'Profile Model Cache');
        // Shuffle together all the connections and return a subset
        shuffle($connections);
        $connections = array_slice($connections, 0, $params->get('connection_count'), true);
        return $connections;
    }
    
    function getFacebookPage($graphid){
        JModel::addIncludePath(JPATH_BASE . DS . 'components' . DS . 'com_social_streams' . DS . 'models');
        $model = JModel::getInstance('FrontProfileCache', 'SocialstreamsModel');
        return $model->getProfile('facebook', $graphid);
    }

    function getNetwork($network = '') {

        static $networks;
        if (!isset($networks))
            $networks = array();
        if ($network) {
            $apiclass = 'appsol' . ucfirst($network) . 'Api';
            $networks[$network] = new $apiclass();
            return $networks[$network];
        }
        return $networks;
    }
    
    function getCacheCycleScript(){
            $url = JURI::base() . 'index.php?option=com_socialstreams&task=cycle';
            $varname = 'cacheCycle';
            $script = <<<AJAX
            window.addEvent('domready',function() {
                var {$varname} = new Ajax('{$url}',{
                    method:'get'
                }).request();
            });
AJAX;
          return $script;
    }
}

?>
