<?php
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// import the list field type
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldNetworkSelect extends JFormFieldList {
    
    protected $type = 'networkselect';
    
    protected function getOptions(){
        
        $jparams = JComponentHelper::getParams('com_socialstreams');
        $networks = explode(',', $jparams->get('networks'));
        $options = array();
        foreach ($networks as $network) {
            if ($jparams->get($network) && $jparams->get($network . '_appkey') && $jparams->get($network . '_appsecret')) {
                $options[] = JHtml::_('select.option', $network, $jparams->get($network . '_nicename'));
            }
        }
        
        $options = array_merge(parent::getOptions(), $options);

        return $options;
    }
}
?>
