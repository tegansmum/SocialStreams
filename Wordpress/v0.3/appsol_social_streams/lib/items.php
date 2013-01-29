<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class SocialStreamsItemCache {

    public static function save($client_id, $data) {
        SocialStreamsHelper::log($client_id);
        SocialStreamsHelper::log($data);
        $index_transient_name = $data->network . '_' . $client_id . '_items';
        $item_transient_name = $data->network . '_' . $data->networkid;
        if (!$index = SocialStreamsHelper::getTransient($index_transient_name))
            $index = array();
        $item_lifespan = SocialStreamsHelper::getParameter('item_period');
        $index[$data->networkid] = array(
            'expires' => time() + $item_lifespan,
            'transient_name' => $item_transient_name);

        SocialStreamsHelper::setTransient($item_transient_name, $data->item, appsolSocialStreams::$item_lifespan);
        SocialStreamsHelper::setTransient($index_transient_name, $index, appsolSocialStreams::$item_lifespan);

        return true;
    }

    public static function refresh($force = false, $network = '') {
        SocialStreamsHelper::log($network);
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
                    if ($items = $api->getItems($network['clientid'])) {
                        $new = $new ? $new + count($items) : count($items);
                        foreach ($items as $item) {
                            if (!self::save($network['clientid'], $item->store()))
                                appsolSocialStreams::add_message('Failed to Save Item ' . $item->networkid . ' for Client ID ' . $network['clientid'] . ' on Network ' . $network['network'], true);
                        }
                    }
                }
                if (!$force)
                    break;
            }
        }
        return $new;
    }

    public static function checkExpired($network, $client_id) {
        $index_transient_name = $network . '_' . $client_id . '_items';
        $item_lifespan = SocialStreamsHelper::getParameter('item_period');
        if (!$index = SocialStreamsHelper::getTransient($index_transient_name))
            return true;
        // If the expiry time is in the past we need to refresh
        foreach ($index as $networkid => $transient)
            if ($transient['expires'] < time())
                return true;
        return false;
    }

    public static function getItemIds($network, $client_id, $expiring = false) {
        $index_transient_name = $network . '_' . $client_id . '_items';
        $item_lifespan = SocialStreamsHelper::getParameter('item_period');
        if (!$index = SocialStreamsHelper::getTransient($index_transient_name))
            $index = array();
        $items = array();
        foreach ($index as $networkid => $transient) {
            if ($expiring) {
                // if the expiry time is in the past the item is expiring
                if ($transient['expires'] < time())
                    $items[$networkid] = $transient;
            } else {
                $items[$networkid] = $transient;
            }
        }
        return $items;
    }

    public static function getItems($network, $client_id, $networkid = false) {
        SocialStreamsHelper::log('Network: ' . $network . ' ClientID: ' . $client_id);
        $index_transient_name = $network . '_' . $client_id . '_items';
        $items = array();
        if ($index = SocialStreamsHelper::getTransient($index_transient_name)) {
            if ($networkid) {
                if (isset($index[$networkid]))
                    $item = SocialStreamsHelper::getItem($network, 'div');
                if ($stored_item = SocialStreamsHelper::getTransient($index[$networkid]['transient_name'])) {
                    $item->setUpdate($stored_item);
                    $item->expires = date('Y-m-d H:i:s', $index[$networkid]['expires']);
                    return $item;
                }
                return false;
            }
            foreach ($index as $networkid => $item_transient) {
                $item = SocialStreamsHelper::getItem($network, 'div');
                if ($stored_item = SocialStreamsHelper::getTransient($item_transient['transient_name'])) {
                    $item->setUpdate($stored_item);
                    $item->expires = date('Y-m-d H:i:s', $item_transient['expires']);
                    $items[$networkid] = $item;
                }
            }
        }
        return $items;
    }

    public static function deleteClientItems($network, $client_id, $force = false) {
        $index_transient_name = $network . '_' . $client_id . '_items';
        $new_index = array();
        if ($index = SocialStreamsHelper::getTransient($index_transient_name)) {
            foreach ($index as $networkid => $item_transient) {
                if (!$force && $networkid == $client_id) {
                    $new_index[$networkid] = $item_transient;
                    continue;
                }
                SocialStreamsHelper::removeTransient($item_transient['transient_name']);
            }
            if ($force)
                SocialStreamsHelper::removeTransient($index_transient_name);
            else
                SocialStreamsHelper::setTransient($index_transient_name, $new_index, SocialStreamsHelper::getParameter('item_period') * 2);
        }
    }

}

?>
