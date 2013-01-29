<?php

/*
  Plugin Name: AppSol Social Streams
  Plugin URI: http://www.appropriatesolutions.co.uk/wordpress/plugins
  Description: Shows Twitter and Facebook data
  Version: 0.3.1
  Author: Stuart
  Author URI: http://www.mouse-cheese.com
  License: GPL2
  Copyright 2011  Stuart Laverick  (email : stuart@mouse-cheese.com)

  This program is free software; you can redistribute it and/or modify
  it under the terms of the GNU General Public License, version 2, as
  published by the Free Software Foundation.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU General Public License for more details.

  You should have received a copy of the GNU General Public License
  along with this program; if not, write to the Free Software
  Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
 */

define('APPSOL_SOCIAL_STREAMS_PATH', plugin_dir_path(__FILE__));
require_once APPSOL_SOCIAL_STREAMS_PATH . 'api/socialstreams.php';
require_once APPSOL_SOCIAL_STREAMS_PATH . 'lib/widget.php';
//require_once APPSOL_SOCIAL_STREAMS_PATH . 'lib/connections.php';
//require_once APPSOL_SOCIAL_STREAMS_PATH . 'lib/profiles.php';
require_once APPSOL_SOCIAL_STREAMS_PATH . 'lib/items.php';
require_once APPSOL_SOCIAL_STREAMS_PATH . 'lib/profiles.php';
require_once APPSOL_SOCIAL_STREAMS_PATH . 'lib/gallery_posttype.php';

class appsolSocialStreams {

    public static $messages;
    public static $errors;
    public static $profile_lifespan;
    public static $item_lifespan;

    public static function activate() {
        
    }

    public static function deactivate() {
        
    }

    public static function uninstall() {
        // important: check if the file is the one that was registered with the uninstall hook (function)
        if (__FILE__ != WP_UNINSTALL_PLUGIN)
            return;
    }

    /**
     * Initialisation point for the Plugin
     */
    public static function init() {
        // set the cache lifespans
        self::$item_lifespan = 60 * 60 * 24;
        self::$profile_lifespan = self::$item_lifespan * 7;
        
        // Register the activate / deactivate / uninstall hooks
        register_deactivation_hook(__FILE__, array('appsolSocialStreams', 'deactivate'));
        register_activation_hook(__FILE__, array('appsolSocialStreams', 'activate'));
        register_uninstall_hook(__FILE__, array('appsolSocialStreams', 'uninstall'));
        // Reset the message arrays
        self::$errors = array();
        self::$messages = array();
        // If this is an Authentication request then we make this as simple as possible
        if (isset($_REQUEST['get_auth']))
            self::request_auth();
        if (isset($_REQUEST['refresh_profiles']))
            self::refresh_profiles();
        if (isset($_REQUEST['refresh_items']))
            self::refresh_items();
        // Add the general actions
//        add_action('init', array('appsolSocialStreams', 'create_post_types'));
//        add_action('init', array('appsolSocialStreams', 'create_taxonomies'));
        add_action('widgets_init', array('appsolSocialStreams', 'load_widgets'));
        // Complete further initialisation based on context
        if (is_admin())
            self::admin_init();
        else
            self::public_init();
    }

    public static function admin_init() {
        add_action('admin_notices', array('appsolSocialStreams', 'show_admin_messages'));
        add_action('admin_menu', array('appsolSocialStreams', 'create_menu'));
        add_action('admin_init', array('appsolSocialStreams', 'register_settings'));
        add_action('wp_ajax_update_social_streams_posts', array('appsolSocialStreams', 'update_items'));
        add_action('wp_ajax_nopriv_update_social_streams_posts', array('appsolSocialStreams', 'update_items'));
        add_action('wp_ajax_update_social_streams_profiles', array('appsolSocialStreams', 'update_profiles'));
        add_action('wp_ajax_nopriv_update_social_streams_profiles', array('appsolSocialStreams', 'update_profiles'));
        add_action('wp_ajax_update_social_streams_connections', array('appsolSocialStreams', 'update_connections'));
        add_action('wp_ajax_nopriv_update_social_streams_connections', array('appsolSocialStreams', 'update_connections'));
        add_action('wp_ajax_update_social_streams_galleries', 'appsol_update_social_streams_galleries');
        add_action('wp_ajax_nopriv_update_social_streams_galleries', 'appsol_update_social_streams_galleries');
        add_action('wp_ajax_appsol_social_streams_users', array('appsolSocialStreams', 'users_ajax'));
        add_action('wp_ajax_nopriv_appsol_social_streams_users', array('appsolSocialStreams', 'users_ajax'));
        add_action('wp_ajax_appsol_social_streams_cache', array('appsolSocialStreams', 'cache_ajax'));
        add_action('wp_ajax_noprive_appsol_social_streams_cache', array('appsolSocialStreams', 'cache_ajax'));
        add_action('appsol_social_streams_get_auth', array('appsolSocialStreams', 'request_auth'));
        add_action('admin_enqueue_scripts', array('appsolSocialStreams', 'admin_resources'));
    }

    public static function public_init() {
        add_action('init', array('appsolSocialStreams', 'set_style'));
        add_action('wp_enqueue_scripts', array('appsolSocialStreams', 'set_script'));
    }

    public static function create_post_types() {
        /**
         * Default Arguments for Profiles and Items 
         */
        $args = array(
            'public' => false,
            'capability_type' => 'post',
            'capabilities' => array(
                'edit_post' => false,
                'edit_posts' => false,
                'read_post' => true,
                'delete_post' => true,
                'publish_posts' => false,
                'read_private_posts' => true
            ),
            'has_archive' => true,
            'taxonomies' => array('social_network'),
            'rewrite' => false,
            'query_var' => false,
            'can_export' => false
        );
        /**
         * Profiles 
         */
        $labels = array(
            'name' => _x('Profiles', 'post type general name'),
            'singular_name' => _x('Profile', 'post type singular name')
        );
        $args['labels'] = $labels;
        register_post_type('social_profile', $args);
        /**
         * Items 
         */
        $labels = array(
            'name' => _x('Items', 'post type general name'),
            'singular_name' => _x('Item', 'post type singular name')
        );
        $args['labels'] = $labels;
        register_post_type('social_item', $args);
        /**
         * Galleries
         */
        $labels = array(
            'name' => _x('Galleries', 'post type general name'),
            'singular_name' => _x('Gallery', 'post type singular name'),
            'add_new' => _x('Add New', 'appsol_social_streams_gallery'),
            'add_new_item' => __('Add New Gallery'),
            'edit_item' => __('Edit Gallery'),
            'new_item' => __('New Gallery'),
            'all_items' => __('All Galleries'),
            'view_item' => __('View Gallery'),
            'search_items' => __('Search Galleries'),
            'not_found' => __('No Galleries found'),
            'not_found_in_trash' => __('No Galleries found in Trash')
        );
        $args = array(
            'labels' => $labels,
            'public' => true,
            'publicly_queryable' => true,
            'exclude_from_search' => false,
            'show_ui' => true,
            'show_in_menu' => true,
            'menu_position' => 20,
            'query_var' => 'gallery',
            'rewrite' => array('slug' => 'social-gallery'),
            'capability_type' => 'post',
            'has_archive' => true,
            'hierarchical' => false,
//        'menu_position' => null,
            'supports' => array(
                'title', 'editor', 'author', 'thumbnail', 'excerpt', 'custom-fields'
            ),
            'taxonomies' => array('category', 'post_tag', 'social_network')
        );
        register_post_type('social_gallery', $args);
    }

    public static function create_taxonomies() {
        $post_types = array(
            'social_profile',
            'social_item',
            'social_gallery'
        );
        $labels = array(
            'name' => _x('Social Networks', 'taxonomy general name'),
            'singular_name' => _x('Social Network', 'taxonomy singular name')
        );
        $args = array(
            'labels' => $labels,
            'public' => false,
            'query_var' => 'network',
            'rewrite' => false
        );
        register_taxonomy('social_network', $post_types, $args);
    }

    public static function load_widgets() {
        register_widget('appsolSocialStreamsWidget');
    }

    public static function create_menu() {
        $page = add_plugins_page('Social Streams Settings', 'Social Streams', 'manage_options', 'appsol_social_streams', 'appsol_social_streams_options');
//    add_action('admin_print_styles-' . $page, 'appsol_social_streams_admin_resources');
    }

    public static function register_settings() {
        // Add the Social Streams Settings sections
        add_settings_section('social_streams_general', 'General Settings', 'appsol_social_streams_general_section', 'appsol_social_streams');
        add_settings_section('social_streams_facebook', 'Facebook Settings', 'appsol_social_streams_facebook_section', 'appsol_social_streams');
        add_settings_section('social_streams_twitter', 'Twitter Settings', 'appsol_social_streams_twitter_section', 'appsol_social_streams');
        add_settings_section('social_streams_linkedin', 'LinkedIn Settings', 'appsol_social_streams_linkedin_section', 'appsol_social_streams');
        add_settings_section('social_streams_foursquare', 'Foursquare Settings', 'appsol_social_streams_foursquare_section', 'appsol_social_streams');
        add_settings_section('social_streams_google', 'Google+ Settings', 'appsol_social_streams_google_section', 'appsol_social_streams');
        add_settings_section('social_streams_instagram', 'Instagram Settings', 'appsol_social_streams_instagram_section', 'appsol_social_streams');
        add_settings_section('social_streams_flickr', 'Flickr Settings', 'appsol_social_streams_flickr_section', 'appsol_social_streams');
        // General Settings
        self::general_settings();
        // Facebook Settings
        self::facebook_settings();
        // Twitter Settings
        self::twitter_settings();
        // LinkedIn Settings
        self::linkedin_settings();
        // Google Plus Settings
        self::google_settings();
        // Instagram Settings
        self::instagram_settings();
        // Foursquare Settings
        self::foursquare_settings();
        // Flickr Settings
        self::flickr_settings();
        // Add the admin stylesheets
        if (is_admin())
            self::admin_resources();
    }

    public static function general_settings() {
        add_settings_field('social_streams_networks', 'Networks', 'appsol_social_streams_hidden_field', 'appsol_social_streams', 'social_streams_general', array('label_for' => 'social_streams_networks', 'default' => 'facebook,twitter,linkedin,google,instagram,flickr,foursquare', 'description' => ''));
        register_setting('appsol_social_streams', 'social_streams_networks');
        add_settings_field('social_streams_stored_connections', 'Stored Connections', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_general', array('label_for' => 'social_streams_stored_connections', 'default' => '20', 'description' => 'The number of connection profiles from each Social Network to store locally'));
        register_setting('appsol_social_streams', 'social_streams_stored_connections');
        add_settings_field('social_streams_stored_items', 'Stored Items', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_general', array('label_for' => 'social_streams_stored_items', 'default' => '20', 'description' => 'The number of update items from each Social Network to store locally'));
        register_setting('appsol_social_streams', 'social_streams_stored_items');
        add_settings_field('social_streams_item_period', 'Item Cache Refresh Time (secs)', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_general', array('label_for' => 'social_streams_item_period', 'default' => '3600', 'description' => 'The number of seconds before the Item Cache will be refreshed. Default is 1 hour, maximum is 1 24 hours'));
        register_setting('appsol_social_streams', 'social_streams_item_period');
        add_settings_field('social_streams_profile_period', 'Profile Cache Refresh Time (secs)', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_general', array('label_for' => 'social_streams_profile_period', 'default' => '86400', 'description' => 'The number of seconds before the Profile Cache will be refreshed. Default is 24 hours, maximum is 1 week'));
        register_setting('appsol_social_streams', 'social_streams_profile_period');
    }

    public static function facebook_settings() {
        add_settings_field('social_streams_facebook', 'Connect to Facebook', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_facebook', array('label_for' => 'social_streams_facebook', 'default' => '0', 'description' => 'Use updates and information from Facebook'));
        register_setting('appsol_social_streams', 'social_streams_facebook');
        add_settings_field('social_streams_facebook_appkey', 'Facebook App Key', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_facebook', array('label_for' => 'social_streams_facebook_appkey', 'default' => '', 'description' => 'The App ID or AppKey taken from https://developers.facebook.com/apps'));
        register_setting('appsol_social_streams', 'social_streams_facebook_appkey');
        add_settings_field('social_streams_facebook_appsecret', 'Facebook App Secret', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_facebook', array('label_for' => 'social_streams_facebook_appsecret', 'default' => '', 'description' => 'The App Secret taken from https://developers.facebook.com/apps'));
        register_setting('appsol_social_streams', 'social_streams_facebook_appsecret');
    }

    public static function twitter_settings() {
        add_settings_field('social_streams_twitter', 'Connect to Twitter', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_twitter', array('label_for' => 'social_streams_twitter', 'default' => '0', 'description' => 'Use updates and information from Twitter'));
        register_setting('appsol_social_streams', 'social_streams_twitter');
        add_settings_field('social_streams_twitter_appkey', 'Twitter App Key', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_twitter', array('label_for' => 'social_streams_twitter_appkey', 'default' => '', 'description' => 'The App ID or AppKey taken from https://dev.twitter.com/apps'));
        register_setting('appsol_social_streams', 'social_streams_twitter_appkey');
        add_settings_field('social_streams_twitter_appsecret', 'Twitter App Secret', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_twitter', array('label_for' => 'social_streams_twitter_appsecret', 'default' => '', 'description' => 'The App Secret taken from https://dev.twitter.com/apps'));
        register_setting('appsol_social_streams', 'social_streams_twitter_appsecret');
        add_settings_field('social_streams_only_friends', 'Only Friends', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_twitter', array('label_for' => 'social_streams_only_friends', 'default' => '0', 'description' => 'Only show followers on Twitter who are followed back'));
        register_setting('appsol_social_streams', 'social_streams_only_friends');
        add_settings_field('social_streams_show_blocked', 'Show Blocked', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_twitter', array('label_for' => 'social_streams_show_blocked', 'default' => '0', 'description' => 'Show Twitter Followers which have been blocked by the User on Twitter'));
        register_setting('appsol_social_streams', 'social_streams_show_blocked');
        add_settings_field('social_streams_trim_user', 'Trim Users', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_twitter', array('label_for' => 'social_streams_trim_user', 'default' => '0', 'description' => 'Only get basic details for each tweet author'));
        register_setting('appsol_social_streams', 'social_streams_trim_user');
        add_settings_field('social_streams_include_retweets', 'Show Retweets', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_twitter', array('label_for' => 'social_streams_include_retweets', 'default' => '0', 'description' => 'Get native re-tweets if they exist'));
        register_setting('appsol_social_streams', 'social_streams_include_retweets');
        add_settings_field('social_streams_exclude_replies', 'Exclude Replies', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_twitter', array('label_for' => 'social_streams_exclude_replies', 'default' => '0', 'description' => 'Filter out reply tweets, may reduce the number of tweets shown as filtering occurs after tweets are retrieved'));
        register_setting('appsol_social_streams', 'social_streams_exclude_replies');
        add_settings_field('social_streams_include_entities', 'Include Entities', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_twitter', array('label_for' => 'social_streams_include_entities', 'default' => '1', 'description' => 'Get additional meta data about each tweet such as user mentions, hashtags and urls'));
        register_setting('appsol_social_streams', 'social_streams_include_entities');
    }

    public static function linkedin_settings() {
        add_settings_field('social_streams_linkedin', 'Connect to Linkedin', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_linkedin', array('label_for' => 'social_streams_linkedin', 'default' => '0', 'description' => 'Use updates and information from LinkedIn'));
        register_setting('appsol_social_streams', 'social_streams_linkedin');
        add_settings_field('social_streams_linkedin_appkey', 'LinkedIn App Key', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_linkedin', array('label_for' => 'social_streams_linkedin_appkey', 'default' => '', 'description' => 'The App ID or AppKey taken from https://www.linkedin.com/secure/developer'));
        register_setting('appsol_social_streams', 'social_streams_linkedin_appkey');
        add_settings_field('social_streams_linkedin_appsecret', 'LinkedIn App Secret', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_linkedin', array('label_for' => 'social_streams_linkedin_appsecret', 'default' => '', 'description' => 'The App Secret taken from https://www.linkedin.com/secure/developer'));
        register_setting('appsol_social_streams', 'social_streams_linkedin_appsecret');
        add_settings_field('social_streams_linkedin_itemtype_shar', 'Share Updates', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_linkedin', array('label_for' => 'social_streams_linkedin_itemtype_shar', 'default' => '0', 'description' => 'LinkedIn Share updates are generated when a member shares or reshares an item'));
        register_setting('appsol_social_streams', 'social_streams_linkedin_itemtype_shar');
        add_settings_field('social_streams_linkedin_itemtype_stat', 'Status Updates', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_linkedin', array('label_for' => 'social_streams_linkedin_itemtype_stat', 'default' => '0', 'description' => 'LinkedIn Status Updates are the result of first degree connections setting their status'));
        register_setting('appsol_social_streams', 'social_streams_linkedin_itemtype_stat');
        add_settings_field('social_streams_linkedin_itemtype_virl', 'Viral Updates', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_linkedin', array('label_for' => 'social_streams_linkedin_itemtype_virl', 'default' => '0', 'description' => 'LinkedIn Viral updates include comments and likes'));
        register_setting('appsol_social_streams', 'social_streams_linkedin_itemtype_virl');
        add_settings_field('social_streams_linkedin_itemtype_conn', 'Connection Updates', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_linkedin', array('label_for' => 'social_streams_linkedin_itemtype_conn', 'default' => '0', 'description' => 'LinkedIn Connection Updates usually describe when a connection of the current member has made a new connection'));
        register_setting('appsol_social_streams', 'social_streams_linkedin_itemtype_conn');
    }

    public static function google_settings() {
        add_settings_field('social_streams_google', 'Connect to Google+', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_google', array('label_for' => 'social_streams_google', 'default' => '0', 'description' => 'Use updates and information from Google+'));
        register_setting('appsol_social_streams', 'social_streams_google');
        add_settings_field('social_streams_google_appkey', 'Google App Key', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_google', array('label_for' => 'social_streams_google_appkey', 'default' => '', 'description' => 'The App ID or AppKey taken from https://code.google.com/apis/console'));
        register_setting('appsol_social_streams', 'social_streams_google_appkey');
        add_settings_field('social_streams_google_appsecret', 'Google App Secret', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_google', array('label_for' => 'social_streams_google_appsecret', 'default' => '', 'description' => 'The App Secret taken from https://code.google.com/apis/console'));
        register_setting('appsol_social_streams', 'social_streams_google_appsecret');
        add_settings_field('social_streams_google_appid', 'Google Client ID', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_google', array('label_for' => 'social_streams_google_appid', 'default' => '', 'description' => 'The Client ID taken from https://code.google.com/apis/console'));
        register_setting('appsol_social_streams', 'social_streams_google_appid');
    }

    public static function instagram_settings() {
        add_settings_field('social_streams_instagram', 'Connect to Instagram', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_instagram', array('label_for' => 'social_streams_instagram', 'default' => '0', 'description' => 'Use updates and information from Instagram'));
        register_setting('appsol_social_streams', 'social_streams_instagram');
        add_settings_field('social_streams_instagram_appkey', 'Instagram App Key', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_instagram', array('label_for' => 'social_streams_instagram_appkey', 'default' => '', 'description' => 'The API Key taken from http://instagram.com/developer/clients/manage/'));
        register_setting('appsol_social_streams', 'social_streams_instagram_appkey');
        add_settings_field('social_streams_instagram_appsecret', 'Instagram App Secret', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_instagram', array('label_for' => 'social_streams_instagram_appsecret', 'default' => '', 'description' => 'The Client Secret taken from http://instagram.com/developer/clients/manage/'));
        register_setting('appsol_social_streams', 'social_streams_instagram_appsecret');
    }

    public static function foursquare_settings() {
        add_settings_field('social_streams_foursquare', 'Connect to Foursquare', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_foursquare', array('label_for' => 'social_streams_foursquare', 'default' => '0', 'description' => 'Use updates and information from Foursquare'));
        register_setting('appsol_social_streams', 'social_streams_foursquare');
        add_settings_field('social_streams_foursquare_appkey', 'Foursquare App Key', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_foursquare', array('label_for' => 'social_streams_foursquare_appkey', 'default' => '', 'description' => 'The API Key taken from https://foursquare.com/developers/apps'));
        register_setting('appsol_social_streams', 'social_streams_foursquare_appkey');
        add_settings_field('social_streams_foursquare_appsecret', 'Foursquare App Secret', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_foursquare', array('label_for' => 'social_streams_foursquare_appsecret', 'default' => '', 'description' => 'The Client Secret taken from https://foursquare.com/developers/apps'));
        register_setting('appsol_social_streams', 'social_streams_foursquare_appsecret');
    }

    public static function flickr_settings() {
        add_settings_field('social_streams_flickr', 'Connect to Flickr', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_flickr', array('label_for' => 'social_streams_flickr', 'default' => '0', 'description' => 'Use updates and information from Flickr'));
        register_setting('appsol_social_streams', 'social_streams_flickr');
        add_settings_field('social_streams_flickr_appkey', 'Flickr App Key', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_flickr', array('label_for' => 'social_streams_flickr_appkey', 'default' => '', 'description' => 'The API Key taken from http://www.flickr.com/services/'));
        register_setting('appsol_social_streams', 'social_streams_flickr_appkey');
        add_settings_field('social_streams_flickr_appsecret', 'Flickr App Secret', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_flickr', array('label_for' => 'social_streams_flickr_appsecret', 'default' => '', 'description' => 'The Client Secret taken from http://www.flickr.com/services/'));
        register_setting('appsol_social_streams', 'social_streams_flickr_appsecret');
    }

    public static function admin_resources() {
        wp_register_style('appsol_social_streams_admin_css', plugins_url('css/admin.css', __FILE__));
        wp_register_script('appsol_social_streams_admin_js', plugins_url('js/social_streams_admin.js', __FILE__), array('jquery', 'jquery-ui-dialog'));
        wp_enqueue_style('appsol_social_streams_admin_css');
        wp_enqueue_script('appsol_social_streams_admin_js');
    }

    public static function set_style() {
        wp_register_style('social-streams', plugins_url('css/default.css', __FILE__));
        wp_enqueue_style('social-streams');
    }

    public static function set_script() {
        global $post;
        wp_enqueue_script('appsol_social_streams_ajax', plugins_url('js/social_streams.js', __FILE__), array('jquery'), '', true);
        wp_localize_script('appsol_social_streams_ajax', 'appsolSocialStreams', array('ajaxurl' => admin_url('admin-ajax.php'), 'pluginurl' => plugin_dir_url(__FILE__), 'postid' => $post->ID));
    }

    public static function request_auth() {
        if (!empty($_REQUEST['network'])) {
            $network = $_REQUEST['network'];
            $networks = SocialStreamsHelper::getNetworks();
            SocialStreamsHelper::log($_REQUEST);
            if (in_array($network, $networks)) {

                $api = SocialStreamsHelper::getApi($network);
                SocialStreamsHelper::log($api);
                if (!empty($api->access_token)) {
                    $message = 'User authenticated on Network ' . $network;
                    self::add_message($message);
                } else {
                    $message = 'Authenticate failed on Network ' . $network . ' : ' . ($api? $api->error : '');
                    self::add_message($message, true);
                }
//                header('Location: ' . SocialStreamsHelper::getBaseRedirectUrl() . '&tab=accounts');
            }
        } else {
            header('Location: ' . SocialStreamsHelper::getBaseRedirectUrl() . '&tab=accounts&message=empty_network');
            SocialStreamsHelper::shutdown();
        }
    }

    public static function remove_auth($network, $client_id) {
        SocialStreamsItemCache::deleteClientItems($network, $client_id);
        SocialStreamsProfileCache::deleteClientProfiles($network, $client_id, true);
        SocialStreamsHelper::removeClientId($network, $client_id);
    }

    public static function refresh_profiles() {
        $force = isset($_REQUEST['force']) && $_REQUEST['force'] ? true : false;
        SocialStreamsProfileCache::refresh($force);
    }

    public static function refresh_items() {
        $force = isset($_REQUEST['force']) && $_REQUEST['force'] ? true : false;
        SocialStreamsItemCache::refresh($force);
    }

    public static function update_profiles() {
        global $appsol_social_streams_caches;
        $widget_id = $_POST['widgetid'];
        $instance_index = end(explode('-', $widget_id));
        $widget_instances = get_option('widget_appsol-social-streams-widget');
        $profiles = array();
        foreach ($appsol_social_streams_caches['profile'] as $network => $profile_caches) {
            foreach ($profile_caches as $profile_cache) {
                $cache = new $profile_cache($widget_instances[$instance_index]);
                if (!$cache->cache)
                    if ($cache->updateCache()) {
                        $updated = true;
                        $profiles[$widget_instances[$instance_index]->$network . '_user_id'] = $cache->cache;
                    }
            }
        }
        header("Content-Type: application/json");
        echo json_encode(array('profiles' => $profiles));
        die();
    }

    public static function update_connections() {
        global $appsol_social_streams_caches;
        $widget_id = $_POST['widgetid'];
        $instance_index = end(explode('-', $widget_id));
        $widget_instances = get_option('widget_appsol-social-streams-widget');
        $updated = false;
        foreach ($appsol_social_streams_caches['connection'] as $network => $connection_caches) {
            foreach ($connection_caches as $connection_cache) {
                $cache = new $connection_cache($widget_instances[$instance_index]);
                if (!$cache->cache) {
                    if ($cache->updateCache())
                        $updated = true;
                }
            }
        }
        if ($updated) {
            $connections = appsolSocialStreamsWidget::connections($widget_instances[$instance_index]);
            foreach ($connections as $connection)
                echo $connection->display();
        }
        die();
    }

    public static function update_items() {
        $widget_id = $_POST['widgetid'];
        SocialStreamsHelper::log('Widget: ' . $widget_id);
        $instance_index = end(explode('-', $widget_id));
        $widget_instances = get_option('widget_appsol-social-streams-widget');
        SocialStreamsHelper::log($widget_instances);
        $accounts = SocialStreamsHelper::getAuthenticatedAccounts();
        $updated = false;
        foreach ($accounts as $account)
            if (!empty($widget_instances[$instance_index][$account['network'] . '_' . $account['clientid']]))
                $updated = SocialStreamsItemCache::refresh(false, $account['network']);
//        foreach ($appsol_social_streams_caches['stream'] as $network => $stream_caches) {
//            foreach ($stream_caches as $stream_cache) {
//                $cache = new $stream_cache($widget_instances[$instance_index]);
//                if (!$cache->cache) {
//                    if ($cache->updateCache())
//                        $updated = true;
//                }
//            }
//        }
        if ($updated) {
            $stream = appsolSocialStreamsWidget::stream($widget_instances[$instance_index]);
            foreach ($stream as $stream_item) {
                echo $stream_item->display();
            }
        }
        die();
    }

    public static function show_admin_messages() {
        // Messages
        if (count(self::$messages))
            foreach (self::$messages as $message)
                self::show_message($message);
        // Errors
        if (count(self::$errors))
            foreach (self::$errors as $error)
                self::show_message($error, true);
    }

    public static function show_message($message, $error = false) {
        echo $error ? '<div id="message" class="error">' : '<div id="message" class="updated fade">';

        echo "<p><strong>$message</strong></p></div>";
    }

    public static function add_message($message, $error = false) {
        if ($error)
            self::$errors[] = $message;
        else
            self::$messages[] = $message;
    }

    public static function clear_messages($errors = false) {
        if ($errors)
            self::$errors = array();
        else
            self::$messages = array();
    }

}

//
//function appsol_update_social_streams_galleries() {
////    global $appsol_social_streams_caches;
//    _log('appsol_update_social_streams_galleries');
//    $networks = array(
//        'fb' => 'facebook'
//    );
//    $user_id = $_POST['user'];
//    $network_id = $_POST['network'];
//    $gallery = isset($_POST['gallery']) ? $_POST['gallery'] : null;
//    _log('Network: ' . $network_id . ' Gallery: ' . $gallery . ' User: ' . $user_id);
//    $updated = false;
//    foreach ($networks as $network => $network_name) {
//        if ($network == $network_id) {
//            $params = array($network_id . '_user_id' => $user_id);
//            $gallery_cache = 'appsol' . ucfirst($networks[$network_id]) . 'AlbumsCache';
//            $cache = new $gallery_cache($params);
//            if (!$cache->cache) {
//                if ($cache->updateCache())
//                    $updated = true;
//            }
//        }
//    }
//    if ($updated && $gallery) {
//        $gallery_cache = 'appsol' . ucfirst($networks[$network_id]) . 'AlbumCache';
//        $params = array(
//            $network_id . '_user_id' => $user_id,
//            $params[$network_id . '_album_id'] => $gallery
//        );
//        $album = new $gallery_cache($params);
//        _log($album);
//        if (!$album->cache) {
//            if ($album->updateCache())
//                $updated = true;
//        }
//        $html = '';
////        foreach ($cache->cache as $album)
////            if ($album->id == $gallery) {
//        $html.= $album->cache->message;
//        foreach ($album->cache->images as $image)
//            $image_html.= $image->message;
//        $html = str_replace('[GALLERY]', $image_html, $html);
////            }
//        echo $html;
//    }
//    die();
//}
//
//function appsol_social_streams_users_ajax() {
//
//    $network = $_POST['network'];
//    $network_users = get_option('appsol_social_streams_' . $network . '_users');
//
//    header("Content-Type: application/json");
//    echo json_encode(array('network' => $network, 'users' => $network_users));
//    die();
//}
//
//function appsol_social_streams_galleries_ajax() {
//    $network = $_POST['network'];
//    $user = $_POST['user'];
//    $cache = new appsolFacebookAlbumCache(array('fb_user_id' => $user));
//    header("Content-Type: application/json");
//    echo json_encode(array('network' => $network, 'user' => $user, 'cache' => $cache->cache));
//    die();
//}
//
//function appsol_social_streams_cache_ajax() {
//    $network = $_POST['network'];
//    $cachetype = $_POST['cache'];
//    $user = $_POST['user'];
//    $update = isset($_POST['update']) ? $_POST['update'] : false;
//    _log('Network: ' . $network . ' Cache: ' . $cachetype . ' User: ' . $user . ' Update: ' . $update);
//    $network_name = str_replace(
//            array('fb', 'tw', 'li', 'gp'), array('facebook', 'twitter', 'linkedin', 'googleplus'), $network
//    );
//    $class = 'appsol' . ucfirst($network_name) . ucfirst($cachetype) . 'Cache';
//    $updated = false;
//    if (class_exists($class)) {
//        $params = array($network . '_user_id' => $user);
//        if ($cachetype = 'album' && isset($_POST['album']))
//            $params[$network . '_album_id'] = $_POST['album'];
//        $cache = new $class($params);
//        if (!$cache->cache && $update) {
//            if (!$cache->updateCache())
//                $updated = true;
//        }
//    }
//    header("Content-Type: application/json");
//    echo json_encode(array('name' => ucfirst($network_name) . ucfirst($cachetype), 'cache' => $cache->cache));
//    die();
//}
//
//function appsol_get_active_social_streams_widgets() {
//    $social_streams_widgets = array();
//    $all_widgets = wp_get_sidebars_widgets();
//    foreach ($all_widgets as $sidebar => $widgets)
//        if ($sidebar != 'wp_inactive_widgets')
//            foreach ($widgets as $widget)
//                if (strpos($widget, 'appsol-social-streams-widget') !== false)
//                    $social_streams_widgets[] = $widget;
//    return $social_streams_widgets;
//}
//
//}

if (is_admin())
    require_once 'appsol_social_streams_options.php';

appsolSocialStreams::init();
