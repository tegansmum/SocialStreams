<?php

// No direct access to this file
defined('_JEXEC') or die('Restricted access');

/**
 * Script file of VisitManager component
 */
class com_SocialStreamsInstallerScript {

    /**
     * $parent is the class calling this method.
     * install runs after the database scripts are executed.
     * If the extension is new, the install method is run.
     * If install returns false, Joomla will abort the install and undo everything already done.
     *
     * @return void
     */
    function install($parent) {
        // $parent is the class calling this method
        echo '<p>' . JText::_('COM_SOCIALSTREAMS_INSTALL_TEXT to ' . $this->release) . '</p>';
        // You can have the backend jump directly to the newly installed component configuration page
        $parent->getParent()->setRedirectURL('index.php?option=com_socialstreams');
    }

    /**
     * $parent is the class calling this method
     * uninstall runs before any other action is taken (file removal or database processing).
     *
     * @return void
     */
    function uninstall($parent) {
        // $parent is the class calling this method
        echo '<p>' . JText::_('COM_SOCIALSTREAMS_UNINSTALL_TEXT') . '</p>';
    }

    /**
     * $parent is the class calling this method.
     * update runs after the database scripts are executed.
     * If the extension exists, then the update method is run.
     * If this returns false, Joomla will abort the update and undo everything already done.
     *
     * @return void
     */
    function update($parent) {
        // $parent is the class calling this method
        echo '<p>' . JText::_('COM_SOCIALSTREAMS_UPDATE_TEXT ' . $parent->get('manifest')->version) . '</p>';
    }

    /**
     * $parent is the class calling this method.
     * $type is the type of change (install, update or discover_install, not uninstall).
     * preflight runs before anything else and while the extracted files are in the uploaded temp folder.
     * If preflight returns false, Joomla will abort the update and undo everything already done.
     *
     * @return void
     */
    function preflight($type, $parent) {
        // $parent is the class calling this method
        // $type is the type of change (install, update or discover_install)
        $jversion = new JVersion();

        // Installing component manifest file version
        $this->release = $parent->get("manifest")->version;

        // Manifest file minimum Joomla version
        $this->minimum_joomla_release = $parent->get("manifest")->attributes()->version;

        // abort if the current Joomla release is older
        if (version_compare($jversion->getShortVersion(), $this->minimum_joomla_release, 'lt')) {
            Jerror::raiseWarning(null, 'Cannot install COM_SOCIALSTREAMS in a Joomla release prior to ' . $this->minimum_joomla_release);
            return false;
        }

        // abort if the component being installed is not newer than the currently installed version
        if ($type == 'update') {
            $oldRelease = $this->getParam('version');
            $rel = $oldRelease . ' to ' . $this->release;
            if (version_compare($this->release, $oldRelease, 'le')) {
                Jerror::raiseWarning(null, 'Incorrect version sequence. Cannot upgrade ' . $rel);
                return false;
            }
        } else {
            $rel = $this->release;
        }
        echo '<p>' . JText::_('COM_SOCIALSTREAMS_PREFLIGHT_' . strtoupper($type) . '_TEXT ' . $rel) . '</p>';
    }

    /**
     * $parent is the class calling this method.
     * $type is the type of change (install, update or discover_install, not uninstall).
     * postflight is run after the extension is registered in the database.
     *
     * @return void
     */
    function postflight($type, $parent) {
        // $parent is the class calling this method
        // $type is the type of change (install, update or discover_install)
        $params = array();
        // Create or modify these parameters if update
        if ($type == 'update') {
            if ($networks = $this->getConfigField($parent, 'networks'))
                $params['networks'] = (string) $networks['default'];
        }

        $this->setParams($params);
        echo '<p>' . JText::_('COM_SOCIALSTREAMS_POSTFLIGHT_' . $type . '_TEXT') . '</p>';
    }

    /*
     * get a variable from the manifest file (actually, from the manifest cache).
     */

    function getParam($name) {
        $db = JFactory::getDbo();
        $db->setQuery('SELECT manifest_cache FROM #__extensions WHERE element = ' . $db->quote('com_socialstreams'));
        $manifest = json_decode($db->loadResult(), true);
        return $manifest[$name];
    }

    /*
     * sets parameter values in the component's row of the extension table
     */

    function setParams($param_array) {
        if (count($param_array) > 0) {
            // read the existing component value(s)
            $db = JFactory::getDbo();
            $db->setQuery('SELECT params FROM #__extensions WHERE element = ' . $db->quote('com_socialstreams'));
            $params = json_decode($db->loadResult(), true);
            // add the new variable(s) to the existing one(s)
            foreach ($param_array as $name => $value) {
                $params[(string) $name] = (string) $value;
            }
            // store the combined new and existing values back as a JSON string
            $paramsString = json_encode($params);
            $db->setQuery('UPDATE #__extensions SET params = ' .
                    $db->quote($paramsString) .
                    ' WHERE element = ' . $db->quote('com_socialstreams'));
            $db->query();
        }
    }

    private function getConfigField($parent, $name) {
        if (file_exists($parent->getParent()->getPath('extension_administrator') . '/config.xml')) {
            $config = simplexml_load_file($parent->getParent()->getPath('extension_administrator') . '/config.xml');
            $element = $config->xpath('//field[@name="' . $name . '"]');
            if (count($element))
                return count($element == 1) ? $element[0] : $element;
        }
        return false;
    }

}

?>
