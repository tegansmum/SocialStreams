<?php

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

jimport('joomla.application.component.modellist');
jimport('joomla.application.component.helper');

JTable::addIncludePath(JPATH_COMPONENT_ADMINISTRATOR . '/tables');

class SocialStreamsModelProfiles extends JModelList {

    protected $_context = 'com_socialstreams.profiles';

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @return	void
     * @since	1.6
     */
    protected function populateState($ordering = 'ordering', $direction = 'ASC') {
        $app = JFactory::getApplication();
        $jparams = JComponentHelper::getParams('com_socialstreams');

        parent::populateState();

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
                        'list.select', 'p.id AS id, p.network AS network, p.networkid AS networkid, p.user AS user, ' .
                        'p.name AS name, p.image AS image, p.url AS url, p.profile AS profile'));
        $query->from('#__ss_profiles AS p');
        
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
            $query->where('p.network = "' . strtolower($network) . '"');
        }
        
        // Filter by Account
        if ($this->getState('filter.accounts')) {
            $accounts = $this->getState('filter.accounts');
            $query->where('p.client_id IN (' . implode(',', $accounts) . ')');
        }

        // Filter by start and end dates.
        $nullDate = $db->Quote($db->getNullDate());
        $nowDate = $db->Quote(JFactory::getDate()->toSql());

        $orderby.= ' p.name ASC';
        $query->order($orderby);
//        jimport('joomla.error.log');
//        $errorLog = & JLog::getInstance();
//        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => nl2br($query->__toString())));
        return $query;
    }

}
?>
