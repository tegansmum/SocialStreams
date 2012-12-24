<?php

// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
// import Joomla controller library
jimport('joomla.application.component.controller');

/**
 * Description of SocialstreamsController
 *
 * @author stuart
 */
class SocialStreamsController extends JController {

    private $networks = array();

    function __construct($default = array()) {
        parent::__construct($default);

        // use the same models as the back-end
        $path = JPATH_COMPONENT_ADMINISTRATOR . DS . 'models';
        $this->addModelPath($path);
        $jparams = JComponentHelper::getParams('com_socialstreams');

        $this->registerTask('refresh', 'refreshCache');
    }

    /**
     * Method to display a view.
     *
     * @param	boolean			If true, the view output will be cached
     * @param	array			An array of safe url parameters and their variable types, for valid values see {@link JFilterInput::clean()}.
     *
     * @return	JController		This object to support chaining.
     * @since	1.5
     */
    public function display($cachable = false, $safeurlparams = array()) {
//        $cachable = true;
        parent::display($cachable, $safeurlparams);

        return $this;
    }

    /**
     * Name: refreshCache
     * calls the refresh method on the Profile and / or Item models
     * If the request parameter 'type' is not set, both are called to check for new entries and the totals returned
     * If the request parameter 'type' is set the appropriate model 
     */
    public function refreshCache() {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsController::refreshCache'));

        $jinput = JFactory::getApplication()->input;
        $type = $jinput->get('type', '', 'STRING');
        $force = $jinput->get('force', FALSE, 'BOOLEAN');
        $profile_count = $item_count = 0;
        if (!$type || $type = 'profiles') {
            // Refresh Profiles
            $model = $this->getModel('Profile', 'SocialStreamsModel');
            $profile_count = $model->refresh($force);
        }

        if (!$type || $type = 'items') {
            // Refresh Items
            $model = $this->getModel('Item', 'SocialStreamsModel');
            $item_count = $model->refresh($force);
        }
//        if ($type == 'profiles' && $profile_count)
//            $this->setRedirect('index.php?option=com_socialstreams&view=profiles&format=json');
//        if ($type == 'items' && $item_count)
//            $this->setRedirect('index.php?option=com_socialstreams&view=items&format=json');
//        else
            $this->setRedirect('index.php?option=com_socialstreams&view=socialstreams&format=json&profiles=' . $profile_count . '&items=' . $item_count);
    }

}

?>
