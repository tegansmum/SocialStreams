<?php

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

jimport('joomla.application.component.modellist');
jimport('joomla.application.component.helper');

JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/tables');

class SocialStreamsModelItems extends JModelList {

    protected $_context = 'com_socialstreams.items';

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @return	void
     * @since	1.6
     */
    protected function populateState($ordering = 'ordering', $direction = 'ASC') {
        jimport('joomla.error.log');
            $errorLog = & JLog::getInstance();
        $app = JFactory::getApplication();
        $jparams = JComponentHelper::getParams('com_socialstreams');

        // List state information
        $value = JRequest::getUInt('limit', $app->getCfg('list_limit', 0));
        $this->setState('list.limit', $value);

        $value = JRequest::getUInt('limitstart', 0);
        $this->setState('list.start', $value);

        $orderCol = JRequest::getCmd('filter_order', 'a.ordering');
        if (!in_array($orderCol, $this->filter_fields)) {
            $orderCol = 'a.ordering';
        }
        $this->setState('list.ordering', $orderCol);

        $listOrder = JRequest::getCmd('filter_order_Dir', 'ASC');
        if (!in_array(strtoupper($listOrder), array('ASC', 'DESC', ''))) {
            $listOrder = 'ASC';
        }
        $this->setState('list.direction', $listOrder);

        $params = $app->getParams();
        $this->setState('params', $params);
        $user = JFactory::getUser();

        if ((!$user->authorise('core.edit.state', 'com_socialstreams')) && (!$user->authorise('core.edit', 'com_socialstreams'))) {
            // filter on published for those who do not have edit or edit.state rights.
            $this->setState('filter.published', 1);
        }

        $this->setState('filter.language', $app->getLanguageFilter());

        // process show_noauth parameter
        if (!$params->get('show_noauth')) {
            $this->setState('filter.access', true);
        } else {
            $this->setState('filter.access', false);
        }

        $this->setState('layout', JRequest::getCmd('layout'));

        $jinput = JFactory::getApplication()->input;

        if ($name = $jinput->get('name', null, 'STRING')) {
            // Name search so ignore the other parameters and add it to the Model state
            $this->setState('filter.name', $name);
            $app->setUserState($this->context . '.name', $name);
        }

        if ($network = $jinput->get('network', null, 'STRING')) {
            // Network search so ignore the other parameters and add it to the Model state
            $this->setState('filter.network', $network);
            $app->setUserState($this->context . '.network', $network);
        }

        if ($accounts = $jinput->get('accounts', null, 'ARRAY')) {
            // Network search so ignore the other parameters and add it to the Model state
            $this->setState('filter.accounts', $accounts);
            $app->setUserState($this->context . '.accounts', $accounts);
        }
    }

    /**
     * Get the master query for retrieving a list of articles subject to the model state.
     *
     * @return	JDatabaseQuery
     * @since	1.6
     */
    function getListQuery() {

        // Create a new query object.
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $nullDate = $db->quote($db->getNullDate());
        $orderby = '';
        // Select the required fields from the table.
        $query->select(
                $this->getState(
                        'list.select', 'i.id AS id, i.network AS network, i.networkid AS networkid, i.profile_id AS profile_id, i.item AS item'));
        $query->from('#__ss_items AS i');

        // Get the details of the Profile
        $query->innerJoin('#__ss_profiles AS p ON p.id = i.profile_id');
        $query->select('p.name AS name, p.profile AS profile');

        // Filter by Name
        if ($this->getState('filter.name')) {
            $parts = array_map('strtolower', explode(' ', $this->getState('filter.name')));
            $likesql = $matchsql = '';
            foreach ($parts as $part) {
                $likesql.= str_replace('x', $part, 'p.name LIKE "%x%" OR ');
                $matchsql.= "SUBSTRING(p.name FROM LOCATE('$part', LOWER(p.name)) FOR LENGTH('$part')),";
            }
            $matchsql = 'LENGTH(CONCAT(' . substr($matchsql, 0, -1) . ')) as matched';
            $query->select($matchsql);
            $orderby.= 'matched DESC,';
            $likesql = substr($likesql, 0, -4);
            $query->where($likesql);
        }

        // Filter by Network
        if ($this->getState('filter.network')) {
            $network = $this->getState('filter.network');
            $query->where('i.network = "' . strtolower($network) . '"');
        }

        // Filter by Account
        if ($this->getState('filter.accounts')) {
            $accounts = $this->getState('filter.accounts');
            $query->where('i.client_id IN (' . implode(',', $accounts) . ')');
        }

        // Filter by start and end dates.
        $nullDate = $db->Quote($db->getNullDate());
        $nowDate = $db->Quote(JFactory::getDate()->toSql());

        $orderby.= ' i.published DESC';
        $query->order($orderby);
//        jimport('joomla.error.log');
//        $errorLog = & JLog::getInstance();
//        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => nl2br($query->__toString())));
        return $query;
    }

}

?>
