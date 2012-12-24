<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
require_once APPSOL_SOCIAL_STREAMS_PATH . 'api/api_config.php';

if (!class_exists('AppsolSocialStreamsFacebook'))
    require_once APPSOL_SOCIAL_STREAMS_PATH . 'api/facebook/facebook.php';
if (!class_exists('TwitterOAuth'))
    require_once(APPSOL_SOCIAL_STREAMS_PATH . 'api/twitter/twitteroauth.php');
if (!class_exists('LinkedIn'))
    require_once(APPSOL_SOCIAL_STREAMS_PATH . 'api/linkedin/linkedin_3.3.0.class.php');
if (!class_exists('apiClient'))
    require_once APPSOL_SOCIAL_STREAMS_PATH . 'api/google/apiClient.php';
if (!class_exists('apiPlusService'))
    require_once APPSOL_SOCIAL_STREAMS_PATH . 'api/google/contrib/apiPlusService.php';
session_start();

class appsolFacebookApi extends AppsolSocialStreamsFacebook {

    public $user_id = null;
    public $profile = null;

    function __construct($userid = 0) {
        $this->user_id = isset($_GET['fb_user']) ? $_GET['fb_user'] : $userid;
        $params = array(
            'appId' => APPSOL_FB_APPID,
            'secret' => APPSOL_FB_SECRET
        );
        parent::__construct($params);
        if (isset($_GET['revoke']) && isset($_GET['fb_user']) && $_GET['revoke'] == 'social_streams_facebook') {
            $this->revoke_authentication($_GET['fb_user']);
            return null;
        }
        $this->connect();
    }

    function connect() {
        _log('appsolFacebookApi::connect');
        // Get the user ID.
        $this->user_id = $this->getUser();
        if (!$this->user) {
            $this->clearAllPersistentData();
        }
        $this->setPersistentData('access_token', $this->getAccessToken());
        $this->setPersistentData('user_id', $this->user_id);
        update_option('appsol_social_streams_fb_last_msg', "");
        try {
            $this->profile = $this->api('/' . $this->user_id);
            $fb_users = get_option('appsol_social_streams_fb_users', array());
            $fb_users[$this->user_id] = $this->profile['name'];
            update_option('appsol_social_streams_fb_users', $fb_users);
        } catch (FacebookApiException $e) {
            $msg = '<strong>Error:</strong>' . $e->__toString();
            update_option('appsol_social_streams_fb_last_msg', $msg);
            if ($e->getType() == 'OAuthException')
                $this->retrieve_authentication();
        }

        return true;
    }

    function revoke_authentication($fb_user) {
        _log('Revoke Facebook Authentication');
        $this->purge_options($this->user_id);
        $this->purge_caches($this->user_id);
        // Remove User
        $fb_users = get_option('appsol_social_streams_fb_users', array());
        unset($fb_users[$fb_user]);
        update_option('appsol_social_streams_fb_users', $fb_users);
        // Clear any Cookies
        if (isset($_COOKIE[$this->getSignedRequestCookieName()]))
            unset($_COOKIE[$this->getSignedRequestCookieName()]);
        // Clear any stored requests
        $this->signedRequest = null;
        
        update_option('appsol_social_streams_fb_last_msg', "Facebook User removed. Don't forget to De-Authorize the Social Streams App in the users Facebook account.");
        header('Location: ' . get_admin_url() . 'plugins.php?page=appsol_social_streams&tab=facebook');
    }
    
    function purge_options($user_id){
        // State, Code, Access Token and User ID
        $this->clearAllPersistentData();
        // Followers
        $followers = get_option('appsol_social_streams_fb_followers', array());
        unset($followers[$user_id]);
        update_option('appsol_social_streams_fb_followers', $followers);
    }
    
    function purge_caches($user_id){
        global $appsol_social_streams_caches;
        foreach ($appsol_social_streams_caches as $cachetype => $caches) {
            foreach ($caches['fb'] as $cache) {
                if ($user_cache = new $cache(array('fb_user' => $user_id)))
                    $user_cache->purgeCache();
            }
        }
    }

    function retrieve_authentication() {
        header('Location: ' . $this->get_request_url());
    }

    function get_request_url() {
        $url = $this->getLoginUrl(array(
            'redirect_uri' => get_admin_url() . 'plugins.php?page=appsol_social_streams&fboauth=1&tab=facebook',
            'scope' => 'offline_access,read_stream,user_photos',
            'response_type' => 'code'));
        return $url;
    }

}

class appsolTwitterApi extends TwitterOAuth {

    public $user_id = null;
    public $profile = null;

    function __construct($userid = 0) {
        $this->user_id = isset($_GET['tw_user']) ? $_GET['tw_user'] : $userid;
        $tokens = get_option('appsol_social_streams_tw_token', array());
        $secrets = get_option('appsol_social_streams_tw_token_secret', array());
        $token = isset($tokens[$this->user_id]) ? $tokens[$this->user_id] : null;
        $secret = isset($secrets[$this->user_id]) ? $secrets[$this->user_id] : null;
        parent::__construct(APPSOL_TW_KEY, APPSOL_TW_SECRET, $token, $secret);
        if (isset($_GET['twoauth']) && $_GET['twoauth'] == 'social_streams')
            $access_token = $this->set_access_token();
        $this->connect();
    }

    function connect() {
        if (!$this->token)
            return false;
        /* If method is set change API call made. Test is called by default. */
        if (isset($_GET['revoke']) && $_GET['revoke'] == 'social_streams_twitter') {
            $this->revoke_authentication();
            return null;
        }
        if ($this->user_id) {
            $this->profile = $this->get('users/show', array('user_id' => $this->user_id));
        }
        return ($this->lastStatusCode() == 200) ? true : false;
    }

    function set_access_token() {
        $tokens = get_option('appsol_social_streams_tw_token', array());
        $secrets = get_option('appsol_social_streams_tw_token_secret', array());
        /* If the oauth_token is old redirect to the connect page. */
        if (isset($_REQUEST['oauth_token']) && $tokens[0] !== $_REQUEST['oauth_token']) {
            return false;
        }
        /* Request access tokens from twitter */
        $access_token = $this->getAccessToken($_REQUEST['oauth_verifier']);
        if ($this->lastStatusCode() == 200) {
            $account = $this->get('account/verify_credentials');
            $this->user_id = $account->id;
            update_option('appsol_social_streams_tw_last_msg', "Recieved new OAuth Token");
            $tokens[$this->user_id] = $access_token['oauth_token'];
            $tokens[0] = null;
            update_option('appsol_social_streams_tw_token', $tokens);
            $secrets[$this->user_id] = $access_token['oauth_token_secret'];
            $secrets[0] = null;
            update_option('appsol_social_streams_tw_token_secret', $secrets);
            $tw_users = get_option('appsol_social_streams_tw_users', array());
            $tw_users[$this->user_id] = $account->name;
            unset($tw_users[0]);
            update_option('appsol_social_streams_tw_users', $tw_users);
        } else {
            $msg = '<strong>Code:</strong>' . $this->lastStatusCode();
            $msg.= '<br /><strong>Request:</strong>' . $this->lastApiCall();
            _log('Failed to retrieve new OAuth Token Code: ' . $this->lastStatusCode() . 'Request: ' . $this->lastApiCall());
            update_option('appsol_social_streams_tw_last_msg', "Failed to retrieve new OAuth Token " . $msg);
            $this->revoke_authentication();
        }
        header('Location: ' . get_admin_url() . 'plugins.php?page=appsol_social_streams&tab=twitter');
    }

    function revoke_authentication() {
        $this->purge_options($this->user_id);
        $this->purge_caches($this->user_id);
        // Remove Twitter User
        $tw_users = get_option('appsol_social_streams_tw_users', array());
        unset($tw_users[$this->user_id]);
        update_option('appsol_social_streams_tw_users', $tw_users);
        update_option('appsol_social_streams_tw_last_msg', "Revoked active OAuth Token");
        header('Location: ' . get_admin_url() . 'plugins.php?page=appsol_social_streams&tab=twitter');
    }
    
    function purge_options($user_id){
        // Token
        $tokens = get_option('appsol_social_streams_tw_token', array());
        unset($tokens[$user_id]);
        update_option('appsol_social_streams_tw_token', $tokens);
        // Token Secret
        $secrets = get_option('appsol_social_streams_tw_token_secret', array());
        unset($secrets[$user_id]);
        update_option('appsol_social_streams_tw_token_secret', $secrets);
        // Followers
        $followers = get_option('appsol_social_streams_tw_followers', array());
        unset($followers[$user_id]);
        update_option('appsol_social_streams_tw_followers', $followers);
        // Spam Followers
        $spam_followers = get_option('appsol_social_streams_tw_spam_followers', array());
        unset($spam_followers[$user_id]);
        update_option('appsol_social_streams_tw_spam_followers', $spam_followers);
    }
    
    function purge_caches($user_id){
        global $appsol_social_streams_caches;
        foreach ($appsol_social_streams_caches as $cachetype => $caches) {
            foreach ($caches['tw'] as $cache) {
                if ($user_cache = new $cache(array('tw_user' => $user_id)))
                    $user_cache->purgeCache();
            }
        }
    }

    function get_request_url() {
        _log('appsolTwitterApi::get_request_url ' . $this->user_id);
        $url = null;
        /* Get temporary credentials. */
        $request_token = $this->getRequestToken(get_admin_url() . 'plugins.php?page=appsol_social_streams&twoauth=social_streams&tab=twitter');
        /* Save temporary credentials to user 0. */
        $tokens = get_option('appsol_social_streams_tw_token', array());
        $tokens[0] = $request_token['oauth_token'];
        $secrets = get_option('appsol_social_streams_tw_token_secret', array());
        $secrets[0] = $request_token['oauth_token_secret'];
        update_option('appsol_social_streams_tw_token', $tokens);
        update_option('appsol_social_streams_tw_token_secret', $secrets);
        if ($this->lastStatusCode() == 200)
            $url = $this->getAuthorizeURL($request_token['oauth_token']);
        return $url;
    }

}

class appsolLinkedinApi extends LinkedIn {

    public $user_id = null;
    public $profile = null;

    function __construct($userid = 0) {
        $this->user_id = isset($_GET['li_user']) ? $_GET['li_user'] : $userid;
        $params = array(
            'appKey' => APPSOL_LI_APIKEY,
            'appSecret' => APPSOL_LI_SECRET,
            'callbackUrl' => get_admin_url() . 'plugins.php?page=appsol_social_streams&' . LINKEDIN::_GET_TYPE . '=initiate&' . LINKEDIN::_GET_RESPONSE . '=1&lioauth=social_streams&tab=linkedin'
        );
        parent::__construct($params);
        $this->connect();
    }

    /**
     * Connect to LinkedIn API
     * @global type $appsol_li_user
     * @return LinkedIn 
     */
    function connect() {

//        if (!isset($_SESSION['linkedin']['social_streams']))
//            $_SESSION['linkedin']['social_streams'] = array();
//        update_option('appsol_social_streams_li_last_msg', "");
        if (isset($_GET['lioauth']) && $_GET['lioauth'] == 'social_streams')
            $this->set_access_token();
        $tokens = get_option('appsol_social_streams_li_token', array());
        $secrets = get_option('appsol_social_streams_li_token_secret', array());
        if (!isset($tokens[$this->user_id]) || !isset($secrets[$this->user_id]))
            return false;
        $this->setTokenAccess(array(
            'oauth_token' => $tokens[$this->user_id],
            'oauth_token_secret' => $secrets[$this->user_id],
            'oauth_expires_in' => 0,
            'oauth_authorization_expires_in' => 0
        ));
        if (isset($_GET['revoke']) && $_GET['revoke'] == 'social_streams_linkedin') {
            $this->revoke_authentication();
            return null;
        }
        $this->setResponseFormat(LINKEDIN::_RESPONSE_JSON);
        $response = $this->profile('~:(id,first-name,last-name,picture-url)');
        if ($response['success']) {
            $this->profile = json_decode($response['linkedin']);
            return true;
        }
        return false;
    }

    function set_access_token() {
        $tokens = get_option('appsol_social_streams_li_token', array());
        $secrets = get_option('appsol_social_streams_li_token_secret', array());
        try {
//            $response = $this->retrieveTokenAccess($_SESSION['linkedin']['social_streams']['oauth']['oauth_token'], $_SESSION['linkedin']['social_streams']['oauth']['oauth_token_secret'], $_GET['oauth_verifier']);
            $response = $this->retrieveTokenAccess($tokens[$this->user_id], $secrets[$this->user_id], $_GET['oauth_verifier']);
            update_option('appsol_social_streams_li_last_msg', '<pre>' . print_r($response, true)) . '</pre>';
        } catch (LinkedInException $e) {
            $msg = '<strong>Error:</strong>' . $e->__toString();
            update_option('appsol_social_streams_li_last_msg', $msg);
            return false;
        }
        if ($response['success']) {
            // the request went through without an error, gather user's 'access' tokens
            $this->setResponseFormat(LINKEDIN::_RESPONSE_JSON);
            $profile = $this->profile('~:(id,first-name,last-name)');
            if ($profile['success']) {
                $profile = json_decode($profile['linkedin']);
                $this->user_id = $profile->id;
                update_option('appsol_social_streams_li_last_msg', "Recieved new OAuth Token");
                $tokens[$this->user_id] = $response['linkedin']['oauth_token'];
                update_option('appsol_social_streams_li_token', $tokens);
                $secrets[$this->user_id] = $response['linkedin']['oauth_token_secret'];
                update_option('appsol_social_streams_li_token_secret', $secrets);
                $li_users = get_option('appsol_social_streams_li_users', array());
                $li_users[$this->user_id] = $profile->firstName . ' ' . $profile->lastName;
                update_option('appsol_social_streams_li_users', $li_users);
            } else {
                update_option('appsol_social_streams_li_last_msg', "Failed to retrieve User Profile :<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response, TRUE) . "</pre>");
            }
//            $_SESSION['linkedin']['social_streams']['access'] = $response['linkedin'];
//            update_option('appsol_li_token', $response['linkedin']['oauth_token']);
//            update_option('appsol_li_token_secret', $response['linkedin']['oauth_token_secret']);
//            update_option('appsol_social_streams_li_last_msg', "Recieved new OAuth Token");
//            // set the user as authorized for future quick reference
//            $_SESSION['linkedin']['social_streams']['authorized'] = TRUE;
        } else {
            update_option('appsol_social_streams_li_last_msg', "Failed to retrieve new OAuth Token :<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response, TRUE) . "</pre>");
        }
        header('Location: ' . get_admin_url() . 'plugins.php?page=appsol_social_streams&tab=linkedin');
    }

    function revoke_authentication() {
        $response = $this->revoke();
        if ($response['success'] === TRUE) {
            $tokens = get_option('appsol_social_streams_li_token', array());
            unset($tokens[$this->user_id]);
            $secrets = get_option('appsol_social_streams_li_token_secret', array());
            unset($secrets[$this->user_id]);
            update_option('appsol_social_streams_li_token', $tokens);
            update_option('appsol_social_streams_li_token_secret', $secrets);
            $li_users = get_option('appsol_social_streams_li_users', array());
            unset($li_users[$this->user_id]);
            update_option('appsol_social_streams_li_users', $li_users);
            foreach ($appsol_social_streams_caches as $cachetype) {
                foreach ($cachetype['li'] as $cache) {
                    if ($user_cache = new cache(array('li_user' => $this->user_id)))
                        $user_cache->purgeCache();
                }
            }
            update_option('appsol_social_streams_li_last_msg', "Revoked active OAuth Token");
        } else {
            update_option('appsol_social_streams_li_last_msg', "Error revoking user's token:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response, TRUE) . "</pre>");
        }
        header('Location: ' . get_admin_url() . 'plugins.php?page=appsol_social_streams&tab=linkedin');
    }

    function get_request_url() {
        $url = null;
        $response = $this->retrieveTokenRequest();
        if ($response['success'] === TRUE) {
            // store the request token
            $tokens = get_option('appsol_social_streams_li_token', array());
            $tokens[$this->user_id] = $response['linkedin']['oauth_token'];
            $secrets = get_option('appsol_social_streams_li_token_secret', array());
            $secrets[$this->user_id] = $response['linkedin']['oauth_token_secret'];
            update_option('appsol_social_streams_li_token', $tokens);
            update_option('appsol_social_streams_li_token_secret', $secrets);
//            $_SESSION['linkedin']['social_streams']['oauth'] = $response['linkedin'];

            $url = LINKEDIN::_URL_AUTHENTICATE . $response['linkedin']['oauth_token'];
        } else {
            update_option('appsol_social_streams_li_last_msg', "Request token retrieval failed:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response, TRUE) . "</pre>");
        }
        return $url;
    }

}

class appsolGoogleApi extends apiPlusService {

    protected $client = null;
    public $user_id = null;
    public $profile = null;

    function __construct($userid = 0) {
        $this->user_id = isset($_GET['gp_user']) ? $_GET['gp_user'] : $userid;
        $this->client = new apiClient();
        $this->client->setApplicationName("Social Streams");
        $this->client->setClientId(APPSOL_GP_ID);
        $this->client->setClientSecret(APPSOL_GP_SECRET);
        $this->client->setRedirectUri(get_admin_url() . 'plugins.php?page=appsol_social_streams&gpoauth=social_streams&tab=googleplus');
        $this->client->setDeveloperKey(APPSOL_GP_KEY);
        $this->client->setAccessType('offline');
        parent::__construct($this->client);
        if (isset($_GET['gpoauth']) && $_GET['gpoauth'] == 'social_streams')
            $this->set_access_token();
        $this->connect();
    }

    function connect() {
        update_option('appsol_social_streams_gp_last_msg', "");
        
        $tokens = get_option('appsol_social_streams_gp_token', array());
        if (!isset($tokens[$this->user_id]))
            return false;
        if (isset($_GET['revoke']) && $_GET['revoke'] == 'social_streams_gplus') {
            $this->revoke_authentication();
            return null;
        }
        $this->client->setAccessToken($tokens[$this->user_id]);
        if ($this->client->getAccessToken()) {
            try {
                $this->profile = $this->people->get('me');
            } catch (apiServiceException $e) {
               _log($e->getMessage());
                update_option('appsol_social_streams_gp_last_msg', $e->getMessage());
            }
            $this->refresh_token();
            return true;
        }
        return false;
    }

    function set_access_token() {
        if (isset($_GET['code'])) {
            /* Request access tokens from Google */
            try {
                $this->client->authenticate();
            } catch (apiAuthException $e) {
                update_option('appsol_social_streams_gp_last_msg', "Failed to retrieve new OAuth Token: " . $e->getMessage());
            }
            $access_token = $this->client->getAccessToken();
        }
        if ($access_token) {
            $this->client->setAccessToken($access_token);
            $profile = $this->people->get('me');
            $this->user_id = $profile['id'];
            $tokens = get_option('appsol_social_streams_gp_token', array());
            $tokens[$this->user_id] = $access_token;
            update_option('appsol_social_streams_gp_token', $tokens);
            // Set user in Google+ Users array
            $gp_users = get_option('appsol_social_streams_gp_users', array());
            $gp_users[$this->user_id] = $profile['displayName'];
            update_option('appsol_social_streams_gp_users', $gp_users);
            update_option('appsol_social_streams_gp_last_msg', "Recieved new OAuth Token");
        } else {
            update_option('appsol_social_streams_gp_last_msg', "Failed to retrieve new OAuth Token ");
        }
        header('Location: ' . get_admin_url() . 'plugins.php?page=appsol_social_streams&tab=googleplus');
    }

    function revoke_authentication() {
        $this->purge_options($this->user_id);
        $this->purge_caches($this->user_id);
        
        $gp_users = get_option('appsol_social_streams_gp_users', array());
        unset($gp_users[$this->user_id]);
        update_option('appsol_social_streams_gp_users', $gp_users);
        
        update_option('appsol_social_streams_gp_last_msg', "Revoked active OAuth Token");
        header('Location: ' . get_admin_url() . 'plugins.php?page=appsol_social_streams&tab=googleplus');
    }
    
    function purge_options($user_id){
        // Token
        $tokens = get_option('appsol_social_streams_gp_token', array());
        unset($tokens[$this->user_id]);
        update_option('appsol_social_streams_gp_token', $tokens);
        // Followers
        $circles = get_option('appsol_social_streams_gp_circles', array());
        unset($circles[$user_id]);
        update_option('appsol_social_streams_gp_circles', $circles);
    }
    
    function purge_caches($user_id){
        global $appsol_social_streams_caches;
        foreach ($appsol_social_streams_caches as $cachetype => $caches) {
            foreach ($caches['gp'] as $cache) {
                if ($user_cache = new $cache(array('gp_user' => $user_id)))
                    $user_cache->purgeCache();
            }
        }
    }

    function get_request_url() {
        $url = $this->client->createAuthUrl();
        return $url;
    }

    function refresh_token() {
        $access_token = $this->client->getAccessToken();
        $tokens = get_option('appsol_social_streams_gp_token', array());
        $tokens[$this->user_id] = $access_token;
        update_option('appsol_social_streams_gp_token', $tokens);
    }

}

?>
