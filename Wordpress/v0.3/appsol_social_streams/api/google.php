<?php

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

require_once(dirname(__FILE__) . '/socialstreams.php');

class googleHelper {

    public static $client;
    public static $server = 'Google';
    public static $redirect_uri = '';
    public static $scope = 'https://www.googleapis.com/auth/plus.me';
    public static $last_error = '';
    public static $debug = 1;

    public static function setup($userid = null) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'googleHelper::setup'));
        self::$client = new SocialStreamsGoogle();
        self::$client->user = $userid;
        self::$client->debug = self::$debug;
        self::$client->server = self::$server;
        self::$client->scope = self::$scope;
        self::$client->redirect_uri = JURI::base() . 'index.php?option=com_socialstreams&task=socialstream.setauth&network=google';

        if (strlen(SocialStreamsHelper::getParameter('google_appid')) == 0 || strlen(SocialStreamsHelper::getParameter('google_appsecret')) == 0)
            return false;
        self::$client->client_id = SocialStreamsHelper::getParameter('google_appid');
        self::$client->client_secret = SocialStreamsHelper::getParameter('google_appsecret');

        if ($success = self::$client->Initialize()) {
            if ($success = self::$client->Process()) {
                if (strlen(self::$client->access_token)) {
                    $success = self::$client->CallAPI(
                            'https://www.googleapis.com/plus/v1/people/me', 'GET', array(), array('FailOnAccessError' => true), $user);
                }
            }
            $success = self::$client->Finalize($success, $user);
        }
        if (self::$client->exit) {
            $app = JFactory::getApplication();
            $app->close();
        }
        if ($success)
            return self::$client;
        return false;
    }

}

class SocialStreamsGoogle extends SocialStreamsApi {

    private $api_url = 'https://www.googleapis.com/plus/v1/';

    public function getNetwork() {
        return 'google';
    }
    
    public function getTokenLifetime() {
        return 0;
    }

    public function Initialize() {
        parent::Initialize();
        $this->dialog_url.= '&access_type=offline';
    }

    public function CallAPI($url, $method, $parameters, $options, &$response) {
        $this->GetAccessTokenUrl($url);
        $this->GetAccessToken($access_token);
        if (strtotime($access_token['expiry']) <= time()) {
            $params = array(
                'client_id' => SocialStreamsHelper::getParameter('google_appid'),
                'client_secret' => SocialStreamsHelper::getParameter('google_appsecret'),
                'refresh_token' => $access_token['refresh_token'],
                'grant_type' => 'refresh_token'
            );
            $success = $this->SendAPIRequest(
                    $url, 'POST', $params, array('FailOnAccessError' => true), $response);
            if (!$success)
                return false;
            $token = array(
                'value' => $response['access_token'],
                'authorized' => 1,
                'expiry' => date("Y-m-d H:i:s", time() + $response['expires_in']),
                'refresh_token' => $access_token['refresh_token']
            );
            $this->StoreAccessToken($token);
        }
        return parent::CallAPI($url, $method, $parameters, $options, $response);
    }

    public function getProfile($id = 'me') {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsGoogle::getProfile'));
        if (strlen($this->access_token))
            $success = $this->CallAPI($this->api_url . 'people/' . $id, 'GET', array(), array('FailOnAccessError' => true), $user);
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => print_r($user, true)));
        $success = $this->Finalize($success);

        if ($success) {
            $profile = new SocialStreamsGoogleProfile();
            $profile->setProfile($user);
            return $profile;
        }
        return false;
    }

    public function getConnectedProfiles($id = '') {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsGoogle::getConnectedProfiles'));
        if (!$id && $this->user)
            $id = $this->user;
        $my_connections = array();
        $profile = $this->getProfile($id);
        $connections = $this->getCircled($profile, $count);

        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => print_r($connections, true)));

        if (count($connections)) {
            $show_friends = array();
            $connection_total = count($connections);

            $stored_connections = SocialStreamsHelper::getParameter('stored_connections');
            $show_connections = $connection_total > $stored_connections ?
                    array_rand($connections, $stored_connections) : array_keys($connections);
            foreach ($show_connections as $connection_id) {
                $connection = $this->getProfile($connection_id);
                $my_connections[$connection_id] = $connection;
            }
        }
        return $my_connections;
    }

    public function getItems() {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsGoogle::getItems'));
        $my_feed = array();
        if (strlen($this->access_token))
            $success = self::CallAPI($this->api_url . $this->user . '/feed', 'GET', array(), array('FailOnAccessError' => true), $feed);
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => print_r($feed, true)));

        $success = $this->Finalize($success);
        if ($success) {
            foreach ($feed->data as $post) {
                if (isset($post->privacy) && $post->privacy->value == 'EVERYONE') {
                    $item = new SocialStreamsFacebookItem();
                    $item->setUpdate($post);
                    $my_feed[$item->networkid] = $item;
                }
            }
            return $my_feed;
        }

        return false;
    }

    public function getStats() {
        
    }

    function getCircled($profile, &$count) {

        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsGoogle::getCircled'));
        $result = '';
        $http = new http_class;
        $http->user_agent = "Mozilla/4.0 (compatible; MSIE 6.0; Windows NT 5.1)";
        $error = $http->GetRequestArguments($profile->url, $arguments);
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'Opening connection to: ' . HtmlSpecialChars($arguments["HostName"])));
        $error = $http->Open($arguments);

        if ($error == "") {
            $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'Sending request for page: ' . HtmlSpecialChars($arguments["RequestURI"])));
            $error = $http->SendRequest($arguments);
            if ($error == "") {
                $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'Request: ' . HtmlSpecialChars($http->request)));
                $headers = array();
                $error = $http->ReadReplyHeaders($headers);
                if ($error == "") {
                    switch ($http->response_status) {
                        case "301":
                        case "302":
                        case "303":
                        case "307":
                            JError::raiseWarning('500', 'Redirect to ' . $headers["location"] . ' Set the follow_redirect variable to handle redirect responses automatically.');
                            break;
                    }
                    $log_headers = '';
                    for (Reset($headers), $header = 0; $header < count($headers); Next($headers), $header++) {
                        $header_name = Key($headers);
                        if (GetType($headers[$header_name]) != "array") {
                            for ($header_value = 0; $header_value < count($headers[$header_name]); $header_value++)
                                $log_headers.= $header_name . ": " . $headers[$header_name][$header_value] . "\r\n";
                        } else {
                            $log_headers.= $header_name . ": " . $headers[$header_name] . "\r\n";
                        }
                    }
                    for (;;) {
                        $error = $http->ReadReplyBody($body, 1000);
                        if ($error != ""
                                || strlen($body) == 0)
                            break;
                        $result.= $body;
                    }
                }
            }
            $http->Close();
        }
        if (!strlen($error)) {
            $dom = new DOMDocument();
            $dom->preserveWhiteSpace = false;
            if ($dom->loadHTML($result)) {
                $gp_users = array();
                $content = $dom->getElementById('content');
                $xpath = new DOMXPath($dom);
                $circle_heading = $xpath->query('//h4[contains(text(), "Have ' . $profile->profile->name->givenName . ' in circles")]', $content);
                if ($circle_heading->length > 0) {
                    $circle_text = $circle_heading->item(0)->textContent;
                    // Get the number of circles the user is in
                    $circle_count = intval(substr($circle_text, strpos($circle_text, '(') + 1, (strpos($circle_text, ')') - strpos($circle_text, '('))));
                    // Get the users that the user in their circles
                    $gp_user_list = $xpath->query('../div/div//a', $circle_heading->item(0));
                    if ($len = $gp_user_list->length)
                        for ($i = 0; $i < $len; $i++)
                            $gp_users[] = $gp_user_list->item($i)->getAttribute('oid');
//                            $gp_users[] = $this->google->people->get($gp_user_id);
                }
                return $gp_users;
            }
//            preg_match('/<h4 class="Pv rla">([\s\w]*\((\d*)\))<\/h4>/is', $result, $matches);
        }
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => $error));
        return false;
    }

}

/**
 * Coomon interface to a Facebook Profile object 
 */
class SocialStreamsGoogleProfile extends SocialStreamsProfile {

    public function __construct($wraptag = 'li') {
        $this->network = 'google';
        $this->nicename = 'Google+';
        parent::__construct($wraptag);
    }

    public function setProfile($profile) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsGoogleProfile::setProfile'));
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => print_r($profile, true)));
        $this->networkid = $profile->id;
        $this->user = $profile->id;
        $this->name = $profile->displayName;
        $this->url = $profile->url;
        $this->image = $profile->image->url;
        if (isset($profile->profile))
            $this->profile = json_decode($profile->profile);
        elseif (is_object($profile))
            $this->profile = $profile;
    }

    public function store() {
        return get_object_vars($this);
    }

}

class SocialStreamsGoogleItem extends SocialStreamsItem {

    public function __construct($wraptag = 'li') {
        $this->network = 'google';
        $this->nicename = 'Google+';
        parent::__construct($wraptag);
    }

    function setUpdate($post, $fb_user = null) {
        jimport('joomla.error.log');
        $errorLog = & JLog::getInstance();
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'SocialStreamsGoogleItem::setUpdate'));
        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'Post -> ' . print_r($post, true)));

        $this->networkid = $post->id;
        // Is this a stored Tweet from the DB or from the API?
        if (isset($post->item)) {
            // Stored Tweet
            $this->item = json_decode($post->item);
            if (isset($post->profile)) {
                $this->profile = new SocialStreamsFacebookProfile();
                $this->profile->setProfile($post->profile);
            }
        } elseif (is_object($post)) {
            // Fresh Tweet from API
            $this->item = $post;
            $this->profile = new SocialStreamsFacebookProfile();
            $this->profile->setProfile(isset($post->profile) ? $post->profile : $post->from);
        }
        $this->published = JFactory::getDate(strtotime($post->created_time))->toMySQL();
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
        $fb_text = '';
        $update = array(
            'type' => '',
            'name' => '',
            'message' => '',
            'icon' => '',
            'description' => '',
            'link' => '',
            'picture' => '',
            'story' => '',
            'caption' => '',
            'source' => '',
            'likes' => ''
        );
        foreach ($update as $property => $value)
            if (!isset($this->item->$property))
                $this->item->$property = $value;
//        $update = array_merge($update, $post);
        if ($this->item->name)
            $fb_text.= '<span class="stream-item-title fb-post-name">' . $this->item->name . '</span>';
        switch ($this->item->type) {
            case 'link':
                if ($this->item->picture) {
                    $fb_text.= '<span class="stream-item-link stream-item-photo fb-post-link">';
                    $fb_text.= '<a class="stream-item-photo-image-link" href="' . $this->item->link . '" target="_blank" rel="nofollow"><img src="' . $this->item->picture . '" alt="' . $this->item->caption . '" /></a>';
                    if ($this->item->caption)
                        $fb_text.= '<span class="stream-item-photo-caption">' . $this->item->caption . '</span>';
                }else {
                    $fb_text.= '<span class="stream-item-link fb-post-link">';
                    $fb_text.= '<a href="' . $this->item->link . '" target="_blank" rel="nofollow">' . $this->item->name . '</a>';
                }
                $fb_text.= '</span>';
                break;
            case 'photo':
                $fb_text.= '<span class="stream-item-link stream-item-photo fb-post-photo">';
                $fb_text.= '<a href="' . $this->item->link . '" target="_blank" rel="nofollow"><img src="' . $this->item->picture . '" alt="' . $this->item->name . '" /></a>';
                $fb_text.= '</span>';
                if ($this->item->story)
                    $fb_text.= $this->item->story;
                break;
            case 'video':
                $fb_text.= '<span class="stream-item-link stream-item-video fb-post-video">';
                $fb_text.= '<a href="' . $this->item->link . '" target="_blank" rel="nofollow">';
                $fb_text.= '<img src="' . $this->item->picture . '" alt="' . $this->item->name . '" />';
                $fb_text.= '</a>';
                $fb_text.= '</span>';
                break;
        }
        $fb_text.= $this->item->description . ' ' . $this->item->message;
        return trim($fb_text);
    }

    function getUpdateActions() {
//        jimport('joomla.error.log');
//        $errorLog = & JLog::getInstance();
//        $errorLog->addEntry(array('status' => 'DEBUG', 'comment' => 'appsolFacebookItem::setUpdateActions'));
        $allowed_actions = array('like', 'comment');
        $actions = array();
        foreach ($this->item->actions as $action) {
            if (!in_array($action->name, $allowed_actions))
                continue;
            $tally = '';
            $action_name = strtolower($action->name) . 's';
            if (isset($this->item->$action_name)) {
                $count = isset($this->item->$action_name->count) ? $this->item->$action_name->count : 0;
                $name = $count > 1 ? $action->name . 's' : $action->name;
                $tally = '<span class="tally"><span class="count">' . $count . '</span> ' . $name . '</span>';
            }
            $actions[strtolower($action->name)] = $tally . '<a class="stream-item-action ' . strtolower($action->name) . '" href="' . $action->link . '" rel="nofollow" target="_blank">' . $action->name . '</a>';
        }
        return $actions;
    }

}

?>
