<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class SocialStreamsProfileCache {

    public static function save($client_id, $data) {
        SocialStreamsHelper::log($client_id);
        SocialStreamsHelper::log($data);
        
        $index_transient_name = $data->network . '_' . $client_id . '_profiles';
        $profile_transient_name = $data->network . '_' . $data->networkid;
        if (!$index = SocialStreamsHelper::getTransient($index_transient_name))
            $index = array();
        $profile_lifespan = SocialStreamsHelper::getParameter('profile_period');
        $index[$data->networkid] = array(
            'expires' => time() + $profile_lifespan,
            'transient_name' => $profile_transient_name);

        SocialStreamsHelper::setTransient($profile_transient_name, $data->profile, appsolSocialStreams::$profile_lifespan);
        SocialStreamsHelper::setTransient($index_transient_name, $index, appsolSocialStreams::$profile_lifespan);
        return true;
    }

    public static function refresh($force = false, $network = '') {
        SocialStreamsHelper::log();
        require_once APPSOL_SOCIAL_STREAMS_PATH . 'api/socialstreams.php';
        $new = 0;
        // Load the authenticated networks into an array and randomise it
        $networks = SocialStreamsHelper::getAuthenticatedAccounts($network);

        shuffle($networks);
        // Work through the networks looking for out of date profiles
        foreach ($networks as $network) {
            $fetch = false;

            if (self::checkExpired($network['network'], $network['clientid']) || $force) {

                if ($api = SocialStreamsHelper::getApi($network['network'], $network['clientid'])) {
                    $connection_count = 0;
                    if ($connections = $api->getConnectedProfiles($connection_count)) {
                        $new = $new ? $new + count($connections) : count($connections);
                        foreach ($connections as $profile) {
                            if (!self::save($network['clientid'], $profile->store()))
                                appsolSocialStreams::add_message('Failed to Save Profile ' . $profile->name . ' for Client ID ' . $network['clientid'] . ' on Network ' . $network['network'], true);
                        }
                    }
                    if ($profile = $api->getProfile($network['clientid'])) {
                        if (!self::save($network['clientid'], $profile->store($connection_count)))
                            appsolSocialStreams::add_message('Failed to Save Profile ' . $profile->name . ' for Client ID ' . $network['clientid'] . ' on Network ' . $network['network'], true);
                    }
                }
                if (!$force)
                    break;
            }
        }
        return $new;
    }

    public static function checkExpired($network, $client_id) {
        $index_transient_name = $network . '_' . $client_id . '_profiles';
        $profile_lifespan = SocialStreamsHelper::getParameter('profile_period');
        if (!$index = SocialStreamsHelper::getTransient($index_transient_name))
            return true;
        // If the expiry time is in the past we need to refresh
        foreach ($index as $networkid => $transient)
            if ($transient['expires'] < time())
                return true;
        return false;
    }

    public static function getProfileIds($network, $client_id, $expiring = false) {
        $index_transient_name = $network . '_' . $client_id . '_profiles';
        $profile_lifespan = SocialStreamsHelper::getParameter('profile_period');
        if (!$index = SocialStreamsHelper::getTransient($index_transient_name))
            $index = array();
        $profiles = array();
        foreach ($index as $networkid => $transient) {
            // if the expiry time is in the past the profile is expiring
            if ($expiring) {
                if ($transient['expires'] < time())
                    $profiles[$networkid] = $transient;
            } else {
                $profiles[$networkid] = $transient;
            }
        }
        return $profiles;
    }

    public static function getProfiles($network, $client_id, $networkid = false) {
        $index_transient_name = $network . '_' . $client_id . '_profiles';
        $profiles = array();

        if ($index = SocialStreamsHelper::getTransient($index_transient_name)) {
            if ($networkid) {
                if (isset($index[$networkid])) {
                    $profile = SocialStreamsHelper::getProfile($network, 'li');
                    $profile->setProfile(SocialStreamsHelper::getTransient($index[$networkid]['transient_name']));
                    $profile->expires = date('Y-m-d H:i:s', $index[$networkid]['expires']);
                    return $profile;
                }
                return false;
            }
            foreach ($index as $networkid => $profile_transient) {
                $profile = SocialStreamsHelper::getProfile($network, 'li');
                $profile->setProfile(SocialStreamsHelper::getTransient($profile_transient['transient_name']));
                $profile->expires = date('Y-m-d H:i:s', $profile_transient['expires']);
                $profiles[$networkid] = $profile;
            }
        }
        return $profiles;
    }

    public static function deleteClientProfiles($network, $client_id, $force = false) {
        $index_transient_name = $network . '_' . $client_id . '_profiles';
        $new_index = array();
        if ($index = SocialStreamsHelper::getTransient($index_transient_name)) {
            foreach ($index as $networkid => $profile_transient) {
                if (!$force && $networkid == $client_id) {
                    $new_index[$networkid] = $profile_transient;
                    continue;
                }
                SocialStreamsHelper::removeTransient($profile_transient['transient_name']);
            }
            if ($force)
                SocialStreamsHelper::removeTransient($index_transient_name);
            else
                SocialStreamsHelper::setTransient($index_transient_name, $new_index, SocialStreamsHelper::getParameter('profile_period') * 2);
        }
    }

}

?>
