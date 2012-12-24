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
        
        $model = & $this->getModel();
        if(!$this->get(ucfirst($this->network) . 'Cache')){
            $adminmodel = & $this->getModel('ProfileCache');
            $method = 'update' . ucfirst($this->network) . 'ProfileCache';
            if(method_exists($adminmodel, $method))
                $adminmodel->$method();
            $this->get(ucfirst($this->network) . 'Cache');
        }
        $this->assignRef('cache', $model->_cache);
        $this->addToolBar();
        parent::display($tpl);
    }
    
    protected function addToolBar(){
        JToolBarHelper::title( JText::_( COM_SOCIALSTREAMS_CACHE), 'generic.png' );
        JToolBarHelper::apply();
        JToolBarHelper::save();
        JToolBarHelper::preferences('com_socialstreams', '200', '400');
    }

}
?>
