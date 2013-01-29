<?php

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// import the Joomla modellist library
jimport('joomla.application.component.modeladmin');

/**
 * Visit Manager List Model
 */
class SocialStreamsModelSocialStream extends JModelAdmin {

    /**
     * Method to save the form data.
     *
     * @param   array  $data  The form data.
     *
     * @return  boolean  True on success, False on error.
     *
     * @since   11.1
     */
    public function save($data) {
        $table = $this->getTable();
        $key = $table->getKeyName();
        $pk = (!empty($data[$key])) ? $data[$key] : (int) $this->getState($this->getName() . '.id');
        // Try to find an existing record
        if ($pk > 0) {
            $data[$key] = $pk;
        } elseif (isset($data['network']) && isset($data['clientid'])) {
            if ($table->load(array('network' => $data['network'], 'clientid' => $data['clientid'])))
                $data[$key] = $table->$key;
        }

        return parent::save($data);
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
    public function getTable($type = 'SsAuth', $prefix = 'SocialStreamsTable', $config = array()) {
        return JTable::getInstance($type, $prefix, $config);
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
        $form = $this->loadForm('com_socialstreams.auth', 'auth', array('control' => 'jform', 'load_data' => $loadData));
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
        $data = JFactory::getApplication()->getUserState('com_socialstreams.edit.auth.data', array());

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Method to get the script that have to be included on the form
     *
     * @return string	Script files
     */
    public function getScript() {
        return 'administrator/components/com_socialstreams/models/forms/socialstream_auth.js';
    }

    public function setAuth($network, $id='') {
        JLoader::import('components.com_socialstreams.helpers.socialstreams', JPATH_ADMINISTRATOR);
        // Get the Oauth API Object
        if (!$api = SocialStreamsHelper::getApi($network)) {
            SocialStreamsHelper::log(get_object_vars($api));
        }
//        $this->save($data);
        return empty($api->access_token) ? false : true;
    }

    public function getAuth($network = '') {
        JLoader::import('components.com_socialstreams.helpers.socialstreams', JPATH_ADMINISTRATOR);
        $jinput = JFactory::getApplication()->input;

        if ($network = $jinput->get('network', $network, 'STRING')) {
            if ($api = SocialStreamsHelper::getApi($network, $user)) {
                return $api;
            }
        }
        return false;
    }

}

?>
