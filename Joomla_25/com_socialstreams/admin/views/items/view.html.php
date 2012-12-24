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
class SocialstreamsViewItems extends JView {

    function display($tpl = null) {
        JLoader::import('components.com_socialstreams.helpers.socialstreams', JPATH_ADMINISTRATOR);
        $this->items = $this->get('Items');
        $this->pagination = $this->get('Pagination');
        $this->state = $this->get('State');
//        dump($this->items, 'Items');
        foreach ($this->items as &$item) {
            $ssitem = SocialStreamsHelper::getItem($item->network, 'div');
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

        $this->addToolBar();
        parent::display($tpl);
    }

    protected function addToolBar() {
        JToolBarHelper::title(JText::_('COM_SOCIALSTREAMS'), 'generic.png');
        JToolBarHelper::custom('items.refreshcache', 'refresh', 'refresh', 'Refresh Items', false, false);
        JToolBarHelper::deleteListX("Items deleted in Local Cache only", 'items.delete', 'JTOOLBAR_DELETE');
        JToolBarHelper::divider();
        JToolBarHelper::preferences('com_socialstreams', '400', '800');
    }

}

?>
