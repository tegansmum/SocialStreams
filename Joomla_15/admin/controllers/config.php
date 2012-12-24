<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
jimport('joomla.application.component.controller');
require_once JPATH_COMPONENT_ADMINISTRATOR . DS . 'lib' . DS . 'social_connections.php';

class SocialStreamsControllerConfig extends JController {

    private $networks = array();
//    private $config_file;

    function __construct() {
        parent::__construct();
        $jparams = JComponentHelper::getParams('com_socialstreams');
        $this->networks = array();
        if($jparams->get('facebook'))
            $this->networks['facebook'] = new appsolFacebookApi();
        if($jparams->get('twitter'))
            $this->networks['twitter'] = new appsolTwitterApi();
        if($jparams->get('linkedin'))
            $this->networks['linkedin'] = new appsolLinkedinApi();
        if($jparams->get('google'))
            $this->networks['google'] = new appsolGoogleApi();
        $this->registerTask('save', 'save');
        $this->registerTask('apply', 'apply');
        $this->registerTask('setaccess', 'setAccess');
        $this->registerTask('clearaccess', 'clearAccess');
    }

    function display() {
        global $option;
        $networks = array();
        $registry = & JFactory::getConfig();
        $session = & JFactory::getSession();
        foreach ($this->networks as $network => $api) {
            $networks[$network] = array(
                'name' => $api->params['nicename'],
                'state' => 'not-authenticated',
                'loginurl' => '',
                'logouturl' => '',
                'user' => null
            );
            foreach ($api->params['oauth'] as $key => $name) {
                $networks[$network][$key] = $registry->getValue('socialstreams.' . $network . '_' . $name, '');
                if (empty($networks[$network][$key]))
                    $networks[$network]['state'] = 'not-ready';
            }
            if ($networks[$network]['state'] == 'not-authenticated') {
                if ($api->user) {
                    $networks[$network]['user'] = $api->user;
                    $networks[$network]['state'] = 'authenticated';
                    $networks[$network]['logouturl'] = $api->get_logout_url();
                } else {
                    $networks[$network]['loginurl'] = $api->get_request_url();
                }
            }
            $networks[$network]['message'] = $session->get($network . '_last_msg', '', 'socialstreams');
        }
        $viewName = JRequest::getVar('view', 'config');
        $viewLayout = JRequest::getVar('layout', 'default');

        // get the view & set the layout
        $view = & $this->getView($viewName, 'html');
        $view->assignRef('social_networks', $networks);
        $view->setLayout($viewLayout);

        // Display the view
        $view->display();
    }

    function apply() {
        $this->update();
        $this->setRedirect('index.php?option=com_socialstreams&c=config');
    }

    function save() {
        $this->update();
        $this->setRedirect('index.php');
    }

    function update() {
        $data = JRequest::get('post');

        $registry = & JFactory::getConfig();
        foreach ($this->networks as $network => $api)
            foreach ($api->params['oauth'] as $key => $name)
                if (!empty($data[$network . '_' . $key]))
                    $registry->setValue('socialstreams.' . $network . '_' . $name, $data[$network . '_' . $key]);
    }
    
    function setAccess(){
        if($network = JRequest::getVar('network', false)){
            $method = 'set' . ucfirst($network) . 'Access';
            if(method_exists($this, $method))
                    $this->$method();
        }
    }
    
    function setFacebookAccess(){
        jimport('joomla.error.log');
        $errorLog =& JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => print_r($_REQUEST, true)));
        $this->networks['facebook']->set_access_token();
    }
    
    function setTwitterAccess(){
        $this->networks['twitter']->set_access_token();
    }
    
    function clearAccess(){
        if($network = JRequest::getVar('network', false)){
            $method = 'clear' . ucfirst($network) . 'Access';
            if(method_exists($this, $method))
                    $this->$method();
        }
    }
    
    function clearFacebookAccess(){
        $this->networks['facebook']->revoke_authentication();
    }
    
    function clearTwitterAccess(){
        $this->networks['facebook']->revoke_authentication();
    }
}

?>
