<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// import the Joomla modellist library
jimport('joomla.application.component.modeladmin');

/**
 * Visit Manager List Model
 */
class SocialStreamsModelProfile extends JModelAdmin {

    /**
     * Method to save the form data.
     *
     * @param   array  $data  The form data.
     *
     * @return  boolean  True on success, False on error.
     *
     * @since   11.1
     */
    public function save($clientid, $data) {
        if(is_object($data)){
            $store = array(
                'id' => isset($data->id)? $data->id : '',
                'network' => $data->network,
                'networkid' => $data->networkid,
                'user' => $data->user,
                'name' => $data->name,
                'image' => $data->image,
                'url' => $data->url,
                'profile' => $data->profile,
                'expires' => $data->expires,
                'created' => $data->created
            );
            $data = $store;
        }
        $data['client_id'] = $clientid;
        $table = $this->getTable();
        $key = $table->getKeyName();
        $pk = (!empty($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');
        // Try to find an existing record
        if ($pk > 0) {
            $data[$key] = $pk;
        } elseif (isset($data['network']) && isset($data['networkid'])) {
            if ($table->load(array('network' => $data['network'], 'networkid' => $data['networkid'])))
                $data[$key] = $table->$key;
        }

        if (!parent::save($data))
            return false;
        return $this->getState($this->getName() . '.id');
    }

    /**
     * Returns a reference to the a Table object, always creating it.
     *
     * @param	type	The table type to instantiate
     * @param	string	A prefix for the table class name. Optional.
     * @param	array	Configuration array for model. Optional.
     * @return	JTable	A database object
     * @since	1.6
     */
    public function getTable($type = 'ssprofiles', $prefix = 'SocialStreamsTable', $config = array()) {
        return JTable::getInstance($type, $prefix, $config);
    }

    /**
     * Prepare and sanitise the table data prior to saving.
     *
     * @param   JTable  $table  A reference to a JTable object.
     *
     * @return  void
     *
     * @since   12.2
     */
    protected function prepareTable(&$table) {
        if (empty($table->id))
            $table->created = JFactory::getDate(time())->toMySQL();
        $jparams = JComponentHelper::getParams('com_socialstreams');
        $table->expires = JFactory::getDate(time() + $jparams->get('profile_period'))->toMySQL();
    }

    /**
     * Method to get the record form.
     *
     * @param	array	$data		Data for the form.
     * @param	boolean	$loadData	True if the form is to load its own data (default case), false if not.
     * @return	mixed	A JForm object on success, false on failure
     * @since	1.6
     */
    public function getForm($data = array(), $loadData = true) {
// Get the form.
        $form = $this->loadForm('com_socialstreams.profile', 'profile', array('control' => 'jform', 'load_data' => $loadData));
        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return	mixed	The data for the form.
     * @since	1.6
     */
    protected function loadFormData() {
        // Check the session for previously entered form data.
        $data = JFactory::getApplication()->getUserState('com_socialstreams.edit.profile.data', array());

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    function refresh($force = false) {

        JLoader::import('components.com_socialstreams.helpers.socialstreams', JPATH_ADMINISTRATOR);
        $new = 0;
        // Load the authenticated networks into an array and randomise it
        $networks = SocialStreamsHelper::getAuthenticatedNetworks();

        shuffle($networks);
        // Work through the networks looking for out of date profiles
        foreach ($networks as $network) {

            // If there are no profiles for this network then it will need fetching
            $profile_ids = $this->getProfileIds($network['id']);

            // If profiles existed for this network, then look for out of date ones
            $expired_profile_ids = array();
            if (count($profile_ids) && !$force) {
                $expired_profile_ids = $this->getProfileIds($network['id'], true);
            }
            // If we have no profiles, expired profiles or we are forcing then refresh the cache
            if (!count($profile_ids) || count($expired_profile_ids) || $force) {

                if ($api = SocialStreamsHelper::getApi($network['network'], $network['clientid'])) {
                    $connection_count = 0;
                    if ($connections = $api->getConnectedProfiles($connection_count)) {
                        $new = $new ? $new + count($connections) : count($connections);
                        foreach ($connections as $profile) {
//                            $save_item = $profile->store();
//                            $save_item->client_id = $network['id'];
                            if (!$this->getInstance('profile', 'SocialStreamsModel')->save($network['id'], $profile->store()))
                                JError::raiseWarning('500', 'Failed to Save Profile ' . $profile->name . ' for Client ID ' . $network['clientid'] . ' on Network ' . $network['network']);
                        }
                    }
                    
                    if ($profile = $api->getProfile($network['clientid'])) {
//                        $save_item = $profile->store($connection_count);
//                        $save_item->client_id = $network['id'];
                        if (!$this->getInstance('profile', 'SocialStreamsModel')->save($network['id'], $profile->store($connection_count)))
                            JError::raiseWarning('500', 'Failed to Save Profile ' . $profile->name . ' for Client ID ' . $network['clientid'] . ' on Network ' . $network['network']);
                    }
                    
                    $this->clearExpired($network['network'], $network['id']);
                }
                if (!$force)
                    break;
            }
        }
        return $new;
    }

    function getProfileIds($client_id, $expired = false) {
        $db = $this->getDbo();
        $query = $db->getQuery(true);
        $query->select('id');
        $query->from('#__ss_profiles');
        $query->where('client_id = "' . $client_id . '"');
        if ($expired)
            $query->where('expires < NOW()');
        $db->setQuery($query);
        return $db->loadResultArray();
    }

    function clearExpired($network, $user = false) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsModelProfile::clearExpired'));
        $db = & JFactory::getDBO();
        $jparams = JComponentHelper::getParams('com_socialstreams');
        $query = $db->getQuery(true);
        $query->select('id');
        $query->from('#__ss_profiles');
        $query->where($db->nameQuote('network') . ' = ' . $db->Quote($network));
        if ($user)
            $query->where($db->nameQuote('client_id') . ' = ' . $user);
        $query->where($db->nameQuote('expires') . ' < ' . $db->quote(JFactory::getDate(time() - (floor($jparams->get('profile_period') / 2)))->toMySQL()));
        $db->setQuery($query);
        if ($result = $db->loadResultArray())
            if ($this->delete($result))
                return count($result);
        return false;
    }

}

?>
