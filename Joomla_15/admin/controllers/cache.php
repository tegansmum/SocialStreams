<?php

// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
jimport('joomla.application.component.controller');
require_once JPATH_COMPONENT_ADMINISTRATOR . DS . 'lib' . DS . 'social_connections.php';

class SocialStreamsControllerCache extends JController {

    private $networks = array();
//    private $config_file;

    function __construct() {
        parent::__construct();
        $this->networks = array();
        $jparams = JComponentHelper::getParams('com_socialstreams');
        if($jparams->get('facebook'))
            $this->networks['facebook'] = new appsolFacebookApi();
        if($jparams->get('twitter'))
            $this->networks['twitter'] = new appsolTwitterApi();
        if($jparams->get('linkedin'))
            $this->networks['linkedin'] = new appsolLinkedinApi();
        if($jparams->get('google'))
            $this->networks['google'] = new appsolGoogleApi();
        // use the same models as the front-end
        $path = JPATH_COMPONENT_SITE . DS . 'models';
        $this->addModelPath($path);
//        $this->config_file = JPATH_COMPONENT . DS . 'socialstreams.ini';
        $this->registerTask('update', 'update');
    }

    function display() {
        $viewName = JRequest::getVar('view', 'cache');
        $cachetype = JRequest::getVar('layout', 'profile');
        $network = JRequest::getVar('network', 'facebook');
        $user = JRequest::getVar('networkuser', '');
        $frontmodel = $this->getModel('Front' . ucfirst($cachetype) . 'Cache');
        $adminmodel = $this->getModel(ucfirst($cachetype) . 'Cache');
        // get the view & set the layout
        $view = & $this->getView($viewName, 'html');
        $view->setModel($frontmodel, true);
        $view->setModel($adminmodel);
        $view->assign('network', $network);
        $view->assign('cachetype', $cachetype);
        $view->setLayout($cachetype);
        // Display the view
        $view->display();
    }

    function update() {
        $registry = & JFactory::getConfig();
        $network = JRequest::getVar('network', 'facebook');
        $cachetype = JRequest::getVar('cachetype', 'profile');

        $model = $this->getModel($cachetype . 'cache');
        $method = 'update' . ucfirst($network) . ucfirst($cachetype) . 'Cache';
        if (method_exists($model, $method))
            $result = $model->$method();
        $cache_nicename = ucfirst($network) . ' ' . ucfirst($cachetype);
        $message = $result ?
                $cache_nicename . ' Updated' : $cache_nicename . ' Update failed';
        JRequest::set(array('network' => $network, 'layout' => $cachetype), 'get');
        $this->setRedirect('index.php?option=com_socialstreams&controller=cache&layout=' . $cachetype . '&network=' . $network, $message);
    }

}

?>
