<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
//require_once JPATH_COMPONENT_ADMINISTRATOR . DS . 'lib' . DS . 'api_config.php';
require_once 'socialstream_profile.php';
require_once 'socialstream_item.php';

require_once 'facebook/facebook.php';
if (!class_exists('TwitterOAuth'))
    require_once('twitter/twitteroauth.php');
if (!class_exists('LinkedIn'))
    require_once('linkedin/linkedin_3.3.0.class.php');
if (!class_exists('apiClient'))
    require_once 'google/apiClient.php';
if (!class_exists('apiPlusService'))
    require_once 'google/contrib/apiPlusService.php';

class appsolFacebookApi extends AppsolSocialStreamsFacebook {

    public $user_id = null;
    public $params;

    function __construct() {
        $this->params = array(
            'network' => 'facebook',
            'nicename' => 'Facebook',
            'oauth' => array(
                'apikey' => 'appid',
                'apisecret' => 'secret')
        );
        $jparams = JComponentHelper::getParams('com_socialstreams');
        $params = array(
            'appId' => $jparams->get('facebook_appkey'),
            'secret' => $jparams->get('facebook_appsecret')
        );
        parent::__construct($params);
        $this->connect();
    }

    function connect() {
        $session = & JFactory::getSession();
//        $registry = & JFactory::getConfig();
        // Get the user ID.
        $this->user_id = $this->getUser();
        if (!$this->user) {
            $this->clearAllPersistentData();
        }
        $this->setPersistentData('access_token', $this->getAccessToken());
        $this->setPersistentData('user_id', $this->user_id);
        try {
            $this->user = new appsolFacebookProfile();
            $user = $this->api('/' . $this->user_id);
            $user = json_decode(json_encode($user));
            $this->user->setProfile($user);
        } catch (FacebookApiException $e) {
            $session->set('facebook_last_msg', $e->__toString(), 'socialstreams');
            $this->user = null;
        }
        return true;
    }
    
    function set_access_token() {
        header('Location: ' . JRoute::_('index.php?option=com_socialstreams&controller=config&task=&network=' . $this->params['network']));
    }

    function revoke_authentication() {
        $session = & JFactory::getSession();
        $registry = & JFactory::getConfig();
        try {
            $response = $this->api(array(
                'method' => 'auth.revokeExtendedPermission'
                    ));
        } catch (FacebookApiException $e) {
            $session->set('facebook_last_msg', $e->__toString(), 'socialstreams');
        }
        $this->clearAllPersistentData();
        $session->set('facebook_last_msg', "Revoked active OAuth Token", 'socialstreams');
        header('Location: ' . JRoute::_('index.php?option=com_socialstreams&controller=config&task=&network=' . $this->params['network']));
    }

    function get_request_url() {
        jimport('joomla.methods');
        $url = $this->getLoginUrl(array(
            'redirect_uri' => JURI::base() . 'index.php?option=com_socialstreams&task=socialstream.setauth&network=' . $this->params['network'],
            'scope' => 'read_stream,publish_stream',
            'response_type' => 'code'));
        return $url;
    }

    function get_logout_url() {
        return JURI::base() . '/index.php?option=com_socialstreams&controller=config&task=clearaccess&network=' . $this->params['network'];
    }

}

class appsolTwitterApi extends TwitterOAuth {

    public $user = null;
    public $params;
    private $config_file;

    function __construct() {
        $this->config_file = JPATH_COMPONENT . DS . 'socialstreams.ini';
        $this->params = array(
            'network' => 'twitter',
            'nicename' => 'Twitter',
            'oauth' => array(
                'clientid' => 'username',
                'apikey' => 'consumerkey',
                'apisecret' => 'consumersecret')
        );
        $app = & JFactory::getApplication();
//        $registry = & JFactory::getConfig();
        
        $jparams = JComponentHelper::getParams('com_socialstreams');
        $params = array(
            'appid' => $jparams->get('twitter_appkey'),
            'secret' => $jparams->get('twitter_appsecret')
        );
//        $apikey = $registry->getValue('socialstreams.twitter_' . $this->params['oauth']['apikey'], '');
//        $apisecret = $registry->getValue('socialstreams.twitter_' . $this->params['oauth']['apisecret'], '');
        $token = $app->getUserState('socialstreams.twitter_oauth_token', '');
//                $session->get('twitter_oauth_token', '', 'socialstreams') : $registry->getValue('socialstreams.twitter_token', '');
        $token_secret = $app->getUserState('socialstreams.twitter_oauth_token_secret', '');
//                $session->get('twitter_oauth_token_secret', '', 'socialstreams') : $registry->getValue('socialstreams.twitter_token_secret', '');
        parent::__construct($params['appid'], $params['secret'], $token, $token_secret);
        $this->connect();
    }

    function connect() {
        $session = & JFactory::getSession();
        $registry = & JFactory::getConfig();
        if (!$registry->getValue('socialstreams.twitter_token', ''))
            return false;
        $this->user = new appsolTwitterProfile();
        $this->user->setProfile($this->get('users/show', array('screen_name' => $registry->getValue('socialstreams.twitter_' . $this->params['oauth']['clientid'], ''))));
        return ($this->lastStatusCode() == 200) ? true : false;
    }

    function set_access_token() {
        $session = & JFactory::getSession();
        $registry = & JFactory::getConfig();
        /* If the oauth_token is old redirect to the connect page. */

        if (JRequest::getVar('oauth_token', '') && $session->get('twitter_oauth_token', '', 'socialstreams') !== JRequest::getVar('oauth_token', '')) {
            $session->set('twitter_oauth_status', 'oldtoken', 'socialstreams');
            return false;
        }
        /* Request access tokens from twitter */
        $access_token = $this->getAccessToken(JRequest::getVar('oauth_verifier', ''));
        if ($this->lastStatusCode() == 200) {
            $session->set('twitter_last_msg', 'Recieved new OAuth Token', 'socialstreams');
            $registry->setValue('socialstreams.twitter_token', $access_token['oauth_token']);
            $registry->setValue('socialstreams.twitter_token_secret', $access_token['oauth_token_secret']);
        } else {
            $msg = 'Code: ' . $this->lastStatusCode() . ' Request: ' . $this->lastApiCall();
            $session->set('twitter_last_msg', $msg, 'socialstreams');
            $registry->setValue('socialstreams.twitter_token', '');
            $registry->setValue('socialstreams.twitter_token_secret', '');
        }
        /* Remove no longer needed request tokens */
        $session->clear('twitter_oauth_token', 'socialstreams');
        $session->clear('twitter_oauth_token_secret', 'socialstreams');
        header('Location: ' . JRoute::_('index.php?option=com_socialstreams&task=socialstream.display&network=twitter'));
    }

    function revoke_authentication() {
        $session = & JFactory::getSession();
        $registry = & JFactory::getConfig();
        $session->clear('twitter_oauth_token', 'socialstreams');
        $session->clear('twitter_oauth_token_secret', 'socialstreams');
        $session->clear('twitter_oauth_status', 'socialstreams');
        $registry->setValue('socialstreams.twitter_token', '');
        $registry->setValue('socialstreams.twitter_token_secret', '');
        $session->set('twitter_last_msg', 'Revoked active OAuth Token', 'socialstreams');
        header('Location: ' . JRoute::_('index.php?option=com_socialstreams&task=socialstream.display&network=' . $this->params['network']));
    }

    function get_request_url() {
        $app = & JFactory::getApplication();
        $url = null;
        /* Get temporary credentials. */
        $request_token = $this->getRequestToken(JURI::base() . 'index.php?option=com_socialstreams&task=socialstream.setauth&network=twitter');
        /* Save temporary credentials to session. */
        $app->setUserState('socialstreams.twitter_oauth_token', $request_token['oauth_token']);
        $app->setUserState('socialstreams.twitter_oauth_token_secret', $request_token['oauth_token_secret']);
        if ($this->lastStatusCode() == 200)
            $url = $this->getAuthorizeURL($request_token['oauth_token']);
        return $url;
    }

    function get_logout_url() {
        return JURI::base() . 'index.php?option=com_socialstreams&task=socialstream.clearauth&network=' . $this->params['network'];
    }

}

class appsolLinkedinApi extends LinkedIn {

    public $user = null;

    function __construct($apikey, $apisecret) {
        $params = array(
            'appKey' => $apikey,
            'appSecret' => $apisecret,
            'callbackUrl' => JRoute::_('index.php?option=com_socialstreams&controller=oauth&task=setaccesstoken&network=linkedin&' . LINKEDIN::_GET_TYPE . '=initiate&' . LINKEDIN::_GET_RESPONSE . '=1')
        );
        parent::__construct($params);
        $this->connect();
    }

    /**
     * Connect to LinkedIn API
     * @global type $appsol_li_user
     * @return LinkedIn 
     */
    function connect($token, $secret) {

        if (!isset($_SESSION['linkedin']['social_streams']))
            $_SESSION['linkedin']['social_streams'] = array();
//        update_option('appsol_li_last_msg', "");
        if (isset($_GET['lioauth']) && $_GET['lioauth'] == 'social_streams')
            $this->set_access_token();
        if (!get_option('appsol_li_token'))
            return false;
        $this->setTokenAccess(array(
            'oauth_token' => get_option('appsol_li_token'),
            'oauth_token_secret' => get_option('appsol_li_token_secret'),
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
            $this->user = json_decode($response['linkedin']);
            return true;
        }
        return false;
    }

    function set_access_token() {
        try {
            $response = $this->retrieveTokenAccess($_SESSION['linkedin']['social_streams']['oauth']['oauth_token'], $_SESSION['linkedin']['social_streams']['oauth']['oauth_token_secret'], $_GET['oauth_verifier']);
            update_option('appsol_li_last_msg', '<pre>' . print_r($response, true)) . '</pre>';
        } catch (LinkedInException $e) {
            $msg = '<strong>Error:</strong>' . $e->__toString();
            update_option('appsol_li_last_msg', $msg);
            return false;
        }
        if ($response['success']) {
            // the request went through without an error, gather user's 'access' tokens
            $_SESSION['linkedin']['social_streams']['access'] = $response['linkedin'];
            update_option('appsol_li_token', $response['linkedin']['oauth_token']);
            update_option('appsol_li_token_secret', $response['linkedin']['oauth_token_secret']);
            update_option('appsol_li_last_msg', "Recieved new OAuth Token");
            // set the user as authorized for future quick reference
            $_SESSION['linkedin']['social_streams']['authorized'] = TRUE;
        } else {
            update_option('appsol_li_last_msg', "Failed to retrieve new OAuth Token :<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response, TRUE) . "</pre>");
        }
        header('Location: ' . get_admin_url() . 'options-general.php?page=appsol_social_streams');
    }

    function revoke_authentication() {
        $response = $this->revoke();
        if ($response['success'] === TRUE) {
            $_SESSION['linkedin']['social_streams'] = array();
            update_option('appsol_li_token', '');
            update_option('appsol_li_token_secret', '');
            update_option('appsol_li_last_msg', "Revoked active OAuth Token");
        } else {
            update_option('appsol_li_last_msg', "Error revoking user's token:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response, TRUE) . "</pre>");
        }
        header('Location: ' . get_admin_url() . 'options-general.php?page=appsol_social_streams');
    }

    function get_request_url() {
        $url = null;
        $response = $this->retrieveTokenRequest();
        if ($response['success'] === TRUE) {
            // store the request token
            $_SESSION['linkedin']['social_streams']['oauth'] = $response['linkedin'];

            $url = LINKEDIN::_URL_AUTHENTICATE . $response['linkedin']['oauth_token'];
        } else {
            update_option('appsol_li_last_msg', "Request token retrieval failed:<br /><br />RESPONSE:<br /><br /><pre>" . print_r($response, TRUE) . "</pre>");
        }
        return $url;
    }

}

class appsolGoogleApi extends apiPlusService {

    protected $client = null;
    public $user = null;

    function __construct() {
        $this->client = new apiClient();
        $this->client->setApplicationName("Social Streams");
        $this->client->setClientId(APPSOL_GP_ID);
        $this->client->setClientSecret(APPSOL_GP_SECRET);
        $this->client->setRedirectUri(get_admin_url() . 'options-general.php?page=appsol_social_streams&gpoauth=social_streams');
        $this->client->setDeveloperKey(APPSOL_GP_KEY);
        parent::__construct($this->client);
        $this->connect();
    }

    function connect() {
//        update_option('appsol_gp_last_msg', "");
        if (isset($_GET['gpoauth']) && $_GET['gpoauth'] == 'social_streams') {
            $this->set_access_token();
        }
        if (!get_option('appsol_gp_token'))
            return false;
        if (isset($_GET['revoke']) && $_GET['revoke'] == 'social_streams_gplus') {
            $this->revoke_authentication();
            return null;
        }
        $access_token = get_option('appsol_gp_token');
        if (!empty($access_token)) {
            $this->client->setAccessToken($access_token);
            if ($this->client->getAccessToken()) {
                $this->user = $this->people->get('me');
                return true;
            }
        }
        return false;
    }

    function set_access_token() {
        $access_token = null;
        if (isset($_GET['code'])) {
            /* Request access tokens from Google */
            $this->client->authenticate();
            $access_token = $this->client->getAccessToken();
        }
        if ($access_token) {
            $this->client->setAccessToken($access_token);
            update_option('appsol_gp_last_msg', "Recieved new OAuth Token");
            update_option('appsol_gp_token', $access_token);
        } else {
            update_option('appsol_gp_last_msg', "Failed to retrieve new OAuth Token ");
            update_option('appsol_gp_token', null);
        }
        header('Location: ' . get_admin_url() . 'options-general.php?page=appsol_social_streams');
    }

    function revoke_authentication() {
        delete_option('appsol_gp_token');
        update_option('appsol_gp_last_msg', "Revoked active OAuth Token");
        header('Location: ' . get_admin_url() . 'options-general.php?page=appsol_social_streams');
    }

    function get_request_url() {
        $url = $this->client->createAuthUrl();
        return $url;
    }

}

?>
