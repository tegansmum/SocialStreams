<?php

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once(dirname(__FILE__) . '/socialstreams.php');

/**
 * Name: linkedinHelper
 * Static class to provide access to the api object 
 */
class linkedinHelper {

    public static $client;
    public static $server = 'LinkedIn';
    public static $redirect_uri = '';
    public static $scope = 'r_fullprofile,r_network,rw_nus';
    public static $last_error = '';
    public static $debug = 1;

    public static function setup($userid = null) {
        self::$client = new SocialStreamsLinkedin();
        self::$client->user = $userid;
        self::$client->debug = self::$debug;
        self::$client->server = self::$server;
        self::$client->scope = self::$scope;
        self::$client->redirect_uri = JURI::base() . 'index.php?option=com_socialstreams&task=socialstream.setauth&network=linkedin';

        if (strlen(SocialStreamsHelper::getParameter('linkedin_appkey')) == 0 || strlen(SocialStreamsHelper::getParameter('linkedin_appsecret')) == 0)
            return false;
        self::$client->client_id = SocialStreamsHelper::getParameter('linkedin_appkey');
        self::$client->client_secret = SocialStreamsHelper::getParameter('linkedin_appsecret');

        $success = self::$client->Bootstrap();

        if ($success) 
            return self::$client;
        return false;
    }

}

class SocialStreamsLinkedin extends SocialStreamsApi {

    private $api_url = 'http://api.linkedin.com/v1/';

    public function getNetwork() {
        return 'linkedin';
    }
    
    public function getTokenLifetime() {
        return 60*60*24*60;
    }

    public function getProfile($id = '~') {
        $fields = 'id,first-name,last-name,picture-url,public-profile-url,num-connections,num-connections-capped,distance';
        if ($id == '~' || $id == $this->user)
            $fields.= ',num-recommenders,recommendations-received';
        if (strlen($this->access_token))
            $success = $this->CallAPI($this->api_url . 'people/' . $id . ':(' . $fields . ')?format=json', 'GET', array(), array('FailOnAccessError' => true), $user);
        SocialStreamsHelper::log($user);
        $success = $this->Finalize($success);

        if ($success) {
            $profile = new SocialStreamsLinkedinProfile();
            $profile->setProfile($user);
            return $profile;
        }
        return false;
    }

    public function getConnectedProfiles() {
        $my_connections = array();
        if (strlen($this->access_token))
            $success = $this->CallAPI($this->api_url . 'people/id=' . $this->user . '/connections?format=json', 'GET', array(), array('FailOnAccessError' => true), $connections);
        SocialStreamsHelper::log($connections);
        $success = $this->Finalize($success);
        if ($success) {
            $show_friends = array();
            $connections_total = $connections->_total;
            shuffle($connections->values);
            $stored_connections = SocialStreamsHelper::getParameter('stored_connections');
            $stored_connections = $connections_total > $stored_connections ?
                    array_slice($connections->values, 0, $stored_connections) : $connections->values;
            foreach ($stored_connections as $connection){
                if($connection->id == 'private')
                    continue;
                if ($connection = $this->getProfile($connection->id))
                    $my_connections[$connection->networkid] = $connection;
            }
        }
        return $my_connections;
    }

    public function getItems() {
        $my_updates = array();
        if (strlen($this->access_token)) {
            $types = array();
            if (SocialStreamsHelper::getParameter('linkedin_itemtype_shar'))
                $types[] = 'type=SHAR';
            if (SocialStreamsHelper::getParameter('linkedin_itemtype_stat'))
                $types[] = 'type=STAT';
            if (SocialStreamsHelper::getParameter('linkedin_itemtype_virl'))
                $types[] = 'type=VIRL';
            if (SocialStreamsHelper::getParameter('linkedin_itemtype_conn'))
                $types[] = 'type=CONN';
            if (count($types)) {
                $types = implode('&', $types);
                $success = self::CallAPI($this->api_url . 'people/id=' . $this->user . '/network/updates?' . $types . '&scope=self&count=50&format=json', 'GET', array(), array('FailOnAccessError' => true), $updates);
                SocialStreamsHelper::log($updates);
            }
        }
        $success = $this->Finalize($success);
        if ($success) {
            foreach ($updates->values as $update) {
                $item = new SocialStreamsLinkedinItem();
                $item->setUpdate($update);
                $my_updates[$item->networkid] = $item;
            }
            return $my_updates;
        }

        return false;
    }

    public function getStats() {
        if (strlen($this->access_token))
            $success = self::CallAPI($this->api_url . 'people/id=' . $this->user . '/network/network-stats?format=json', 'GET', array(), array('FailOnAccessError' => true), $stats);
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => print_r($stats, true)));

        $success = $this->Finalize($success);
        if ($success) {

            return $stats;
        }

        return false;
    }

}

/**
 * Coomon interface to a Facebook Profile object 
 */
class SocialStreamsLinkedinProfile extends SocialStreamsProfile {

    public function __construct($wraptag = 'li') {
        $this->network = 'linkedin';
        $this->nicename = 'LinkedIn';
        parent::__construct($wraptag);
    }

    public function setProfile($profile) {
        $this->networkid = $profile->id;
        $this->user = isset($profile->id) ? $profile->id : '';
        $this->name = $profile->firstName . ' ' . $profile->lastName;
        $this->url = isset($profile->publicProfileUrl) ? $profile->publicProfileUrl : 'https://www.linkedin.com/';
        $this->image = isset($profile->pictureUrl) ? $profile->pictureUrl : 'http://s4.licdn.com/scds/common/u/img/icon/icon_no_photo_50x50.png';
        if (isset($profile->profile))
            $this->profile = json_decode($profile->profile);
        elseif (is_object($profile))
            $this->profile = $profile;
    }

    public function store() {
        return get_object_vars($this);
    }

}

class SocialStreamsLinkedinItem extends SocialStreamsItem {

    public function __construct($wraptag = 'li') {
        $this->network = 'linkedin';
        $this->nicename = 'LinkedIn';
        parent::__construct($wraptag);
    }

    function setUpdate($update) {
        $this->networkid = $update->updateKey;
        // Is this a stored Update from the DB or from the API?
        if (isset($update->item)) {
            // Stored Update
            $this->item = json_decode($update->item);
            if (isset($update->profile)) {
                $this->profile = new SocialStreamsLinkedinProfile();
                $this->profile->setProfile($update->profile);
            }
        } elseif (is_object($update)) {
            // Fresh Update from API
            $this->item = $update;
            $this->profile = new SocialStreamsLinkedinProfile();
            $this->profile->setProfile(isset($update->profile) ? $update->profile : $update->updateContent->person);
        }
        $this->published = JFactory::getDate(intval(substr($update->timestamp, 0, 10)))->toMySQL();
    }

    function store() {
        $array = array(
            'network' => $this->network,
            'profile' => $this->profile,
            'networkid' => $this->networkid,
            'published' => $this->published,
            'item' => $this->item
        );
        return $array;
    }

    function styleUpdate() {
        $li_text = array();
        if (isset($this->item->updateContent->person->headline))
            $li_text[] = '<span class="stream-item-title li-headline">' . $this->item->updateContent->person->headline . '</span>';
        switch ($this->item->updateType) {
            case 'SHAR':
                if (isset($this->item->updateContent->person->currentShare->content)) {
                    $li_text[] = '<span class="stream-item-link li-share-link">' .
                            '<a href="' . $this->item->updateContent->person->currentShare->content->submittedUrl . '" target="_blank" rel="nofollow">' . $this->item->updateContent->person->currentShare->content->title . '</a></span>';
                    if (isset($this->item->updateContent->person->currentShare->content->submittedUrl)) {
                        if (isset($this->item->updateContent->person->currentShare->content->thumbnailUrl)) {
                            $li_text[] = '<span class="stream-item-link stream-item-photo li-update-photo">' .
                                    '<a href="' . $this->item->updateContent->person->currentShare->content->submittedUrl . '" target="_blank" rel="nofollow">' .
                                    '<img src="' . $this->item->updateContent->person->currentShare->content->thumbnailUrl . '" alt="' . $this->item->updateContent->person->currentShare->content->title . '" /></a></span>';
                        }
                    }
                    $share_text = '';
                    if (isset($this->item->updateContent->person->currentShare->content->description)) {
                        $share_text = $this->item->updateContent->person->currentShare->content->description;
                    }
                } elseif (isset($this->item->updateContent->person->currentShare->comment)) {
                    $share_text = $this->item->updateContent->person->currentShare->comment;
                }
                $urls = $this->findLinks($share_text);
                foreach ($urls as $url)
                    $share_text = str_ireplace($url, '<a class="stream-item-link li-link" href="' . $url . '" rel="nofollow">' . $url . '</a>', $share_text);
                $li_text[] = $share_text;

                break;
            case 'STAT':
                $status_text = $this->item->updateContent->person->currentStatus;
                $urls = $this->findLinks($status_text);
                foreach ($urls as $url)
                    $status_text = str_ireplace($url, '<a class="stream-item-link li-link" href="' . $url . '" rel="nofollow">' . $url . '</a>', $status_text);
                $li_text[] = $status_text;
                break;
            case 'VIRL':
                break;
            case 'CONN':
                break;
            default:
        }

        return implode("\n", $li_text);
    }

    function getUpdateActions() {
        $actions = array();
        if (isset($this->item->updateComments)) {
            $name = $this->item->updateComments->_total > 1 ? 'comments' : 'comment';
            $actions['comment'] = '<span class="tally"><span class="count">' . $this->item->updateComments->_total . '</span> ' . $name . '</span>';
        }
        if (isset($this->item->isLiked) && $this->item->isLiked) {
            $name = $this->item->numLikes > 1 ? 'likes' : 'like';
            $actions['like'] = '<span class="tally"><span class="count">' . $this->item->numLikes . '</span> ' . $name . '</span>';
        }
        return $actions;
    }

}

?>
