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
        JToolBarHelper::title( JText::_( 'Social Streams' ), 'generic.png' );
        JToolBarHelper::apply();
        JToolBarHelper::save();
        JToolBarHelper::preferences('com_socialstreams', '200', '400');
        $model = & $this->getModel();
        dump($model, 'Model');
//        $mentions = $this->get(ucfirst($this->network) . 'Cache');
        $mentions = array();
        $mentions['facebook'] = $model->fetchFacebookMentions();
        $mentions['twitter'] = $model->fetchTwitterMentions();
        $mentions['stumbleupon'] = $model->fetchStumbleuponMentions();
        dump($mentions, 'View Mentions');
        $this->assignRef('cache', $mentions);
        parent::display($tpl);
    }

}
?>
