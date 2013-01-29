<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
// no direct access
defined('_JEXEC') or die('Restricted access');

JLoader::import('joomla.application.component.model');

class modSocialStreamsHelper {

    function stream($accounts = array()) {
        JLoader::import('components.com_socialstreams.helpers.socialstreams', JPATH_ADMINISTRATOR);
        SocialStreamsHelper::log($accounts);
        JModel::addIncludePath(JPATH_BASE . DS . 'components' . DS . 'com_socialstreams' . DS . 'models');
        $model = JModel::getInstance('items', 'SocialStreamsModel');
        if (count($accounts))
            $model->setState('filter.accounts', $accounts);
        $items = $model->getItems();

        foreach ($items as &$item) {
            $ssitem = SocialStreamsHelper::getItem($item->network, 'li');
            if ($update = json_decode($item->item)) {
                if (!empty($item->profile) && $profile = json_decode($item->profile))
                    $update->profile = $profile;
                $ssitem->setUpdate($update);
            }
            $item->item = $ssitem;
        }
        return $items;
    }

    function connections($accounts = array(), $limit = 8) {
        JLoader::import('components.com_socialstreams.helpers.socialstreams', JPATH_ADMINISTRATOR);
        JModel::addIncludePath(JPATH_BASE . DS . 'components' . DS . 'com_socialstreams' . DS . 'models');
        $model = JModel::getInstance('profiles', 'SocialStreamsModel', array('ignore_request' => 1));
//        $model->setState('list.limit', $limit);
        if (count($accounts))
            $model->setState('filter.accounts', $accounts);
        $show_connections = array();
        $connections = $model->getItems();
        shuffle($connections);
        $connections = array_slice($connections, 0, $limit);
        foreach ($connections as &$connection) {
            $ssprofile = SocialStreamsHelper::getProfile($connection->network, 'li');
            if ($profile = json_decode($connection->profile))
                $ssprofile->setProfile($profile);
            $connection->profile = $ssprofile;
        }
        return $connections;
    }
    
    function profiles($accounts = array()){
        JLoader::import('components.com_socialstreams.helpers.socialstreams', JPATH_ADMINISTRATOR);
        $profiles = array();
        $auth_accounts = SocialStreamsHelper::getAuthenticatedNetworks();
        foreach ($auth_accounts as $account){
            if(!in_array($account['id'], $accounts))
                    continue;
            $profiles[$account['clientid']] = SocialStreamsHelper::getProfile($account['network'], 'li', $account['clientid']);
        }
        return $profiles;
    }

    function getCacheCycleScript($accounts, $limit) {
        $baseurl = JURI::base() . 'index.php?option=com_socialstreams&format=json';
        $varname = 'socialStreamRefresh';
        $accounts_qs = '';
        foreach ($accounts as $account)
            $accounts_qs.= '&accounts[]=' . urlencode($account);
        $script = <<<AJAX
            window.addEvent('domready',function() {
                // Retrieves a new set of latest items
                var updateItems = function(){
                    var stream = $$('.module .stream ul')
                    stream.fade('out')
                    try {
                        fetchItems = new Request.HTML({
                            url: '{$baseurl}&view=items{$accounts_qs}',
                            method:'get',
                            onSuccess: function(responseTree, responseElements, responseHTML, responseJavaScript){
                                if(typeof responseHTML != 'undefined')
                                    stream.set('html', responseHTML)
                                stream.fade('in')
                            }
                        }).send();
                    } catch (e) {
                       for (var i in e){
                            console.log(i + ': ' + e[i])
                            }
                    }
                }
                // Retrieves a new set of Profiles
                var updateProfiles = function(){
                    var connections = $$('.module .connections ul')
                    connections.fade('out')
                    try {
                        fetchProfiles = new Request.HTML({
                            url: '{$baseurl}&view=profiles&limit={$limit}{$accounts_qs}',
                            method:'get',
                            //update: connections,
                            onSuccess: function(responseTree, responseElements, responseHTML, responseJavaScript){
                                if(typeof responseHTML != 'undefined')
                                    connections.set('html', responseHTML)
                                connections.fade('in')
                            }
                        }).send();
                    } catch (e) {
                    for (var i in e){
                        console.log(i + ': ' + e[i])
                        }
                    }
                }
                // Pings the refresh endpoint to check for expired content
                var {$varname} = new Request.JSON({
                    url: '{$baseurl}&task=refresh',
                    method:'get',
                    onComplete: function(response){
                        console.log(response)
                        console.log(response.items)
                        console.log(response['items'])
                        console.log(response.profiles)
                        console.log(response['profiles'])
                        if(response['items'] > 0){
                            // There are new items
                            updateItems()
                        }
                        if(response['profiles'] > 0){
                            // There are new profiles
                            updateProfiles()
                        }
                        
                    }
                }).send();
            });
AJAX;
        return $script;
    }

    function getBaseCss() {
        return JURI::base() . 'modules/mod_socialstreams/css/default.css';
    }

}

?>
