<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

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
    echo '<input title="' . $args["description"] . '" type="radio" id="' . $setting . '_yes" name="' . $setting . '" value="1" ' . ($option ? 'checked="checked" ' : '') . '/>Yes ';
    echo '<input title="' . $args["description"] . '" type="radio" id="' . $setting . '_no" name="' . $setting . '" value="0" ' . ($option ? '' : 'checked="checked" ') . '/>No';
}

function appsol_social_streams_general_section() {
    echo '<p>General settings around storing and retrieving updates.</p>';
}

function appsol_social_streams_facebook_section() {
    echo '<p>Facebook settings</p>';
}

function appsol_social_streams_twitter_section() {
    echo '<p>Twitter settings</p>';
}

function appsol_social_streams_linkedin_section() {
    echo '<p>LinkedIn settings</p>';
}

function appsol_social_streams_google_section() {
    echo '<p>Google+ settings</p>';
}

function appsol_social_streams_instagram_section() {
    echo '<p>Instagram settings</p>';
}

/**
 * Show the configuration options for the available social networks 
 */
function appsol_social_streams_networks() {
    ?>
    <form method="post" action="options.php"> 
        <?php settings_fields('appsol_social_streams'); ?>
        <?php do_settings_sections('appsol_social_streams'); ?>
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
    $accounts = SocialStreamsHelper::getAuthenticatedAccounts();
    SocialStreamsHelper::log($accounts);
    ?>
    <h3>Account details</h3>
    <form action="<?php echo SocialStreamsHelper::getBaseRedirectUrl(); ?>&tab=accounts" method="POST">
        <?php wp_nonce_field(plugin_basename(__FILE__), 'appsol_social_streams_accounts_nonce'); ?>
        <table class="form-table social-streams-accounts">
            <tbody>
                <tr>
                    <th>Account ID</th>
                    <th>Network</th>
                    <th>User Name</th>
                    <th>Account Status</th>
                    <th>Authentication Expires</th>
                    <th>Delete?</th>
                </tr>
                <?php
                foreach ($accounts as $account):
                    if ($profile = SocialStreamsProfileCache::getProfiles($account['network'], $account['clientid'], $account['clientid'])):
                        ?>
                        <tr>
                            <td><?php echo $account['clientid']; ?></td>
                            <td><?php echo $profile->nicename; ?></td>
                            <td><?php echo $profile->name; ?></td>
                            <td>
                                <label for="social_streams_account_<?php echo $account['clientid']; ?>_status_yes"><input type="radio" id="social_streams_account_<?php echo $account['clientid']; ?>_status_yes" name="social_streams_account_status[<?php echo $account['clientid']; ?>]" value="1" <?php echo ($account['authorized'] ? 'checked="checked" ' : ''); ?>/>On</label>
                                <label for="social_streams_account_<?php echo $account['clientid']; ?>_status_no"><input type="radio" id="social_streams_account_<?php echo $account['clientid']; ?>_status_no" name="social_streams_account_status[<?php echo $account['clientid']; ?>]" value="0" <?php echo ($account['authorized'] ? '' : 'checked="checked"'); ?>/>Off</label>
                            </td>
                            <td><?php echo $account['expiry']; ?></td>
                            <td><input type="checkbox" id="social_streams_account_delete_<?php echo $account['clientid']; ?>" name="social_streams_account_delete[<?php echo $account['network']; ?>][]" value="<?php echo $account['clientid']; ?>" /></td>
                        </tr>
                        <?php
                    endif;
                endforeach;
                ?>
            </tbody>
        </table>
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Apply') ?>" />
        </p>
    </form>
    <h3>New Account</h3>
    <p>To authorise account access, you must be logged in or able to log in to the social network account you wish to connect to.</p>
    <form action="<?php echo SocialStreamsHelper::getBaseRedirectUrl(); ?>&tab=accounts" method="POST">
    <?php wp_nonce_field(plugin_basename(__FILE__), 'appsol_social_streams_accounts_nonce'); ?>
        <input type="hidden" name="get_auth" value="1" />
        <table class="form-table social-streams-new-account">
            <tbody>
                <tr>
                    <th scope="row"><label for="social_streams_new_account">Select a Social Network</label></th>
                    <td>
                        <select id="social_streams_new_account" name="network">
                            <option value="0">Select Network</option>
                            <?php foreach (SocialStreamsHelper::getNetworks() as $network): ?>
                                <option value="<?php echo $network; ?>"><?php echo ucfirst($network); ?></option>
    <?php endforeach; ?>
                        </select>
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="submit">
            <input type="submit" class="button-primary" value="<?php _e('Connect') ?>" />
        </p>
    </form>
    <?php
}

/**
 * List the current stored profiles
 */
function appsol_social_streams_profiles() {
    $filter = isset($_POST['social_network_filter']) ? $_POST['social_network_filter'] : '';
    ?>
    <h3>Connected Profiles</h3>
    <form action="<?php echo SocialStreamsHelper::getBaseRedirectUrl(); ?>&tab=profiles" method="POST">
    <?php wp_nonce_field(plugin_basename(__FILE__), 'appsol_social_streams_profiles_nonce'); ?>
        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="action">
                    <option value="0">Bulk Actions</option>
                    <option value="1">Refresh</option>
                </select>
                <input type="submit" name="" id="post-query-submit" class="button-secondary" value="Apply" />
            </div>
            <div class="alignleft actions">
                <select name="social_network_filter">
                    <option value="0">View all Networks</option>
                    <?php $networks = SocialStreamsHelper::getNetworks(); ?>
                    <?php foreach ($networks as $network): ?>
                        <?php $selected = $network == $filter ? ' selected="selected"' : ''; ?>
                        <option value="<?php echo $network; ?>"<?php echo $selected; ?>><?php echo ucfirst($network); ?></option>
    <?php endforeach; ?>
                </select>
                <input type="submit" name="" id="post-query-submit" class="button-secondary" value="Filter" />
            </div>
        </div>
    </form>
    <table class="form-table social-streams-profiles">
        <tbody>
            <tr>
                <th>Network</th>
                <th>User</th>
                <th>Name</th>
                <th>Image</th>
                <th>Expires</th>
            </tr>
            <?php foreach (SocialStreamsHelper::getAuthenticatedAccounts($filter) as $account): ?>
                <?php foreach (SocialStreamsProfileCache::getProfiles($account['network'], $account['clientid']) as $profile): ?>
            <?php SocialStreamsHelper::log($profile); ?>
                    <tr>
                        <td><?php echo $profile->nicename; ?></td>
                        <td><?php echo $profile->user; ?></td>
                        <td><?php echo $profile->name; ?></td>
                        <td><img width="40" height="40" src="<?php echo $profile->image; ?>" /></td>
                        <td><?php echo $profile->expires; ?></td>
                    </tr>
                <?php endforeach; ?>
    <?php endforeach; ?>
        </tbody>
    </table>
    <?php
}

/**
 * List the current stored items
 */
function appsol_social_streams_items() {
    $filter = isset($_POST['social_network_filter']) ? $_POST['social_network_filter'] : '';
    ?>
    <h3>Network Updates</h3>
    <form action="<?php echo SocialStreamsHelper::getBaseRedirectUrl(); ?>&tab=items" method="POST">
    <?php wp_nonce_field(plugin_basename(__FILE__), 'appsol_social_streams_items_nonce'); ?>
        <div class="tablenav top">
            <div class="alignleft actions">
                <select name="action">
                    <option value="0">Bulk Actions</option>
                    <option value="1">Refresh</option>
                </select>
                <input type="submit" name="" id="post-query-submit" class="button-secondary" value="Apply" />
            </div>
            <div class="alignleft actions">
                <select name="social_network_filter">
                    <option value="0">View all Networks</option>
                    <?php $networks = SocialStreamsHelper::getNetworks(); ?>
                    <?php foreach ($networks as $network): ?>
                        <?php $selected = $network == $filter ? ' selected="selected"' : ''; ?>
                        <option value="<?php echo $network; ?>"<?php echo $selected; ?>><?php echo ucfirst($network); ?></option>
    <?php endforeach; ?>
                </select>
                <input type="submit" name="" id="post-query-submit" class="button-secondary" value="Filter" />
            </div>
        </div>
    </form>
    <table class="form-table social-streams-items">
        <tbody>
            <tr>
                <th>Network</th>
                <th>Name</th>
                <th>Update</th>
                <th>Published</th>
                <th>Expires</th>
            </tr>
            <?php foreach (SocialStreamsHelper::getAuthenticatedAccounts($filter) as $account): ?>
        <?php foreach (SocialStreamsItemCache::getItems($account['network'], $account['clientid']) as $item): ?>
                    <tr>
                        <td><?php echo $item->nicename; ?></td>
                        <td><?php echo $item->profile->name; ?></td>
                        <td><?php echo $item->display(); ?></td>
                        <td><?php echo $item->published; ?></td>
                        <td><?php echo $item->expires; ?></td>
                    </tr>
                <?php endforeach; ?>
    <?php endforeach; ?>
        </tbody>
    </table>
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
        @session_start();
    $active_tab = isset($_GET['tab']) ? $_GET['tab'] : 'networks';
    ?>
    <div class="wrap">
        <div class="icon32" id="icon-options-general"><br></div>
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
                appsol_social_streams_networks();
                break;
            case 'accounts':
                if (isset($_POST['social_streams_account_delete']) && count($_POST['social_streams_account_delete'])) {
                    foreach ($_POST['social_streams_account_delete'] as $network => $clients) {
                        foreach ($clients as $client_id) {
                            appsolSocialStreams::remove_auth($network, $client_id);
                        }
                    }
                }
                appsol_social_streams_accounts();
                break;
            case 'profiles':
                if (isset($_POST['action']) && $_POST['action'] == 1) {
                    $network = isset($_POST['social_network_filter']) ? $_POST['social_network_filter'] : '';
                    SocialStreamsProfileCache::refresh(true, $network);
                }
                appsol_social_streams_profiles();
                break;
            case 'items':
                if (isset($_POST['action']) && $_POST['action'] == 1) {
                    $network = isset($_POST['social_network_filter']) ? $_POST['social_network_filter'] : '';
                    SocialStreamsItemCache::refresh(true, $network);
                }
                appsol_social_streams_items();
                break;
        }
        ?>
    </div>
    <?php
}
?>
