<?php
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * JSON View class for the Social Streams Component
 */
class SocialStreamsViewProfiles extends JView {

    // Overwriting JView display method
    function display($tpl = 'json') {
        JLoader::import('components.com_socialstreams.helpers.socialstreams', JPATH_ADMINISTRATOR);
        // Assign data to the view
        $this->items = $this->get('Items');
        
        foreach ($this->items as &$item) {
            $ssprofile = SocialStreamsHelper::getProfile($item->network, 'li');
            if ($profile = json_decode($item->profile))
                $ssprofile->setProfile($profile);
            $item->profile = $ssprofile;
        }
        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode('<br />', $errors));
            return false;
        }
        // Display the view
        parent::display($tpl);
    }

}

?>
