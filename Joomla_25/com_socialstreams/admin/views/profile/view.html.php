<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
jimport('joomla.application.component.view');

/**
 * Description of SocialstreamsViewProfilecache
 *
 * @author stuart
 */
class SocialstreamsViewProfile extends JView {

    function display($tpl = null) {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode('<br />', $errors));
            return false;
        }
        $this->addToolbar();

        // Display the template
        parent::display($tpl);

        // Set the document
        $this->setDocument();
    }

    /**
     * Add the page title and toolbar.
     *
     * @since	1.6
     */
    protected function addToolbar() {
        JLoader::import('components.com_socialstreams.helpers.socialstreams', JPATH_ADMINISTRATOR);
        $input = JFactory::getApplication()->input;
        $input->set('hidemainmenu', true);

        JToolBarHelper::title(JText::_('COM_SOCIALSTREAMS_PROFILE'));
        JToolBarHelper::cancel('socialstream.cancel', JText::_('JTOOLBAR_CLOSE'));
    }

    /**
     * Method to set up the document properties
     *
     * @return void
     */
    protected function setDocument() {
        $document = JFactory::getDocument();
        $document->setTitle(JText::_('COM_SOCIALSTREAMS_PROFILE'));
    }

}

?>
