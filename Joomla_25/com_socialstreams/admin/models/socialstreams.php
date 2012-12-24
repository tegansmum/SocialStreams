<?php
defined('_JEXEC') or die('Restricted access');
/*
 * Default Model for Social Streams Admin
 */

// import the Joomla modellist library
jimport('joomla.application.component.modellist');

/**
 * Description of socialstreams
 *
 * @author stuart
 */
class SocialStreamsModelSocialStreams extends JModellist {
    
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

        $state = $this->getUserStateFromRequest($this->context . '.filter.state', 'filter_state', '', 'string');
        $this->setState('filter.state', $state);

        $authorId = $app->getUserStateFromRequest($this->context . '.filter.author_id', 'filter_author_id');
        $this->setState('filter.author_id', $authorId);

//        $language = $this->getUserStateFromRequest($this->context . '.filter.language', 'filter_language', '');
//        $this->setState('filter.language', $language);
        // Load the parameters.
        $params = JComponentHelper::getParams('com_socialstreams');
        $this->setState('params', $params);

        // List state information.
        parent::populateState('a.network', 'asc');
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
        $query->select($this->getState('list.select','a.id AS id, a.network AS network, a.expires AS expires, ' .
//                'a.clientid AS clientid, p.userid AS userid, p.name AS name, p.image AS image, ' .
                'a.checked_out AS checked_out, a.checked_out_time AS checked_out_time, ' .
                'a.state AS state, a.created AS created, a.created_by AS created_by'));
        
        // From the hello table
        $query->from($db->quoteName('#__ss_auth') . ' as a');
        
//        $query->innerJoin('#__ss_profiles AS p ON p.id = a.profile_id');
        
        // Join over the users for the checked out user.
        $query->select('uc.name AS editor');
        $query->join('LEFT', '#__users AS uc ON uc.id=a.checked_out');
        
        // Join over the users for the author.
        $query->select('ua.name AS author_name');
        $query->join('LEFT', '#__users AS ua ON ua.id = a.created_by');
        
        // Filter by published state
        $published = $this->getState('filter.published');
        if (is_numeric($published)) {
            $query->where('a.state = ' . (int) $published);
        } elseif ($published === '') {
            $query->where('(a.state = 0 OR a.state = 1)');
        }
        
        // Filter by author
        $authorId = $this->getState('filter.author_id');
        if (is_numeric($authorId)) {
            $type = $this->getState('filter.author_id.include', true) ? '= ' : '<>';
            $query->where('a.created_by ' . $type . (int) $authorId);
        }
        
        // Filter by search in title.
        $search = $this->getState('filter.search');
        if (!empty($search)) {
            if (stripos($search, 'id:') === 0) {
                $query->where('a.id = ' . (int) substr($search, 3));
            } elseif (stripos($search, 'author:') === 0) {
                $search = $db->Quote('%' . $db->escape(substr($search, 7), true) . '%');
                $query->where('(ua.name LIKE ' . $search . ' OR ua.username LIKE ' . $search . ')');
//            } else {
//                $search = $db->Quote('%' . $db->escape($search, true) . '%');
//                $query->where('(p.name LIKE ' . $search . ')');
            }
        }
        
        // Add the list ordering clause.
        $query->order($db->escape('a.network asc '));
        
        return $query;
    }
}

?>
