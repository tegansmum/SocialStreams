<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

function appsol_social_streams_create_menu() {
    $page = add_plugins_page('Social Streams Settings', 'Social Streams', 'manage_options', 'appsol_social_streams', 'appsol_social_streams_options');
//    add_action('admin_print_styles-' . $page, 'appsol_social_streams_admin_resources');
}

function appsol_social_streams_register_settings() {
    // Add the Social Straems Settings sections
    add_settings_section('social_streams_general', 'General Settings', 'appsol_social_streams_general', 'appsol_social_streams');
    add_settings_section('social_streams_facebook', 'Facebook Settings', 'appsol_social_streams_facebook', 'appsol_social_streams');
    add_settings_section('social_streams_twitter', 'Twitter Settings', 'appsol_social_streams_twitter', 'appsol_social_streams');
    add_settings_section('social_streams_linkedin', 'LinkedIn Settings', 'appsol_social_streams_linkedin', 'appsol_social_streams');
    add_settings_section('social_streams_google', 'Google+ Settings', 'appsol_social_streams_google', 'appsol_social_streams');
    add_settings_section('social_streams_instagram', 'Instagram Settings', 'appsol_social_streams_instagram', 'appsol_social_streams');
    // General Settings
    add_settings_field('socialstreams_networks', 'Networks', 'appsol_social_streams_hidden_field', 'appsol_social_streams', 'social_streams_general', array('label_for' => 'socialstreams_networks', 'default' => 'facebook,twitter,linkedin,google,instagram,flickr,foursquare', 'description' => ''));
    register_setting('social_streams_general', 'socialstreams_networks');
    add_settings_field('social_streams_stored_connections', 'Stored Connections', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_general', array('label_for' => 'social_streams_stored_connections', 'default' => '20', 'description' => 'The number of connection profiles from each Social Network to store locally'));
    register_setting('social_streams_general', 'social_streams_stored_connections');
    add_settings_field('social_streams_stored_items', 'Stored Items', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_general', array('label_for' => 'social_streams_stored_items', 'default' => '20', 'description' => 'The number of update items from each Social Network to store locally'));
    register_setting('social_streams_twitter', 'social_streams_stored_items');
    add_settings_field('social_streams_item_period', 'Item Cache Refresh Time (secs)', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_general', array('label_for' => 'social_streams_item_period', 'default' => '3600', 'description' => 'The number of seconds before the Item Cache will be refreshed. Default is 1 hour'));
    register_setting('social_streams_general', 'social_streams_item_period');
    add_settings_field('social_streams_profile_period', 'Profile Cache Refresh Time (secs)', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_general', array('label_for' => 'social_streams_profile_period', 'default' => '86400', 'description' => 'The number of seconds before the Profile Cache will be refreshed. Default is 24 hours'));
    register_setting('social_streams_general', 'social_streams_profile_period');
    // Facebook Settings
    add_settings_field('social_streams_facebook', 'Connect to Facebook', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_facebook', array('label_for' => 'social_streams_facebook', 'default' => '0', 'description' => 'Use updates and information from Facebook'));
    register_setting('social_streams_facebook', 'social_streams_facebook');
    add_settings_field('social_streams_facebook_appkey', 'Facebook App Key', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_facebook', array('label_for' => 'social_streams_facebook_appkey', 'default' => '', 'description' => 'The App ID or AppKey taken from https://developers.facebook.com/apps'));
    register_setting('social_streams_facebook', 'social_streams_facebook_appkey');
    add_settings_field('social_streams_facebook_appsecret', 'Facebook App Secret', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_facebook', array('label_for' => 'social_streams_facebook_appsecret', 'default' => '', 'description' => 'The App Secret taken from https://developers.facebook.com/apps'));
    register_setting('social_streams_facebook', 'social_streams_facebook_appsecret');
    // Twitter Settings
    add_settings_field('social_streams_twitter', 'Connect to Twitter', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_twitter', array('label_for' => 'social_streams_twitter', 'default' => '0', 'description' => 'Use updates and information from Twitter'));
    register_setting('social_streams_twitter', 'social_streams_twitter');
    add_settings_field('social_streams_twitter_appkey', 'Twitter App Key', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_twitter', array('label_for' => 'social_streams_twitter_appkey', 'default' => '', 'description' => 'The App ID or AppKey taken from https://dev.twitter.com/apps'));
    register_setting('social_streams_twitter', 'social_streams_twitter_appkey');
    add_settings_field('social_streams_twitter_appsecret', 'Twitter App Secret', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_twitter', array('label_for' => 'social_streams_twitter_appsecret', 'default' => '', 'description' => 'The App Secret taken from https://dev.twitter.com/apps'));
    register_setting('social_streams_twitter', 'social_streams_twitter_appsecret');
    add_settings_field('social_streams_only_friends', '', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_twitter', array('label_for' => 'social_streams_only_friends', 'default' => '0', 'description' => 'Only show followers on Twitter who are followed back'));
    register_setting('social_streams_twitter', 'social_streams_only_friends');
    add_settings_field('social_streams_show_blocked', '', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_twitter', array('label_for' => 'social_streams_show_blocked', 'default' => '0', 'description' => 'Show Twitter Followers which have been blocked by the User on Twitter'));
    register_setting('social_streams_twitter', 'social_streams_show_blocked');
    add_settings_field('social_streams_trim_user', '', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_twitter', array('label_for' => 'social_streams_trim_user', 'default' => '0', 'description' => 'Only get basic details for each tweet author'));
    register_setting('social_streams_twitter', 'social_streams_trim_user');
    add_settings_field('social_streams_include_retweets', '', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_twitter', array('label_for' => 'social_streams_include_retweets', 'default' => '0', 'description' => 'Get native re-tweets if they exist'));
    register_setting('social_streams_twitter', 'social_streams_include_retweets');
    add_settings_field('social_streams_exclude_replies', '', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_twitter', array('label_for' => 'social_streams_exclude_replies', 'default' => '0', 'description' => 'Filter out reply tweets, may reduce the number of tweets shown as filtering occurs after tweets are retrieved'));
    register_setting('social_streams_twitter', 'social_streams_exclude_replies');
    add_settings_field('social_streams_include_entities', '', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_twitter', array('label_for' => 'social_streams_include_entities', 'default' => '1', 'description' => 'Get additional meta data about each tweet such as user mentions, hashtags and urls'));
    register_setting('social_streams_twitter', 'social_streams_include_entities');
    // LinkedIn Settings
    add_settings_field('social_streams_linkedin', 'Connect to Linkedin', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_linkedin', array('label_for' => 'social_streams_linkedin', 'default' => '0', 'description' => 'Use updates and information from LinkedIn'));
    register_setting('social_streams_linkedin', 'social_streams_linkedin');
    add_settings_field('social_streams_linkedin_appkey', 'LinkedIn App Key', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_linkedin', array('label_for' => 'social_streams_linkedin_appkey', 'default' => '', 'description' => 'The App ID or AppKey taken from https://www.linkedin.com/secure/developer'));
    register_setting('social_streams_linkedin', 'social_streams_linkedin_appkey');
    add_settings_field('social_streams_linkedin_appsecret', 'LinkedIn App Secret', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_linkedin', array('label_for' => 'social_streams_linkedin_appsecret', 'default' => '', 'description' => 'The App Secret taken from https://www.linkedin.com/secure/developer'));
    register_setting('social_streams_linkedin', 'social_streams_linkedin_appsecret');
    add_settings_field('social_streams_linkedin_itemtype_shar', '', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_linkedin', array('label_for' => 'social_streams_linkedin_itemtype_shar', 'default' => '0', 'description' => 'LinkedIn Share updates are generated when a member shares or reshares an item'));
    register_setting('social_streams_linkedin', 'social_streams_linkedin_itemtype_shar');
    add_settings_field('social_streams_linkedin_itemtype_stat', '', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_linkedin', array('label_for' => 'social_streams_linkedin_itemtype_stat', 'default' => '0', 'description' => 'LinkedIn Status Updates are the result of first degree connections setting their status'));
    register_setting('social_streams_linkedin', 'social_streams_linkedin_itemtype_stat');
    add_settings_field('social_streams_linkedin_itemtype_virl', '', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_linkedin', array('label_for' => 'social_streams_linkedin_itemtype_virl', 'default' => '0', 'description' => 'LinkedIn Viral updates include comments and likes'));
    register_setting('social_streams_linkedin', 'social_streams_linkedin_itemtype_virl');
    add_settings_field('social_streams_linkedin_itemtype_conn', '', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_linkedin', array('label_for' => 'social_streams_linkedin_itemtype_conn', 'default' => '0', 'description' => 'LinkedIn Connection Updates usually describe when a connection of the current member has made a new connection'));
    register_setting('social_streams_linkedin', 'social_streams_linkedin_itemtype_conn');
    // Google Plus Settings
//    add_settings_field('social_streams_google', 'Connect to Google+', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_google', array('label_for' => 'social_streams_google', 'default' => '0', 'description' => 'Use updates and information from Google+'));
//    register_setting('social_streams_google', 'social_streams_google');
//    add_settings_field('social_streams_google_appkey', 'Google App Key', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_google', array('label_for' => 'social_streams_google_appkey', 'default' => '', 'description' => 'The App ID or AppKey taken from https://code.google.com/apis/console'));
//    register_setting('social_streams_google', 'social_streams_google_appkey');
//    add_settings_field('social_streams_linkedin_appsecret', 'Google App Secret', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_google', array('label_for' => 'social_streams_linkedin_appsecret', 'default' => '', 'description' => 'The App Secret taken from https://code.google.com/apis/console'));
//    register_setting('social_streams_google', 'social_streams_google_appsecret');
//    add_settings_field('social_streams_google_appid', 'Google Client ID', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_google', array('label_for' => 'social_streams_google_appid', 'default' => '', 'description' => 'The Client ID taken from https://code.google.com/apis/console'));
//    register_setting('social_streams_google', 'social_streams_google_appid');
    // Instagram Settings
    add_settings_field('social_streams_instagram', 'Connect to Instagram', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_instagram', array('label_for' => 'social_streams_instagram', 'default' => '0', 'description' => 'Use updates and information from Instagram'));
    register_setting('social_streams_instagram', 'social_streams_instagram');
    add_settings_field('social_streams_instagram_appkey', 'Instagram App Key', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_instagram', array('label_for' => 'social_streams_instagram_appkey', 'default' => '', 'description' => 'The API Key taken from http://instagram.com/developer/clients/manage/'));
    register_setting('social_streams_instagram', 'social_streams_instagram_appkey');
    add_settings_field('social_streams_instagram_appsecret', 'Instagram App Secret', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_instagram', array('label_for' => 'social_streams_instagram_appsecret', 'default' => '', 'description' => 'The Client Secret taken from http://instagram.com/developer/clients/manage/'));
    register_setting('social_streams_instagram', 'social_streams_instagram_appsecret');
    // Foursquare Settings
//    add_settings_field('social_streams_foursquare', 'Connect to Foursquare', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_foursquare', array('label_for' => 'social_streams_foursquare', 'default' => '0', 'description' => 'Use updates and information from Foursquare'));
//    register_setting('social_streams_foursquare', 'social_streams_foursquare');
//    add_settings_field('social_streams_foursquare_appkey', 'Foursquare App Key', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_foursquare', array('label_for' => 'social_streams_foursquare_appkey', 'default' => '', 'description' => 'The API Key taken from https://foursquare.com/developers/apps'));
//    register_setting('social_streams_foursquare', 'social_streams_foursquare_appkey');
//    add_settings_field('social_streams_foursquare_appsecret', 'Foursquare App Secret', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_foursquare', array('label_for' => 'social_streams_foursquare_appsecret', 'default' => '', 'description' => 'The Client Secret taken from https://foursquare.com/developers/apps'));
//    register_setting('social_streams_foursquare', 'social_streams_foursquare_appsecret');
    // Flickr Settings
//    add_settings_field('social_streams_flickr', 'Connect to Flickr', 'appsol_social_streams_yesno_field', 'appsol_social_streams', 'social_streams_flickr', array('label_for' => 'social_streams_flickr', 'default' => '0', 'description' => 'Use updates and information from Flickr'));
//    register_setting('social_streams_flickr', 'social_streams_flickr');
//    add_settings_field('social_streams_flickr_appkey', 'Flickr App Key', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_flickr', array('label_for' => 'social_streams_flickr_appkey', 'default' => '', 'description' => 'The API Key taken from http://www.flickr.com/services/'));
//    register_setting('social_streams_flickr', 'social_streams_flickr_appkey');
//    add_settings_field('social_streams_flickr_appsecret', 'Flickr App Secret', 'appsol_social_streams_text_field', 'appsol_social_streams', 'social_streams_flickr', array('label_for' => 'social_streams_flickr_appsecret', 'default' => '', 'description' => 'The Client Secret taken from http://www.flickr.com/services/'));
//    register_setting('social_streams_flickr', 'social_streams_flickr_appsecret');
    // Add the admin stylesheets
    wp_register_style('appsol_social_streams_admin_css', plugins_url('css/admin.css', __FILE__));
    wp_register_script('appsol_social_streams_admin_js', plugins_url('js/social_streams_admin.js', __FILE__), array('jquery', 'jquery-ui-dialog'));
    appsol_social_streams_admin_resources();
}

/**
 * Text Field
 * @param type $args 
 */
function appsol_social_streams_text_field($args) {
    $setting = $args['label_for'];
    $option = get_option($setting) ? get_option($setting) : $args['default'];
    echo '<input title="' . $args["description"] . '" type="text" id="' . $setting . '" name="' . $setting . '" value="' . $option . '" />';
}

/**
 * Hidden Field
 * @param type $args 
 */
function appsol_social_streams_hidden_field($args) {
    $setting = $args['label_for'];
    $option = get_option($setting) ? get_option($setting) : $args['default'];
    echo '<input type="hidden" id="' . $setting . '" name="' . $setting . '" value="' . $option . '" />';
}

/**
 * Yes / No radio fields
 * @param type $args 
 */
function appsol_social_streams_yesno_field($args) {
    $setting = $args['label_for'];
    $option = get_option($setting) ? get_option($setting) : $args['default'];
    echo '<input title="' . $args["description"] . '" type="radio" id="' . $setting . '_yes" name="' . $setting . '" value="1" ' . ($option ? 'checked="checked" ' : '') . '/>';
    echo '<input title="' . $args["description"] . '" type="radio" id="' . $setting . '_no" name="' . $setting . '" value="0" ' . ($option ? '' : 'checked="checked" ') . '/>';
}

/**
 * Show the configuration options for the available social networks 
 */
function appsol_social_streams_networks() {
    ?>
    <form method="post" action="options.php"> 
        <?php
        settings_fields('social_streams_general');
        $networks = get_option('socialstreams_networks');
        if ($networks) {
            $networks = explode(',', $networks);
            foreach ($networks as $network)
                settings_fields('social_streams_' . $network);
        }
        ?>
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
        </p>
    </form>
    <?php
}

/**
 * Show the registered social network accounts
 * Allow a new account to be added 
 */
function appsol_social_streams_accounts() {
    $accounts = SocialStreamsHelper::getAuthenticatedNetworks();
    ?>
    <h3>Account details</h3>
    <table class="form-table social-streams-accounts">
        <tbody>
            <tr>
                <th>Network</th>
                <th>Account ID</th>
                <th>User Name</th>
                <th>Account Status</th>
                <th>Authentication Expires</th>
            </tr>
            <?php
            foreach ($accounts as $account):
                $transient_name = 'socialstreams_' . $accoun['network'] . '_profile_' . $account['clientid'];
                if ($profile = get_transient($transient_name)):
                    ?>
                    <tr>
                        <td><?php echo $profile->Nicename; ?></td>
                        <td><?php echo $account['clientid']; ?></td>
                        <td><?php echo $profile->name; ?></td>
                        <td>
                            <label for="social_streams_account_<?php echo $account['clientid']; ?>_status_yes"><input type="radio" id="social_streams_account_<?php echo $account['clientid']; ?>_status_yes" name="social_streams_account_<?php echo $account['clientid']; ?>_status" value="1" <?php echo ($account['authorized'] ? 'checked="checked" ' : ''); ?>/>On</label>
                            <label for="social_streams_account_<?php echo $account['clientid']; ?>_status_yes"><input type="radio" id="social_streams_account_<?php echo $account['clientid']; ?>_status_no" name="social_streams_account_<?php echo $account['clientid']; ?>_status" value="0" <?php echo ($account['authorized'] ? '' : 'checked="checked"'); ?>/>Off</label>
                        </td>
                        <td><?php echo $account['expiry']; ?></td>
                    </tr>
                <?php
                endif;
            endforeach;
            ?>
        </tbody>
    </table>
    <h3>New Account</h3>
    <table class="form-table social-streams-new-account">
        <tbody>
        <form action="<?php get_admin_url(); ?>plugins.php?page=appsol_social_streams" method="GET">
            <input type="hidden" name="action" value="appsol_social_streams_get_auth" />
            <tr>
                <th scope="row">Authorise Account Access</th>
                <td>
                    <label for="social_streams_new_account">Select a Social Network</label>
                    <select id="social_streams_new_account" name="social_streams_new_account">
                        <option value="0">Select Network</option>
                        <?php foreach (SocialStreamsHelper::getNetworks() as $network): ?>
                            <option value="<?php echo $network; ?>"><?php echo ucfirst($network); ?></option>
    <?php endforeach; ?>
                    </select>
                </td>
                <td>
                    <p class="submit">
                        <input type="submit" class="button-primary" value="<?php _e('Connect') ?>" />
                    </p>
                </td>
            </tr>
            </tbody>
    </table>
    </form>
    <?php
}

/**
 * Main entry point for Options Page 
 */
function appsol_social_streams_options() {
    if (!current_user_can('manage_options')) {
        wp_die(__('You do not have sufficient permissions to access this page.'));
    }
    if (!session_id())
        session_start();
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'facebook';
    ?>
    <div class="wrap">
        <h2>Social Streams</h2>
        <h2 class="nav-tab-wrapper">
            <a href="<?php get_admin_url(); ?>plugins.php?page=appsol_social_streams&tab=networks" class="nav-tab<?php echo $active_tab == 'networks' ? ' nav-tab-active' : ''; ?>">Networks</a>
            <a href="<?php get_admin_url(); ?>plugins.php?page=appsol_social_streams&tab=accounts" class="nav-tab<?php echo $active_tab == 'accounts' ? ' nav-tab-active' : ''; ?>">Accounts</a>
            <a href="<?php get_admin_url(); ?>plugins.php?page=appsol_social_streams&tab=profiles" class="nav-tab<?php echo $active_tab == 'profiles' ? ' nav-tab-active' : ''; ?>">Profiles</a>
            <a href="<?php get_admin_url(); ?>plugins.php?page=appsol_social_streams&tab=items" class="nav-tab<?php echo $active_tab == 'items' ? ' nav-tab-active' : ''; ?>">Items</a>
        </h2>

        <?php
        switch ($active_tab) {
            case 'networks':

                break;
            case 'auth':

                break;
            case 'profiles':

                break;
            case 'items':

                break;
        }
        ?>
    </div>
    <?php
}
?>
