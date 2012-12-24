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
class SocialstreamsViewProfilecache extends JView {

    function display($tpl = null) {
        global $option;
        JToolBarHelper::title( JText::_( 'Social Streams' ), 'generic.png' );
        JToolBarHelper::apply();
        JToolBarHelper::save();
        
        $cache = $this->get('Cache', 'profilecache');
        $this->assignRef('cache', $cache);
        parent::display($tpl);
    }

}
?>
