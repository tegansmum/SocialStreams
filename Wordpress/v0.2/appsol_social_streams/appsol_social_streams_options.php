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
    // Facebook Settings
    register_setting('social_streams_facebook_options', 'appsol_social_streams_fb_access_token');
    register_setting('social_streams_facebook_options', 'appsol_social_streams_fb_state');
    register_setting('social_streams_facebook_options', 'appsol_social_streams_fb_code');
    register_setting('social_streams_facebook_options', 'appsol_social_streams_fb_users');
    register_setting('social_streams_facebook_options', 'appsol_social_streams_fb_last_msg');
    // Twitter Settings
    register_setting('social_streams_twitter_options', 'appsol_social_streams_tw_token');
    register_setting('social_streams_twitter_options', 'appsol_social_streams_tw_token_secret');
    register_setting('social_streams_twitter_options', 'appsol_social_streams_tw_users');
    register_setting('social_streams_twitter_options', 'appsol_social_streams_tw_last_msg');
    // LinkedIn Settings
    register_setting('social_streams_linkedin_options', 'appsol_social_streams_li_token');
    register_setting('social_streams_linkedin_options', 'appsol_social_streams_li_token_secret');
    register_setting('social_streams_linkedin_options', 'appsol_social_streams_li_users');
    register_setting('social_streams_linkedin_options', 'appsol_social_streams_li_last_msg');
    // Google Plus Settings
    register_setting('social_streams_googleplus_options', 'appsol_social_streams_gp_token');
    register_setting('social_streams_googleplus_options', 'appsol_social_streams_gp_users');
    register_setting('social_streams_googleplus_options', 'appsol_social_streams_gp_last_msg');
    // Add the settings sections
    add_settings_section('appsol_facebook', 'Facebook Settings', 'appsol_social_streams_facebook', 'appsol_social_streams');
    add_settings_section('appsol_twitter', 'Twitter Settings', 'appsol_social_streams_twitter', 'appsol_social_streams');
    add_settings_section('appsol_linkedin', 'LinkedIn Settings', 'appsol_social_streams_linkedin', 'appsol_social_streams');
    add_settings_section('appsol_googleplus', 'Google+ Settings', 'appsol_social_streams_googleplus', 'appsol_social_streams');
    // Add the admin stylesheets
    wp_register_style('appsol_social_streams_admin_css', plugins_url('css/admin.css', __FILE__));
    wp_register_script('appsol_social_streams_admin_js', plugins_url('js/social_streams_admin.js', __FILE__), array('jquery', 'jquery-ui-dialog'));
    appsol_social_streams_admin_resources();
}

function appsol_social_streams_header($network) {
    ?>
    <h3>Account details for <?php echo ucfirst($network); ?></h3>
    <table class="form-table social-streams-accounts <?php echo $network; ?>">
        <tbody>
            <?php
        }

function appsol_social_streams_footer() {
            ?>
        </tbody>
    </table>
    <?php
}

function appsol_social_streams_text_field($args) {
    $setting = $args['label_for'];
    $option = get_option($setting);
    echo '<input type="text" id="' . $setting . '" name="' . $setting . '" value="' . $option . '" />';
}

function appsol_social_streams_message($args) {
    $setting = $args['label_for'];
    $option = get_option($setting);
    echo '<p>' . $option . '</p>';
}

function appsol_social_streams_facebook() {
    appsol_social_streams_header('facebook');
    $appsol_fb = new appsolFacebookApi();
    ?>
    <tr>
        <th scope="row">New Account</th>
        <td>image</td>
        <td><a class="button" href="<?php echo $appsol_fb->get_request_url(); ?>">Authenticate with Facebook</a></td>
<!--        <td></td>
        <td></td>
        <td></td>
        <td></td>-->
    </tr>
    <?php
    if ($fb_users = get_option('appsol_social_streams_fb_users')) {
        foreach ($fb_users as $fb_id => $fb_name) {
            appsol_social_streams_facebook_account($fb_id);
        }
    }
    ?>
    <tr>
        <th scope="row">Facebook Last Message</th>
        <td colspan="2"><?php echo get_option('appsol_social_streams_fb_last_msg'); ?></td>
    </tr>
    <?php
    appsol_social_streams_footer();
}

function appsol_social_streams_twitter() {
    _log('appsol_social_streams_twitter');
    appsol_social_streams_header('twitter');
    $appsol_tw = new appsolTwitterApi();
    _log($appsol_tw->profile);
    _log(get_option('appsol_social_streams_tw_users'));
    ?>
    <tr>
        <th scope="row">New Account</th>
        <td>image</td>
        <td><a class="button" href="<?php echo $appsol_tw->get_request_url(); ?>">Authenticate with Twitter</a></td>
    </tr>
    <?php
    if ($tw_users = get_option('appsol_social_streams_tw_users')) {
        foreach ($tw_users as $tw_id => $tw_name) {
            appsol_social_streams_twitter_account($tw_id);
        }
    }
    appsol_social_streams_footer();
}

function appsol_social_streams_linkedin() {
    appsol_social_streams_header('linkedin');
    $appsol_li = new appsolLinkedinApi();
    ?>
    <tr>
        <th scope="row">New Account</th>
        <td>image</td>
        <td><a class="button" href="<?php echo $appsol_li->get_request_url(); ?>">Authenticate with LinkedIn</a></td>
    </tr>
    <?php
    if ($li_users = get_option('appsol_social_streams_li_users')) {
        foreach ($li_users as $li_id => $li_name) {
            appsol_social_streams_linkedin_account($li_id);
        }
    }
    appsol_social_streams_footer();
}

function appsol_social_streams_googleplus() {
    appsol_social_streams_header('googleplus');
    $appsol_gp = new appsolGoogleApi();
    ?>
    <tr>
        <th scope="row">New Account</th>
        <td>image</td>
        <td><a class="button" href="<?php echo $appsol_gp->get_request_url(); ?>">Authenticate with Google+</a></td>
    </tr>
    <?php
    if ($gp_users = get_option('appsol_social_streams_gp_users')) {
        foreach ($gp_users as $gp_id => $gp_name) {
            appsol_social_streams_googleplus_account($gp_id);
        }
    }
    appsol_social_streams_footer();
}

function appsol_social_streams_facebook_account($fb_id) {
    $appsol_fb = new appsolFacebookApi($fb_id);
    $profile = new appsolFacebookProfile();
    $profile->setProfile($appsol_fb->profile);
    ?>
    <tr>
        <th scope="row"><?php echo $profile->name; ?></th>
        <td><a href="<?php echo $profile->link; ?>" target="_blank"><img src="<?php echo $profile->image; ?>" /></a></td>
        <td><a class="button" href="<?php get_admin_url(); ?>plugins.php?page=appsol_social_streams&revoke=social_streams_facebook&fb_user=<?php echo $fb_id; ?>&tab=facebook">Revoke Facebook Authentication</a></td>
<!--        <td><a class="button stream-test" id="facebook_profile" href="#">Profile</a></td>
        <td><a class="button stream-test" id="facebook_post" href="#">Posts</a></td>
        <td><a class="button stream-test" id="facebook_friend" href="#">Friends</a></td>
        <td><a class="button stream-test" id="facebook_album" href="#">Albums</a></td>-->
    </tr>
    <?php
}

function appsol_social_streams_twitter_account($tw_id) {
    $appsol_tw = new appsolTwitterApi($tw_id);
    $profile = new appsolTwitterProfile();
    $profile->setProfile($appsol_tw->profile);
    ?>
    <tr>
        <th scope="row"><?php echo $profile->name; ?></th>
        <td><a href="<?php echo $profile->link; ?>" target="_blank"><img src="<?php echo $profile->image; ?>" /></a></td>
        <td><a class="button" href="<?php get_admin_url(); ?>plugins.php?page=appsol_social_streams&revoke=social_streams_twitter&tw_user=<?php echo $tw_id; ?>&tab=twitter">Revoke Twitter Authentication</a></td>
    </tr>
    <?php
}

function appsol_social_streams_linkedin_account($li_id) {
    $appsol_li = new appsolLinkedinApi($li_id);
    $profile = new appsolLinkedinProfile();
    $profile->setProfile($appsol_li->profile);
    ?>
    <tr>
        <th scope="row"><?php echo $profile->name; ?></th>
        <td><a href="<?php echo $profile->link; ?>" target="_blank"><img src="<?php echo $profile->image; ?>" /></a></td>
        <td><a class="button" href="<?php get_admin_url(); ?>plugins.php?page=appsol_social_streams&revoke=social_streams_linkedin&li_user=<?php echo $li_id; ?>&tab=linkedin">Revoke LinkedIn Authentication</a></td>
    </tr>
    <?php
}

function appsol_social_streams_googleplus_account($gp_id) {
    $appsol_gp = new appsolGoogleApi($gp_id);
    $profile = new appsolGooglePlusProfile();
    $profile->setProfile($appsol_gp->profile);
    ?>
    <tr>
        <th scope="row"><?php echo $profile->name; ?></th>
        <td><a href="<?php echo $profile->link; ?>" target="_blank"><img src="<?php echo $profile->image; ?>" /></a></td>
        <td><a class="button" href="<?php get_admin_url(); ?>plugins.php?page=appsol_social_streams&revoke=social_streams_gplus&gp_user=<?php echo $gp_id; ?>&tab=googleplus">Revoke Google+ Authentication</a></td>
    </tr>
    <?php
}

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
            <a href="<?php get_admin_url(); ?>plugins.php?page=appsol_social_streams&tab=facebook" class="nav-tab<?php echo $active_tab == 'facebook' ? ' nav-tab-active' : ''; ?>">Facebook</a>
            <a href="<?php get_admin_url(); ?>plugins.php?page=appsol_social_streams&tab=twitter" class="nav-tab<?php echo $active_tab == 'twitter' ? ' nav-tab-active' : ''; ?>">Twitter</a>
            <a href="<?php get_admin_url(); ?>plugins.php?page=appsol_social_streams&tab=linkedin" class="nav-tab<?php echo $active_tab == 'linkedin' ? ' nav-tab-active' : ''; ?>">Linked In</a>
            <a href="<?php get_admin_url(); ?>plugins.php?page=appsol_social_streams&tab=googleplus" class="nav-tab<?php echo $active_tab == 'googleplus' ? ' nav-tab-active' : ''; ?>">Google+</a>
        </h2>
        <form method="post" action="options.php"> 
            <?php
            switch ($active_tab) {
                case 'facebook':
                    settings_fields('social_streams_facebook_options');
                    appsol_social_streams_facebook();
                    break;
                case 'twitter':
                    settings_fields('social_streams_twitter_options');
                    appsol_social_streams_twitter();
                    break;
                case 'linkedin':
                    settings_fields('social_streams_linkedin_options');
                    appsol_social_streams_linkedin();
                    break;
                case 'googleplus':
                    settings_fields('social_streams_googleplus_options');
                    appsol_social_streams_googleplus();
                    break;
            }
            ?>
            <p class="submit">
                <input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
            </p>
        </form>

    </div>
    <?php
}
?>
