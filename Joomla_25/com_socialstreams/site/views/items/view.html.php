<?php

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * HTML View class for the Tourism Component
 */
class SocialStreamsViewItems extends JView {

    protected $pagination;

    // Overwriting JView display method
    function display($tpl = null) {
        JLoader::import('components.com_socialstreams.helpers.socialstreams', JPATH_ADMINISTRATOR);
        // Assign data to the view
        $pagination = $this->get('Pagination');
        $this->state = $this->get('State');
        $this->items = $this->get('Items');
        foreach ($this->items as &$item) {
            $ssitem = SocialStreamsHelper::getItem($item->network, 'li');
            if ($update = json_decode($item->item)){
                if(!empty($item->profile) && $profile = json_decode($item->profile))
                    $update->profile = $profile;
                $ssitem->setUpdate($update);
            }
            $item->item = $ssitem;
        }

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode('<br />', $errors));
            return false;
        }
        $this->assignRef('pagination', $pagination);
        // Display the view
        parent::display($tpl);

        $this->setDocument();
    }

    private function setDocument() {
        $document = JFactory::getDocument();
//        $document->addScript(JURI::base() . 'media/com_visitmanager/js/jquery.appsol.tabs.js');
    }

}

?>
