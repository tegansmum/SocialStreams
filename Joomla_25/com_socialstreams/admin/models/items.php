<?php

// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
//jimport('joomla.application.component.model');
//require_once JPATH_COMPONENT_ADMINISTRATOR . DS . 'lib' . DS . 'social_connections.php';

/**
 * Description of Socialcount
 *
 * @author stuart
 */
// import the Joomla modellist library
jimport('joomla.application.component.modellist');

class SocialstreamsModelItems extends JModelList {

    /**
     * Method to auto-populate the model state.
     *
     * Note. Calling getState in this method will result in recursion.
     *
     * @since	1.6
     */
    protected function populateState($ordering = null, $direction = null) {
        // Initialise variables.
        $app = JFactory::getApplication('administrator');

        // Load the filter state.
        $search = $this->getUserStateFromRequest($this->context . '.filter.search', 'filter_search');
        $this->setState('filter.search', $search);

        $network = $app->getUserStateFromRequest($this->context . '.filter.network', 'filter_network');
        $this->setState('filter.network', $network);

//        $language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
//        $this->setState('filter.language', $language);
        // Load the parameters.
        $params = JComponentHelper::getParams('com_socialstreams');
        $this->setState('params', $params);

        // List state information.
        parent::populateState('i.published', 'desc');
    }

    /**
     * Method to build an SQL query to load the list data.
     *
     * @return	string	An SQL query
     */
    protected function getListQuery() {
        // Create a new query object.		
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $user = JFactory::getUser();
        // Select some fields
        $query->select($this->getState('list.select', 'i.id AS id, i.network AS network, i.networkid AS networkid, ' .
                        'i.item AS item, i.published AS published, i.expires AS expires, i.created AS created '));
        // From the profiles table
        $query->from($db->quoteName('#__ss_items') . ' as i');

        $query->select('p.name AS name');
        $query->leftjoin('#__ss_profiles AS p ON p.id = i.profile_id');

        if ($this->getState('filter.network')) {
            $network = $this->getState('filter.network');
            $query->where('i.network = "' . $network . '"');
        }

        // Filter by search in title.
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('i.id = ' . (int) substr($search, 3));
            } else {
                $search = $db->Quote('%' . $db->escape($search, true) . '%');
                $query->where('(p.name LIKE ' . $search . ')');
            }
        }

        // Add the list ordering clause.
        $orderCol = $this->state->get('list.ordering', 'i.published');
        $orderDirn = $this->state->get('list.direction', 'desc');
        if ($orderCol == 'network' || $orderCol == 'name') {
            $orderCol = 'p.name ' . $orderDirn . ', i.network';
        }
        $query->order($db->escape($orderCol . ' ' . $orderDirn));

        return $query;
    }

}

?>
