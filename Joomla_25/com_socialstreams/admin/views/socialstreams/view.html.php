<?php

// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * Description of SocialcountViewSocialcount
 *
 * @author stuart
 */
class SocialStreamsViewSocialStreams extends JView {

    protected $items;
    protected $pagination;
    protected $state;

    function display($tpl = null) {
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            JError::raiseError(500, implode('<br />', $errors));
            return false;
        }
        
        $this->addToolBar();
        parent::display($tpl);
    }

    function setNetworks($networks) {
        $this->social_networks = $networks;
    }

    protected function addToolBar() {
        JToolBarHelper::title(JText::_('COM_SOCIALSTREAMS'), 'generic.png');
        JToolBarHelper::addNewX('socialstream.add');
        JToolBarHelper::editListX('socialstream.edit');
        JToolBarHelper::divider();
        JToolBarHelper::publishList('socialstreams.publish', 'JTOOLBAR_PUBLISH');
        JToolBarHelper::unpublishList('socialstreams.unpublish', 'JTOOLBAR_UNPUBLISH');
        JToolBarHelper::divider();
        JToolBarHelper::deleteListX("Account Removed", 'socialstreams.delete');
        JToolBarHelper::divider();
        JToolBarHelper::preferences('com_socialstreams', '400', '800');
    }

}

?>
