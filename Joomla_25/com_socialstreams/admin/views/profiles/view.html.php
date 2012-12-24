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
class SocialstreamsViewProfiles extends JView {

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
    
    protected function addToolBar() {
        JToolBarHelper::title(JText::_('COM_SOCIALSTREAMS'), 'generic.png');
        JToolBarHelper::custom('profiles.refreshcache', 'refresh', 'refresh', 'Refresh Profiles', false, false);
        JToolBarHelper::deleteListX("Profiles deletd in Local Cache only", 'profiles.delete', 'JTOOLBAR_DELETE');
        JToolBarHelper::divider();
        JToolBarHelper::preferences('com_socialstreams', '400', '800');
    }

}
?>
