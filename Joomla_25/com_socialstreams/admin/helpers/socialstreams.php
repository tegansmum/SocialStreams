<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once(dirname(__FILE__) . '/../lib/oauth/http.php');
require_once(dirname(__FILE__) . '/../lib/oauth/oauth_client.php');

class SocialStreamsHelper {

    public static $extension = 'com_socialstreams';
    public static $jparams = null;
    public static $clients = array();

    public static function addSubmenu($vname) {

        JSubMenuHelper::addEntry(
                JText::_('COM_SOCIALSTREAMS_SUBMENU_ACCOUNTS'), 'index.php?option=com_socialstreams', $vname == 'auth'
        );

        JSubMenuHelper::addEntry(
                JText::_('COM_SOCIALSTREAMS_SUBMENU_PROFILES'), 'index.php?option=com_socialstreams&view=profiles', $vname == 'profiles'
        );

        JSubMenuHelper::addEntry(
                JText::_('COM_SOCIALSTREAMS_SUBMENU_ITEMS'), 'index.php?option=com_socialstreams&view=items', $vname == 'posts'
        );
    }

    /**
     * Gets a list of the actions that can be performed.
     *
     * @param	int		The category ID.
     * @param	int		The article ID.
     *
     * @return	JObject
     * @since	1.6
     */
    public static function getActions($type, $categoryId = 0, $itemId = 0) {
        // Reverted a change for version 2.5.6
        $user = JFactory::getUser();
        $result = new JObject;

        $assetName = 'com_visitmanager';

        $actions = array(
            'core.admin', 'core.manage', 'core.create', 'core.edit', 'core.edit.own', 'core.edit.state', 'core.delete'
        );

        foreach ($actions as $action) {
            $result->set($action, $user->authorise($action, $assetName));
        }

        return $result;
    }

    public static function getParameter($name) {
        if (!self::$jparams)
            self::$jparams = JComponentHelper::getParams('com_socialstreams');
        return self::$jparams->get($name);
    }

    public static function getNetworkHelper($network) {
        JLoader::import('components.com_socialstreams.helpers.' . $network, JPATH_ADMINISTRATOR);
        $helper = $network . 'Helper';
        if (!class_exists($helper) || !property_exists($helper, 'client'))
            return false;
        return $helper;
    }

    /**
     * Name: SocialStreamsHelper::getApi
     * returns an object of a class that extends SocialStreamsApi
     * The actual class of the object will depend upon the network string passed
     * @param string $network The social network to attempt to connect to
     * @param string $userid The user whose associated authentication tokens is to be used
     * @return boolean 
     */
    public static function getApi($network, $userid = null) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsHelper::getApi'));
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'Network: ' . $network));

        if(isset(self::$clients[$network]))
            return self::$clients[$network];
        if ($helper = self::getNetworkHelper($network))
            if (self::$clients[$network] = call_user_func(array($helper, 'setup'), $userid))
                return self::$clients[$network];

        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'Failed to call ' . $helper . '::setup'));
        return false;
    }

    /**
     * Name: getNetworks
     * 
     * @return array List of active networks to work with Joomla select.options
     */
    public static function getNetworks() {

        $jparams = JComponentHelper::getParams('com_socialstreams');
        $all_networks = explode(',', $jparams->get('networks'));
        $active_networks = array();
        foreach ($all_networks as $network) {
            if ($jparams->get($network) && $jparams->get($network . '_appkey') && $jparams->get($network . '_appsecret')) {
                $active_networks[] = array('value' => $network, 'text' => $jparams->get($network . '_nicename'));
            }
        }
        return $active_networks;
    }

    public static function getAuthenticatedNetworks() {
        $db = JFactory::getDBO();
        $query = $db->getQuery(true);
        $query->select('id, network, clientid', 'access_token');
        $query->from('#__ss_auth');
        $query->where('state = 1 AND expires > NOW()');
        $db->setQuery($query);
        if ($networks = $db->loadAssocList())
            return $networks;
        return array();
    }

    public static function getProfile($network, $wraptag = 'li', $networkid = null) {
        JLoader::import('components.com_socialstreams.helpers.' . $network, JPATH_ADMINISTRATOR);
        $class = 'SocialStreams' . ucfirst($network) . 'Profile';
        $profile = new $class($wraptag);
        return $profile;
    }

    public static function getItem($network, $wraptag = 'li', $networkid = null) {
        JLoader::import('components.com_socialstreams.helpers.' . $network, JPATH_ADMINISTRATOR);
        $class = 'SocialStreams' . ucfirst($network) . 'Item';
        $item = new $class($wraptag);
        return $item;
    }

}

/**
 * Name: SocialStreamsApi
 * Abstract class to provide base functionality to social network specific Api classes
 * Extends the base OAuth client class to provide easy authentication and access to social network API 
 * @abstract
 */
abstract class SocialStreamsApi extends oauth_client_class {

    /**
     * Name: GetAccessToken
     * returns true on success if a valid OAuth Access Token for the social network API is stored
     * sets the passed variable to hold the OAuth Access Token details array
     * @param type $access_token
     * @return boolean 
     */
    function GetAccessToken(&$access_token) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsApi::GetAccessToken'));
        $access_token = array();
        // First check the session
        if (!@session_start())
            return($this->SetPHPError('it was not possible to start the PHP session', $php_error_message));
        if (IsSet($_SESSION['OAUTH_ACCESS_TOKEN'][$this->access_token_url]))
            $access_token = $_SESSION['OAUTH_ACCESS_TOKEN'][$this->access_token_url];
        // Nothing found in the Session so look for a valid token in the DB
        if (empty($access_token)) {
            $db = JFactory::getDBO();
            $query = $db->getQuery(true);
            $query->select('access_token, access_token_secret, expires, state');
            $query->from('#__ss_auth');
            $query->where('network = "' . $this->getNetwork() . '" AND access_token <> "" AND expires > NOW()');
            if (isset($this->user) && !empty($this->user))
                $query->where('clientid = "' . $this->user . '"');
            $db->setQuery((string) $query);
            if ($auth = $db->loadObject()){
                $access_token = array(
                    'value' => $auth->access_token,
                    'expiry' => $auth->expires,
                    'authorized' => $auth->state,
                );
                if(intval($this->oauth_version) == 2)
                    $access_token['refresh_token'] = $auth->access_token_secret;
                else
                    $access_token['secret'] = $auth->access_token_secret;
            }
        }
        return true;
    }

    /**
     * Name: StoreAccessToken
     * creates an array of standardised format to hold the OAuth Access Token
     * sets the expiry timestamp for the Token then passes the array to the model and calls save method
     * @param array $access_token
     * @return boolean 
     */
    public function StoreAccessToken($access_token) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsApi::StoreAccessToken'));
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => print_r($access_token, true)));

        JLoader::import('socialstream', JPATH_ADMINISTRATOR . '/com_socialstreams/models');
        $model = JModel::getInstance('socialstream', 'SocialStreamsModel');
        $data = array(
            'network' => $this->getNetwork(),
            'access_token' => $access_token['value'],
            'access_token_secret' => isset($access_token['secret']) ? $access_token['secret'] : '',
            'expires' => empty($access_token['expiry']) ? JFactory::getDate(time() + (60 * 60 * 24 * 60))->toMySQL() : JFactory::getDate($access_token['expiry'])->toMySQL(),
            'state' => $access_token['authorized']
        );
        
        // Google among others uses a refresh token to request new access tokens
        if(empty($data['access_token_secret']) && isset($access_token['refresh_token']))
            $data['access_token_secret'] = $access_token['refresh_token'];
        
        parent::StoreAccessToken($access_token);
        
        // If there is a valid Client ID we should store that
        if($clientid = $this->getClientid())
            $data['clientid'] = $clientid;
        else
            if ($profile = $this->getProfile())
                $data['clientid'] = $profile->id;
        
        return $model->save($data);
    }

    public function Finalize($success) {
        if (!$success) {
            jimport('joomla.error.log');
            $errorLog = & JLog::getInstance();
            $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsApi::Finalize'));
            $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => $this->error));
            JError::raiseWarning('500', 'Failed to Finalize API Call: ' . $this->error);
        }

        return parent::Finalize($success);
    }
    
    /**
     * Name: getClientid
     * @return string unique identifier of the authenticated user on the social media network
     */
    public function getClientid(){
        return isset($this->user)? $this->user : '';
    }

    /**
     * Name: getNetwork
     * @abstract
     * @return string name of the social media network
     */
    abstract public function getNetwork();

    /**
     * Name: getProfile
     * @abstract
     * @return object of class that extends SocialStreamsProfile
     */
    abstract public function getProfile();

    /**
     * Name: getConnectedProfiles
     * @abstract
     * @return array of objects extend SocialStreamsProfile
     */
    abstract public function getConnectedProfiles();

    /**
     * Name: getItems
     * @abstract
     * @return array of objects of class that extends SocialStreamsItem
     */
    abstract public function getItems();

    /**
     * Name: getStats
     * @abstract
     * @return integer number of connections the user has in the social network 
     */
    abstract public function getStats();
}

/**
 * Name: SocialStreamsProfile
 * holds the profile data from a social network and presents a common interface
 * @abstract
 */
abstract class SocialStreamsProfile {

    public $id = '';
    public $network = '';
    public $nicename = '';
    public $networkid = '';
    public $user = '';
    public $name = '';
    public $url = '';
    public $image = '';
    public $profile = '';
    public $expires = '';
    protected $wraptag = 'li';

    function __construct($wraptag = 'li') {
        $this->wraptag = $wraptag;
    }

    abstract public function setProfile($profile);

    /**
     * Name: store
     * cretaes an associative array ready to store in the database
     * @return array 
     */
    abstract function store();

    public function setExpires() {
        $jparams = JComponentHelper::getParams('com_socialstreams');
        $this->expires = time() + $jparams->get('profile_period');
    }

    public function display() {
        $html = array();
        $html[] = '<' . $this->wraptag . ' class="connection ' . $this->network . '">';
        $html[] = '<a href="' . $this->url . '" title="' . $this->name . '" rel="nofollow" target="_blank">';
        $html[] = '<img src="' . $this->image . '" alt="' . $this->name . '" width="48px" height="48px" />';
        $html[] = '<span class="social-network-icon"></span></a>';
        $html[] = '</' . $this->wraptag . '>';
        return implode("\n", $html);
    }

}

/**
 * Name: SocialStreamsItem
 * holds update information from a social network and presents a common interface
 * @abstract
 */
abstract class SocialStreamsItem {

    public $id = 0;
    public $network = '';
    public $nicename = '';
    public $networkid = '';
    public $item = '';
    public $published = '';
    public $display_date = '';
    public $profile = null;
    public $actions = array();
    protected $wraptag = 'li';
    protected $url_pattern = '/\b((https?|ftp|file):\/\/|(www|ftp)\.)[-A-Z0-9+&@#\/%?=~_|$!:,.;]*/i';

    function __construct($wraptag = 'li') {
        $this->wraptag = $wraptag;
    }

    /**
     * Name: setUpdate
     * recives the update data from the social network and creates a unified interface for external use
     * @param various the returned status update from the social network api 
     */
    abstract function setUpdate($update);

    /**
     * Name: styleUpdate
     * creates html of the update to show in the stream
     * @return string an html snippet 
     */
    abstract function styleUpdate();

    /**
     * Name: getUpdateActions
     * creates html with actionable elements to allow interaction of the visitor with the update
     * e.g. Like, retweet, etc
     * @return array an array of html snippets
     */
    abstract function getUpdateActions();

    /**
     * Name: store
     * cretaes an associative array ready to store in the database
     * @return array 
     */
    abstract function store();

    function display() {
        $html = '<' . $this->wraptag . ' class="stream-item ' . $this->network . '">';
        $html.= '<span class="profile-image"><a href="' . $this->profile->url . '" rel="nofollow" target="_blank" title="' . $this->profile->name . '\'s ' . $this->nicename . ' Profile' . '"><img width="48px" height="48px" src="' . $this->profile->image . '" /></a></span>';
        $html.= '<span class="message">' . $this->styleUpdate() . '</span>';
        $html.= '<span class="meta">Posted <span class="post-date">' . $this->display_date . '</span> by <a class="profile-name" href="' . $this->profile->url . '" rel="nofollow">' . $this->profile->name . '</a></span>';
        $html.= '<span class="actions">';
        foreach ($this->getUpdateActions() as $action)
            $html.= '<span class="action">' . $action . '</span>';
        $html.= '</span>';
        $html.= '</' . $this->wraptag . '>';
        return $html;
    }

    public function toArray() {
        return get_object_vars($this);
    }

    protected function getAge($date) {
        $datetime = is_numeric($date) ? $date : strtotime($date);
        $this->id = $datetime;
        $now = time();
        $age = $now - $datetime;
        $age_str = 'ago';
        if (floor($age / 60 / 60 / 24 / 7) > 0) {
            $weeks = floor($age / 60 / 60 / 24 / 7);
            $week_str = $weeks == 1 ? 'week' : 'weeks';
            $age_str = $weeks . ' ' . $week_str . ' ' . $age_str;
        } elseif (floor($age / 60 / 60 / 24) > 0) {
            $days = floor($age / 60 / 60 / 24);
            $day_str = $days == 1 ? 'day' : 'days';
            $age_str = $days . ' ' . $day_str . ' ' . $age_str;
        } elseif (floor($age / 60 / 60) > 0) {
            $hours = floor($age / 60 / 60);
            $hour_str = $hours == 1 ? 'hour' : 'hours';
            $age_str = $hours . ' ' . $hour_str . ' ' . $age_str;
        } else {
            $minutes = floor($age / 60);
            $minute_str = $minutes == 1 ? 'minute' : 'minutes';
            $age_str = $minutes . ' ' . $minute_str . ' ' . $age_str;
        }
        return $age_str;
    }

    protected function shortenLink($link) {
        $shortener = 'http://is.gd/create.php?';
        $short_format = 'format=simple';
        $long_url = 'url=' . $link;
        $fh = fopen($shortener . $short_format . '&' . $long_url, 'r');
        if ($fh) {
            $short_url = '';
            while (!feof($fh)) {
                $chunk = fgets($fh);
                $short_url.= $chunk;
            }
            fclose($fh);
            return $short_url;
        } else {
            return $link;
        }
    }

    protected function findLinks($text) {
        $urls = array();
        $match_count = preg_match_all($this->url_pattern, $text, $matches, PREG_SET_ORDER);
        if (!$match_count)
            return $urls;
        foreach ($matches as $match)
            $urls[] = $match[0];
        return $urls;
    }

}

?>
