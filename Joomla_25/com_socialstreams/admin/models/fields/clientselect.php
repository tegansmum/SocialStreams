<?php

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// import the list field type
jimport('joomla.form.helper');
JFormHelper::loadFieldClass('list');

class JFormFieldClientSelect extends JFormFieldList {

    protected $type = 'clientselect';

    protected function getOptions() {
        $options = array();
        $jparams = JComponentHelper::getParams('com_socialstreams');
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select('a.id AS id, a.network AS network, p.name AS name');
        $query->from('#__ss_auth AS a');
        $query->innerJoin('#__ss_profiles AS p ON p.networkid = a.clientid');
        $query->where('a.state = 1 AND a.expires > NOW()');
        $db->setQuery($query);
        if ($clients = $db->loadAssocList())
            foreach ($clients as $client) {
                $network = $jparams->get($client['network'] . '_nicename');
                $options[] = JHtml::_('select.option', $client['id'], $client['name'] . ' on ' . $network);
            }
        $options = array_merge(parent::getOptions(), $options);

        return $options;
    }

}

?>
