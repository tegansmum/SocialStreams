<?php

/**
 * Copyright 2011 Facebook, Inc.
 *
 * Licensed under the Apache License, Version 2.0 (the "License"); you may
 * not use this file except in compliance with the License. You may obtain
 * a copy of the License at
 *
 *     http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS, WITHOUT
 * WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied. See the
 * License for the specific language governing permissions and limitations
 * under the License.
 */
if (!class_exists('BaseFacebook'))
    require_once "base_facebook.php";

/**
 * Extends the BaseFacebook class with the intent of using
 * PHP sessions to store user ids and access tokens.
 */
class AppsolSocialStreamsFacebook extends BaseFacebook {

    public static $CURL_OPTS = array(
        CURLOPT_CONNECTTIMEOUT => 10,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_USERAGENT => 'facebook-php-3.1',
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_MAXREDIRS => 1
    );

    /**
     * Identical to the parent constructor, except that
     * we start a PHP session to store the user ID and
     * access token if during the course of execution
     * we discover them.
     * 
     * @param Array $config the application configuration.
     * @see BaseFacebook::__construct in facebook.php
     */
    public function __construct($config) {
        if (!session_id()) {
            session_start();
        }
        parent::__construct($config);
    }

    protected static $kSupportedKeys =
            array('state', 'code', 'access_token', 'user_id');

    /**
     * Provides the implementations of the inherited abstract
     * methods.  The implementation uses PHP sessions to maintain
     * a store for authorization codes, user ids, CSRF states, and
     * access tokens.
     */
    protected function setPersistentData($key, $value) {
        if (!in_array($key, self::$kSupportedKeys)) {
            self::errorLog('Unsupported key passed to setPersistentData.');
            return;
        }
        $var_name = $this->constructSessionVariableName($key);
        $index = $this->user_id ? $this->user_id : 0;
        $var_data = get_option($var_name, array());
        $var_data[$index] = $value;
        update_option($var_name, $var_data);
    }

    protected function getPersistentData($key, $default = false) {
        if (!in_array($key, self::$kSupportedKeys)) {
            self::errorLog('Unsupported key passed to getPersistentData.');
            return $default;
        }
        $var_name = $this->constructSessionVariableName($key);
        if (!$var_data = get_option($var_name))
            return $default;
        $index = $this->user_id ? $this->user_id : 0;
        return is_array($var_data) && array_key_exists($index, $var_data) ?
                $var_data[$index] : $default;
    }

    protected function clearPersistentData($key) {
        if (!in_array($key, self::$kSupportedKeys)) {
            self::errorLog('Unsupported key passed to clearPersistentData.');
            return;
        }

        $var_name = $this->constructSessionVariableName($key);
        $index = $this->user_id ? $this->user_id : 0;
        $var_data = get_option($var_name, array());
        unset($var_data[$index]);
        if ($index != 0 && isset($var_data[0]))
            unset($var_data[0]);
        update_option($var_name, $var_data);
//        delete_option($var_name);
    }

    public function clearAllPersistentData() {
        foreach (self::$kSupportedKeys as $key) {
            $this->clearPersistentData($key);
        }
    }

    protected function constructSessionVariableName($key) {
        $params = array('appsol_social_streams_fb', $this->getAppId(), $key);
        return implode('_', $params);
    }

    public function getAccessTokenFromCode($code, $redirect_uri = null) {
        return parent::getAccessTokenFromCode($code, $redirect_uri);
    }

    public function getSignedRequest() {
        if (!$this->signedRequest) {
            if (isset($_POST['signed_request'])) {
                $this->signedRequest = $this->parseSignedRequest(
                        $_POST['signed_request']);
            } elseif (isset($_GET['signed_request'])) {
                $this->signedRequest = $this->parseSignedRequest(
                        $_GET['signed_request']);
            }
        }
        return $this->signedRequest;
    }

}
