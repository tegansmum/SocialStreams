<?php
// No direct access
defined('_JEXEC') or die('Restricted access');

jimport('joomla.application.component.view');

/**
 * Description of SocialcountViewSocialcount
 *
 * @author stuart
 */
class SocialstreamsViewConfig extends JView {
    
    protected $social_networks;

    function display($tpl = null) {
        global $option;
        JToolBarHelper::title( JText::_( 'Social Streams' ), 'generic.png' );
        JToolBarHelper::apply();
        JToolBarHelper::save();
        JToolBarHelper::preferences('com_socialstreams', '200', '400');
//        $this->assignRef('social_networks', $this->social_networks);
        parent::display($tpl);
    }
    
    function setNetworks($networks){
        $this->social_networks = $networks;
    }

}
?>
