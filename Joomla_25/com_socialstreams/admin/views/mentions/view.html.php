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
class SocialstreamsViewMentions extends JView {

    function display($tpl = null) {
        global $option;
        
        $model = & $this->getModel();
        $mentions = array();
        $mentions['facebook'] = $model->fetchFacebookMentions();
        $mentions['twitter'] = $model->fetchTwitterMentions();
        $mentions['stumbleupon'] = $model->fetchStumbleuponMentions();
        $this->assignRef('cache', $mentions);
        $this->addToolBar();
        parent::display($tpl);
    }
    
    protected function addToolBar(){
        JToolBarHelper::title( JText::_(COM_SOCIALSTREAMS_MENTIONS), 'generic.png' );
        JToolBarHelper::apply();
        JToolBarHelper::save();
        JToolBarHelper::preferences('com_socialstreams', '200', '400');
    }
}
?>
