<?php

// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
jimport('joomla.application.component.controller');
require_once JPATH_COMPONENT_ADMINISTRATOR . DS . 'lib' . DS . 'social_connections.php';

/**
 * Description of SocialstreamsController
 *
 * @author stuart
 */
class SocialstreamsController extends JController {

    private $networks = array();

    function __construct() {
        parent::__construct();
        // use the same models as the back-end
        $path = JPATH_COMPONENT_ADMINISTRATOR . DS . 'models';
        $this->addModelPath($path);
        $jparams = JComponentHelper::getParams('com_socialstreams');
        $this->networks = array();
        if ($jparams->get('facebook'))
            $this->networks['facebook'] = new appsolFacebookApi();
        if ($jparams->get('twitter'))
            $this->networks['twitter'] = new appsolTwitterApi();
        if ($jparams->get('linkedin'))
            $this->networks['linkedin'] = new appsolLinkedinApi();
        if ($jparams->get('google'))
            $this->networks['google'] = new appsolGoogleApi();
        $this->registerTask('cycle', 'cycleCaches');
        $this->registerTask('count', 'updateCount');
    }

    /**
     * Method to display the view
     *
     * @access    public
     */
    function display() {
        $document = & JFactory::getDocument();
        $viewName = JRequest::getVar('view', 'stream');
        $cachetype = JRequest::getVar('layout', 'item');
        $view = & $this->getView($viewName, 'html');
        $model = & $this->getModel('Front' . ucfirst($cachetype) . 'Cache');
        if (!JError::isError($model)) {
            $view->setModel($model, true);
        }
        $view->assignRef('networks', array_keys($this->networks));
        $view->setLayout($cachetype);
        $view->display();
    }

    function cycleCaches() {
        global $mainframe;
        $cachetypes = array('profile' => 0, 'item' => 0);
        $registry = & JFactory::getConfig();
        $jparams = JComponentHelper::getParams('com_socialstreams');
        $now = time();
        // Run updates on each cache type
        foreach ($cachetypes as $cachetype => &$cache) {
            $model = $this->getModel($cachetype . 'cache');
            $period = intval($jparams->get($cachetype . '_period'), 10);
            $oldestcachedate = $now + $period;
            $oldestcache = '';
            // Get the names of the active social networks
            $networks = array_keys($this->networks);
            // Find the oldest cache
            foreach ($networks as $network) {
                $lastcachedate = $registry->getValue('socialstreams.' . $network . '_' . $cachetype . '_next_cache_date', 0);

                if ($lastcachedate < $oldestcachedate) {
                    $oldestcachedate = $lastcachedate;
                    $oldestcache = $network;
                }
            }
            // Does the oldest cache need renewing?
            if ($oldestcachedate < $now) {
                $cache = $model->cycleCaches($oldestcache);
                // Update the next cache date
                foreach ($cache as $network => $result)
                    if ($result)
                        $registry->setValue('socialstreams.' . $network . '_' . $cachetype . '_next_cache_date', $now + $period);
            }
        }
        // We're not going to hand back to the component file, so we'd better store the registry now
        $ini = $registry->toString('INI', 'socialstreams');
        // save INI file
        jimport('joomla.filesystem.file');
        JFile::write(JPATH_COMPONENT_ADMINISTRATOR . DS . 'socialstreams.ini', $ini);
        // Get the document object.
        $document = & JFactory::getDocument();
        // Set the MIME type for JSON output.
        $document->setMimeEncoding('application/json');
        // Change the suggested filename.
        JResponse::setHeader('Content-Disposition', 'attachment;filename="' . $network . '.json"');
        // Output the JSON data.
        echo json_encode($cachetypes);
        $mainframe->close();
    }
    
    function updateCount(){
        global $mainframe;
        $now = time();
        $networks = array(
            'facebook' => 1,
            'twitter' => 1,
            'stumbleupon' => 1
        );
        $url = urldecode(JRequest::getVar('url', ''));
        $articleid = JRequest::getVar('articleid', '');
        $catid = JRequest::getVar('catid', '');
        $frontmodel = $this->getModel('frontmentioncache');
        $mentions = $frontmodel->getUrl($url);
        foreach($mentions as $mention)
            if($mention->date > $now - 86400)
                unset ($networks[$mention->network]);
        if(count($networks)){
            $adminmodel = $this->getModel('mentioncache');
            $id = empty($catid)? $articleid : $catid;
            $adminmodel->cycleCache($url, array_keys($networks), $id);
        }
        // Get the document object.
        $document = & JFactory::getDocument();
        // Set the MIME type for JSON output.
        $document->setMimeEncoding('application/json');
        // Change the suggested filename.
        JResponse::setHeader('Content-Disposition', 'attachment;filename="' . $network . '.json"');
        // Output the JSON data.
        echo json_encode($cachetypes);
        $mainframe->close();
    }

}

?>
