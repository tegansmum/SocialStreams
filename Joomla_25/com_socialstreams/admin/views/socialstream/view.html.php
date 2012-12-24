<?php

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// import Joomla view library
jimport('joomla.application.component.view');

/**
 * Attraction View
 */
class SocialStreamsViewSocialStream extends JView {

    /**
     * Attraction view display method
     * @return void
     */
    function display($tpl = null) {
        // get the Data
        if ($this->getLayout() !== 'modal') {
            $this->form = $this->get('Form');
            $this->item = $this->get('Item');
            $this->script = $this->get('Script');
        } else {
            $jinput = JFactory::getApplication()->input;
            $this->auth = $this->get('Auth');
            $this->network = $jinput->get('network', '', 'STRING');
            $this->success = $jinput->get('success', false, 'BOOL');
            $this->function = $jinput->get('function', false, 'STRING');
        }
        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode('<br />', $errors));
            return false;
        }
        if ($this->getLayout() !== 'modal')
            $this->addToolbar();

        // Display the template
        parent::display($tpl);

        // Set the document
        if ($this->getLayout() !== 'modal')
            $this->setDocument();
    }

    /**
     * Add the page title and toolbar.
     *
     * @since	1.6
     */
    protected function addToolbar() {
//        require_once JPATH_COMPONENT . '/helpers/visitmanager.php';
        JLoader::import('components.com_socialstreams.helpers.socialstreams', JPATH_ADMINISTRATOR);
        $input = JFactory::getApplication()->input;
        $input->set('hidemainmenu', true);
        $isNew = ($this->item->id == 0);
        $canDo = SocialStreamsHelper::getActions('auth');

        JToolBarHelper::title($isNew ? JText::_('COM_SOCIALSTREAMS_AUTH_NEW') : JText::_('COM_SOCIALSTREAMS_AUTH_EDIT'));
        if ($canDo->get('core.create') || $canDo->get('core.edit')) {
            JToolBarHelper::apply('socialstream.apply', JText::_('JTOOLBAR_APPLY'));
            JToolBarHelper::save('socialstream.save', JText::_('JTOOLBAR_SAVE'));
        }
        if ($canDo->get('core.create'))
            JToolBarHelper::save2new('socialstream.save2new', 'JTOOLBAR_SAVE_AND_NEW');
        JToolBarHelper::cancel('socialstream.cancel', $isNew ? JText::_('JTOOLBAR_CANCEL') : JText::_('JTOOLBAR_CLOSE'));
    }

    /**
     * Method to set up the document properties
     *
     * @return void
     */
    protected function setDocument() {
        $isNew = ($this->item->id == 0);
        $document = JFactory::getDocument();
        $document->setTitle($isNew ? JText::_('COM_SOCIALSTREAMS_AUTH_NEW') : JText::_('COM_SOCIALSTREAMS_AUTH_EDIT'));
//        $document->addScript(JURI::root() . $this->script);
//        $document->addScript(JURI::root() . "/administrator/components/com_visitmanager/views/site/submitbutton.js");
        JText::script('COM_SOCIALSTREAMS_AUTH_ERROR_UNACCEPTABLE');
    }

}

?>
