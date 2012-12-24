<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
jimport('joomla.application.component.controller');
require_once JPATH_COMPONENT_ADMINISTRATOR . DS . 'lib' . DS . 'social_connections.php';

class SocialStreamsControllerMentions extends JController {

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
        $this->registerTask('update', 'update');
    }
    
    function display() {
        $viewName = JRequest::getVar('view', 'mentions');
        $network = JRequest::getVar('network', 'facebook');
        $layout = JRequest::getVar('layout', 'default');
        $adminmodel = $this->getModel('MentionCache');
        // get the view & set the layout
        $view = & $this->getView($viewName, 'html');
        $view->setModel($adminmodel, true);
        $view->assign('network', $network);
        $view->setLayout($layout);
        // Display the view
        $view->display();
    }
}
?>
