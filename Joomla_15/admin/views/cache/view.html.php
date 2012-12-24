<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
jimport('joomla.application.component.view');

/**
 * Description of SocialstreamsViewItemcache
 *
 * @author stuart
 */
class SocialstreamsViewCache extends JView {

    function display($tpl = null) {
        global $option;
        JToolBarHelper::title( JText::_( 'Social Streams' ), 'generic.png' );
        JToolBarHelper::apply();
        JToolBarHelper::save();
        JToolBarHelper::preferences('com_socialstreams', '200', '400');
        $model = & $this->getModel();
        dump($model, 'Model');
        if(!$this->get(ucfirst($this->network) . 'Cache')){
            $adminmodel = & $this->getModel('ProfileCache');
            dump($adminmodel, 'Admin Model');
            $method = 'update' . ucfirst($this->network) . 'ProfileCache';
            if(method_exists($adminmodel, $method))
                $adminmodel->$method();
            $this->get(ucfirst($this->network) . 'Cache');
        }
        dump($this->network, 'Network');
        dump($model->_cache, 'Cache');
        $this->assignRef('cache', $model->_cache);
        parent::display($tpl);
    }

}
?>
