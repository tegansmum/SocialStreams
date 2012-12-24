<?php
/*
  Plugin Name: AppSol Social Streams
  Plugin URI: http://www.appropriatesolutions.co.uk/wordpress/plugins
  Description: Shows Twitter and Facebook data
  Version: 0.2
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
require_once APPSOL_SOCIAL_STREAMS_PATH . 'lib/connections.php';
require_once APPSOL_SOCIAL_STREAMS_PATH . 'lib/profiles.php';
require_once APPSOL_SOCIAL_STREAMS_PATH . 'lib/items.php';
require_once APPSOL_SOCIAL_STREAMS_PATH . 'lib/caches.php';
require_once APPSOL_SOCIAL_STREAMS_PATH . 'lib/gallery_posttype.php';

class appsolSocialStreamsWidget extends WP_Widget {

    private $instance = null;
    private $facebook = null;
    private $facebookUser = null;
    public $facebookProfile = null;
    private $twitter = null;
    private $twitterProfile = null;
    private $linkedin = null;
    private $linkedinProfile = null;
    private $googleplus = null;
    private $googleplusProfile = null;

    function appsolSocialStreamsWidget() {
        $widget_ops = array(
            'classname' => 'social-streams',
            'description' => 'Displays the updates for a user from multiple Social Networks in one stream.'
        );
        $control_ops = array(
            'width' => 300,
            'height' => 350,
            'id_base' => 'appsol-social-streams-widget'
        );
        $this->WP_Widget('appsol-social-streams-widget', 'Social Streams', $widget_ops, $control_ops);
    }

    function widget($args, $instance) {
        if (is_admin())
            return false;
        extract($args);
        $this->instance = $instance;
        $title = apply_filters('widget_title', $instance['title']);
        echo $before_widget;

        if ($title)
            echo $before_title . $title . $after_title;
        ?>
        <div  class="stream">
            <ul>
                <?php
                if (!is_admin()) {
                    $stream = $this->stream($this->instance);
                    foreach ($stream as $stream_item) {
                        echo $stream_item->display();
                    }
                }
                ?>
            </ul>
        </div>
        <?php if (!is_admin()) $this->profiles($this->instance); ?>
        <div class="social-metrics">
            <ul>
                <?php if (isset($this->facebookProfile['name'])): ?>
                    <?php $friend_totals = get_option('appsol_social_streams_fb_followers', array()); ?>
                    <li class="social-metric facebook">
                        <a title="Friend <?php echo $this->facebookProfile['name']; ?> on Facebook" class="profile-link" href="<?php echo $this->facebookProfile['link'] ?>" rel="nofollow"><span class="metric-count"><?php echo $friend_totals[$this->instance['fb_user_id']]; ?></span> Friends</a>
                    </li>
                <?php endif; ?>
                <?php if (isset($this->twitterProfile->name)): ?>
                    <?php $follower_totals = get_option('appsol_social_streams_tw_followers', array()); ?>
                    <li class="social-metric twitter">
                        <a title="Follow <?php echo $this->twitterProfile->name; ?> on Twitter" class="profile-link" href="http://twitter.com#!/<?php echo $this->twitterProfile->screen_name; ?>" rel="nofollow"><span class="metric-count"><?php echo $follower_totals[$this->instance['tw_user_id']]; ?></span> Followers</a>
                    </li>
                <?php endif; ?>
                <?php if (isset($this->linkedinProfile->firstName)): ?>
                    <li class="social-metric linkedin">
                        <a title="Connect with <?php echo $this->linkedinProfile->firstName . ' ' . $this->linkedinProfile->lastName; ?> on LinkedIn" class="profile-link" href="<?php echo $this->linkedinProfile->publicProfileUrl; ?>" rel="nofollow"><span class="metric-count"><?php echo get_option('appsol_li_connections'); ?></span> Connections</a>
                    </li>
                <?php endif; ?>
                <?php if (isset($this->googleplusProfile['displayName'])): ?>
                    <?php $circles_totals = get_option('appsol_social_streams_gp_circles', array()); ?>
                    <li class="social-metric gplus">
                        <a title="Add <?php echo $this->googleplusProfile['displayName']; ?> to your Google+ Circles" class="profile-link" href="<?php echo $this->googleplusProfile['url']; ?>" rel="nofollow"> In <span class="metric-count"><?php echo $circles_totals[$this->instance['gp_user_id']]; ?></span> Circles</a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
        <div class="connections">
            <ul>
                <?php
                $connections = $this->connections($this->instance);
                foreach ($connections as $connection)
                    echo $connection->display();
                ?>
            </ul>
        </div>
        <script type="text/javascript">
            /* <![CDATA[ */
            jQuery(document).ready(function(){
                appsolSocialStreamsUpdate('<?php echo $this->id; ?>')
            })
            /* ]]> */
        </script>
        <?php
        echo $after_widget;
    }

    function update($new_instance, $old_instance) {
        $instance = $old_instance;
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['tw_user_id'] = $new_instance['tw_user_id'];
        $instance['tw_trim_user'] = $new_instance['tw_trim_user'];
        $instance['tw_include_rts'] = $new_instance['tw_include_rts'];
        $instance['tw_include_entities'] = $new_instance['tw_include_entities'];
        $instance['tw_show_profile_image'] = $new_instance['tw_show_profile_image'];
        $instance['tw_show_only_friends'] = $new_instance['tw_show_only_friends'];
        $instance['tw_show_spam_followers'] = $new_instance['tw_show_spam_followers'];
        $instance['fb_user_id'] = $new_instance['fb_user_id'];
//        $instance['fb_user'] = $new_instance['fb_user'];
        $instance['fb_feed'] = $new_instance['fb_feed'];
        $instance['li_user_id'] = $new_instance['li_user_id'];
        $instance['gp_user_id'] = $new_instance['gp_user_id'];
        $instance['gp_show_content'] = $new_instance['gp_show_content'];
        $instance['show_connections'] = strip_tags($new_instance['show_connections']);
        if ($instance['show_connections'] === '')
            $instance['show_connections'] = 0;
        $instance['connection_image_size'] = $new_instance['connection_image_size'];
        // Reset all the caches
        global $appsol_social_streams_caches;
        foreach ($appsol_social_streams_caches as $cachetype) {
            foreach ($cachetype as $network => $networkcaches) {
                foreach ($networkcaches as $networkcache) {
                    $cache = new $networkcache($old_instance);
                    delete_transient($cache->transient);
                }
            }
        }
        return $instance;
    }

    function form($instance) {
        $defaults = array(
            'title' => 'Social Activity',
            'tw_user_id' => 0,
//            'tw_count' => 5,
            'tw_trim_user' => 1,
            'tw_include_rts' => 1,
            'tw_include_entities' => 1,
            'tw_show_profile_image' => 1,
            'tw_show_only_friends' => 1,
            'tw_show_spam_followers' => 0,
            'fb_user_id' => 0,
            'fb_feed' => 'feed',
            'li_user_id' => 0,
            'gp_user_id' => 0,
            'gp_show_content' => 0,
            'show_connections' => 6,
            'connection_image_size' => 'normal',
        );
        $instance = wp_parse_args((array) $instance, $defaults);
        $this->instance = $instance;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
        </p>

        <h3>Twitter</h3>
        <p>
            <label for="<?php echo $this->get_field_id('tw_user_id'); ?>">Twitter User</label>
            <select id="<?php echo $this->get_field_id('tw_user_id'); ?>" name="<?php echo $this->get_field_name('tw_user_id'); ?>">
                <option value="0" <?php if ($instance['tw_user_id'] == 0) echo 'selected="selected"'; ?>>Please select</option>
                <?php foreach (get_option('appsol_social_streams_tw_users') as $tw_id => $tw_name): ?>
                    <option value="<?php echo $tw_id; ?>" <?php if ($instance['tw_user_id'] == $tw_id) echo 'selected="selected"'; ?>><?php echo $tw_name; ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('tw_trim_user'); ?>_true">
                <input type="radio" id="<?php echo $this->get_field_id('tw_trim_user'); ?>_true" name="<?php echo $this->get_field_name('tw_trim_user'); ?>" <?php checked($instance['tw_trim_user'], 1); ?> value="1" />
                Short User Details</label>
            <label for="<?php echo $this->get_field_id('tw_trim_user'); ?>_false">
                <input type="radio" id="<?php echo $this->get_field_id('tw_trim_user'); ?>_false" name="<?php echo $this->get_field_name('tw_trim_user'); ?>" <?php checked($instance['tw_trim_user'], 0); ?> value="0" />
                Full User Details</label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('tw_include_rts'); ?>_true">
                <input type="radio" id="<?php echo $this->get_field_id('tw_include_rts'); ?>_true" name="<?php echo $this->get_field_name('tw_include_rts'); ?>" <?php checked($instance['tw_include_rts'], 1); ?> value="1" />
                Include Re-Tweets</label>
            <label for="<?php echo $this->get_field_id('tw_include_rts'); ?>_false">
                <input type="radio" id="<?php echo $this->get_field_id('tw_include_rts'); ?>_false" name="<?php echo $this->get_field_name('tw_include_rts'); ?>" <?php checked($instance['tw_include_rts'], 0); ?> value="0" />
                Exclude Re-Tweets</label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('tw_include_entities'); ?>_true">
                <input type="radio" id="<?php echo $this->get_field_id('tw_include_entities'); ?>_true" name="<?php echo $this->get_field_name('tw_include_entities'); ?>" <?php checked($instance['tw_include_entities'], 1); ?> value="1" />
                Include Entities</label>
            <label for="<?php echo $this->get_field_id('tw_include_entities'); ?>_false">
                <input type="radio" id="<?php echo $this->get_field_id('tw_include_entities'); ?>_false" name="<?php echo $this->get_field_name('tw_include_entities'); ?>" <?php checked($instance['tw_include_entities'], 0); ?> value="0" />
                Exclude Entities</label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('tw_show_profile_image'); ?>_true">
                <input type="radio" id="<?php echo $this->get_field_id('tw_show_profile_image'); ?>_true" name="<?php echo $this->get_field_name('tw_show_profile_image'); ?>" <?php checked($instance['tw_show_profile_image'], 1); ?> value="1" />
                Show Profile Image</label>
            <label for="<?php echo $this->get_field_id('tw_show_profile_image'); ?>_false">
                <input type="radio" id="<?php echo $this->get_field_id('tw_show_profile_image'); ?>_false" name="<?php echo $this->get_field_name('tw_show_profile_image'); ?>" <?php checked($instance['tw_show_profile_image'], 0); ?> value="0" />
                Hide Profile Image</label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('tw_show_only_friends'); ?>_true">
                <input type="radio" id="<?php echo $this->get_field_id('tw_show_only_friends'); ?>_true" name="<?php echo $this->get_field_name('tw_show_only_friends'); ?>" <?php checked($instance['tw_show_only_friends'], 1); ?> value="1" />
                Show Only Friends</label>
            <label for="<?php echo $this->get_field_id('tw_show_only_friends'); ?>_false">
                <input type="radio" id="<?php echo $this->get_field_id('tw_show_only_friends'); ?>_false" name="<?php echo $this->get_field_name('tw_show_only_friends'); ?>" <?php checked($instance['tw_show_only_friends'], 0); ?> value="0" />
                Show all Followers</label>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('tw_show_spam_followers'); ?>_true">
                <input type="radio" id="<?php echo $this->get_field_id('tw_show_spam_followers'); ?>_true" name="<?php echo $this->get_field_name('tw_show_spam_followers'); ?>" <?php checked($instance['tw_show_spam_followers'], 1); ?> value="1" />
                Show Spammy Followers</label>
            <label for="<?php echo $this->get_field_id('tw_show_spam_followers'); ?>_false">
                <input type="radio" id="<?php echo $this->get_field_id('tw_show_spam_followers'); ?>_false" name="<?php echo $this->get_field_name('tw_show_spam_followers'); ?>" <?php checked($instance['tw_show_spam_followers'], 0); ?> value="0" />
                Hide Spammy Followers</label>
        </p>
        <h3>Facebook</h3>
        <p>
            <label for="<?php echo $this->get_field_id('fb_user_id'); ?>">Facebook User</label>
            <select id="<?php echo $this->get_field_id('fb_user_id'); ?>" name="<?php echo $this->get_field_name('fb_user_id'); ?>">
                <option value="0" <?php if ($instance['fb_user_id'] == 0) echo 'selected="selected"'; ?>>Please select</option>
                <?php foreach (get_option('appsol_social_streams_fb_users') as $fb_id => $fb_name): ?>
                    <option value="<?php echo $fb_id; ?>" <?php if ($instance['fb_user_id'] == $fb_id) echo 'selected="selected"'; ?>><?php echo $fb_name; ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('fb_feed'); ?>_true">
                <input type="radio" id="<?php echo $this->get_field_id('fb_feed'); ?>_true" name="<?php echo $this->get_field_name('fb_feed'); ?>" <?php checked($instance['fb_feed'], 'feed'); ?> value="feed" />
                Show Wall Feed</label>
            <label for="<?php echo $this->get_field_id('fb_feed'); ?>_false">
                <input type="radio" id="<?php echo $this->get_field_id('fb_feed'); ?>_false" name="<?php echo $this->get_field_name('fb_feed'); ?>" <?php checked($instance['fb_feed'], 'home'); ?> value="home" />
                Show News Feed</label>
        </p>
        <h3>LinkedIn</h3>
        <p>
            <label for="<?php echo $this->get_field_id('li_user_id'); ?>">LinkedIn User</label>
            <select id="<?php echo $this->get_field_id('li_user_id'); ?>" name="<?php echo $this->get_field_name('li_user_id'); ?>">
                <option value="0" <?php if ($instance['li_user_id'] == 0) echo 'selected="selected"'; ?>>Please select</option>
                <?php foreach (get_option('appsol_social_streams_li_users') as $li_id => $li_name): ?>
                    <option value="<?php echo $li_id; ?>" <?php if ($instance['li_user_id'] == $li_id) echo 'selected="selected"'; ?>><?php echo $li_name; ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <h3>Google+</h3>
        <p>
            <label for="<?php echo $this->get_field_id('gp_user_id'); ?>">Google+ User</label>
            <select id="<?php echo $this->get_field_id('gp_user_id'); ?>" name="<?php echo $this->get_field_name('gp_user_id'); ?>">
                <option value="0" <?php if ($instance['gp_user_id'] == 0) echo 'selected="selected"'; ?>>Please select</option>
                <?php foreach (get_option('appsol_social_streams_gp_users') as $gp_id => $gp_name): ?>
                    <option value="<?php echo $gp_id; ?>" <?php if ($instance['gp_user_id'] == $gp_id) echo 'selected="selected"'; ?>><?php echo $gp_name; ?></option>
                <?php endforeach; ?>
            </select>
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('gp_show_content'); ?>_true">
                <input type="radio" id="<?php echo $this->get_field_id('gp_show_content'); ?>_true" name="<?php echo $this->get_field_name('gp_show_content'); ?>" <?php checked($instance['gp_show_content'], 1); ?> value="1" />
                Show Full Content</label>
            <label for="<?php echo $this->get_field_id('gp_show_content'); ?>_false">
                <input type="radio" id="<?php echo $this->get_field_id('gp_show_content'); ?>_false" name="<?php echo $this->get_field_name('gp_show_content'); ?>" <?php checked($instance['gp_show_content'], 0); ?> value="0" />
                Show only Title</label>
        </p>
        <h3>Connections</h3>
        <p>
            <label for="show_connections">Connections</label>
            <input type="text" id="<?php echo $this->get_field_id('show_connections'); ?>" name="<?php echo $this->get_field_name('show_connections'); ?>" value="<?php echo $instance['show_connections']; ?>" style="width:100%;" />
        </p>
        <p>
            <label for="<?php echo $this->get_field_id('connection_image_size'); ?>">Connection Image Size</label>
            <select id="<?php echo $this->get_field_id('connection_image_size'); ?>" name="<?php echo $this->get_field_name('connection_image_size'); ?>">
                <option <?php
        if ($instance['connection_image_size'] == 'mini')
            echo 'selected="selected" ';
                ?>value="mini">mini</option>
                <option <?php
            if ($instance['connection_image_size'] == 'normal')
                echo 'selected="selected" ';
                ?>value="normal">normal</option>
                <option <?php
            if ($instance['connection_image_size'] == 'bigger')
                echo 'selected="selected" ';
                ?>value="bigger">bigger</option>
            </select>
        </p>
        <?php
    }

    function profiles($instance) {
        // Twitter
        if ($instance['tw_user_id'] != 0) {
            // Check the transient cache for Twitter Profile
            $twitter_profile = new appsolTwitterProfileCache($instance);
            if (!$twitter_profile->cache)
                $twitter_profile->updateCache();
            $this->twitterProfile = $twitter_profile->getCache();
        }
        // Facebook
        if ($instance['fb_user_id'] != 0) {
            // Check the transient cache for Facebook Profile
            $facebook_profile = new appsolFacebookProfileCache($instance);
            if (!$facebook_profile->cache)
                $facebook_profile->updateCache();
            $this->facebookProfile = $facebook_profile->getCache();
        }
        // LinkedIn
        if ($instance['li_user_id'] != 0) {
            // Check the transient cache for LinkedIn Profile
            $linkedin_profile = new appsolLinkedinProfileCache($instance);
            if (!$linkedin_profile->cache)
                $linkedin_profile->updateCache();
            $this->linkedinProfile = $linkedin_profile->getCache();
        }
        // Google+
        if ($instance['gp_user_id'] != 0) {
            // Check the transient cache for Google Profile
            $google_profile = new appsolGoogleplusProfileCache($instance);
            if (!$google_profile->cache)
                $google_profile->updateCache();
            $this->googleplusProfile = $google_profile->getCache();
        }
    }

    function stream($instance) {
        $stream = array();
        // Twitter
        if ($instance['tw_user_id'] != 0) {
            // Check the transient cache for Tweets
            $tweet_stream = new appsolTwitterTweetCache($instance);
            // Add the Tweets to the Stream
            if (is_array($tweet_stream->cache))
                foreach ($tweet_stream->cache as $key => $item) {
                    while (isset($stream[$key])) {
                        $key++;
                    }
                    $stream[$key] = $item;
                }
        }
        // Facebook
        if ($instance['fb_user_id'] != 0) {
            // Check the Transient cache for Posts
            $posts_stream = new appsolFacebookPostCache($instance);
            // Add the Wall Posts to the Stream
            if (is_array($posts_stream->cache))
                foreach ($posts_stream->cache as $key => $item) {
                    while (isset($stream[$key])) {
                        $key++;
                    }
                    $stream[$key] = $item;
                }
        }
        // LinkedIn
        if ($instance['li_user_id'] != 0) {
            // Check the Transient cache for LinkedIn Updates
            $update_stream = new appsolLinkedinUpdatesCache($instance);
            // Add the LinkedIn Updates to the Stream
            if (is_array($update_stream->cache))
                foreach ($update_stream->cache as $key => $item) {
                    while (isset($stream[$key])) {
                        $key++;
                    }
                    $stream[$key] = $item;
                }
        }
        // Google+
        if ($instance['gp_user_id'] != 0) {
            // Check the transient cache for Google+ Posts
            $gplus_activities = new appsolGoogleplusActivitiesCache($instance);
            // Add the Google+ Posts to the Stream
            if (is_array($gplus_activities->cache))
                foreach ($gplus_activities->cache as $key => $item) {
                    while (isset($stream[$key])) {
                        $key++;
                    }
                    $stream[$key] = $item;
                }
        }
        krsort($stream, SORT_NUMERIC);
        return $stream;
    }

    function connections($instance) {
        $connections = array();
        // Twitter
        if ($instance['tw_user_id'] != 0) {
            // Check Transient cache for Twitter Followers
            $twitter_connections = new appsolTwitterFollowerCache($instance);
            // Add Followers to Connections
            if (is_array($twitter_connections->cache))
                $connections = array_merge($connections, $twitter_connections->cache);
        }
        // Facebook
        if ($instance['fb_user_id'] != 0) {
            // Check Transient cache for Facebook Friends
            $facebook_connections = new appsolFacebookFriendCache($instance);
            // Add Friends to Connections
            if (is_array($facebook_connections->cache))
                $connections = array_merge($connections, $facebook_connections->cache);
        }
        // LinkedIn
        if ($instance['li_user_id'] != 0) {
            // Check Transient cache for LinkedIn Contacts
            $linkedin_connections = new appsolLinkedinConnectionsCache($instance);
            // Add Contacts to Connections
            if (is_array($linkedin_connections->cache))
                $connections = array_merge($connections, $linkedin_connections->cache);
        }
        // Google+
        if ($instance['gp_user_id'] != 0) {
            // Check Transient cache for Google+ Circlers
            $googleplus_connections = new appsolGoogleplusCircleCache($instance);
            // Add Circlers to Connections
            if (is_array($googleplus_connections->cache))
                $connections = array_merge($connections, $googleplus_connections->cache);
        }
        // Shuffle together all the connections and return a subset
        shuffle($connections);
        $connections = array_slice($connections, 0, $instance['show_connections'], true);
        return $connections;
    }

}

function appsol_update_social_streams_profiles() {
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

function appsol_update_social_streams_connections() {
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

function appsol_update_social_streams_posts() {
    global $appsol_social_streams_caches;
    $widget_id = $_POST['widgetid'];
    $instance_index = end(explode('-', $widget_id));
    $widget_instances = get_option('widget_appsol-social-streams-widget');
    $updated = false;
    foreach ($appsol_social_streams_caches['stream'] as $network => $stream_caches) {
        foreach ($stream_caches as $stream_cache) {
            $cache = new $stream_cache($widget_instances[$instance_index]);
            if (!$cache->cache) {
                if ($cache->updateCache())
                    $updated = true;
            }
        }
    }
    if ($updated) {
        $stream = appsolSocialStreamsWidget::stream($widget_instances[$instance_index]);
        foreach ($stream as $stream_item) {
            echo $stream_item->display();
        }
    }
    die();
}

function appsol_update_social_streams_galleries() {
//    global $appsol_social_streams_caches;
    _log('appsol_update_social_streams_galleries');
    $networks = array(
        'fb' => 'facebook'
    );
    $user_id = $_POST['user'];
    $network_id = $_POST['network'];
    $gallery = isset($_POST['gallery']) ? $_POST['gallery'] : null;
    _log('Network: ' . $network_id . ' Gallery: ' . $gallery . ' User: ' . $user_id);
    $updated = false;
    foreach ($networks as $network => $network_name) {
        if ($network == $network_id) {
            $params = array($network_id . '_user_id' => $user_id);
            $gallery_cache = 'appsol' . ucfirst($networks[$network_id]) . 'AlbumsCache';
            $cache = new $gallery_cache($params);
            if (!$cache->cache) {
                if ($cache->updateCache())
                    $updated = true;
            }
        }
    }
    if ($updated && $gallery) {
        $gallery_cache = 'appsol' . ucfirst($networks[$network_id]) . 'AlbumCache';
        $params = array(
            $network_id . '_user_id' => $user_id,
            $params[$network_id . '_album_id'] => $gallery
        );
        $album = new $gallery_cache($params);
        _log($album);
        if (!$album->cache) {
            if ($album->updateCache())
                $updated = true;
        }
        $html = '';
//        foreach ($cache->cache as $album)
//            if ($album->id == $gallery) {
        $html.= $album->cache->message;
        foreach ($album->cache->images as $image)
            $image_html.= $image->message;
        $html = str_replace('[GALLERY]', $image_html, $html);
//            }
        echo $html;
    }
    die();
}

function appsol_social_streams_users_ajax() {

    $network = $_POST['network'];
    $network_users = get_option('appsol_social_streams_' . $network . '_users');

    header("Content-Type: application/json");
    echo json_encode(array('network' => $network, 'users' => $network_users));
    die();
}

function appsol_social_streams_galleries_ajax() {
    $network = $_POST['network'];
    $user = $_POST['user'];
    $cache = new appsolFacebookAlbumCache(array('fb_user_id' => $user));
    header("Content-Type: application/json");
    echo json_encode(array('network' => $network, 'user' => $user, 'cache' => $cache->cache));
    die();
}

function appsol_social_streams_cache_ajax() {
    $network = $_POST['network'];
    $cachetype = $_POST['cache'];
    $user = $_POST['user'];
    $update = isset($_POST['update']) ? $_POST['update'] : false;
    _log('Network: ' . $network . ' Cache: ' . $cachetype . ' User: ' . $user . ' Update: ' . $update);
    $network_name = str_replace(
            array('fb', 'tw', 'li', 'gp'), array('facebook', 'twitter', 'linkedin', 'googleplus'), $network
    );
    $class = 'appsol' . ucfirst($network_name) . ucfirst($cachetype) . 'Cache';
    $updated = false;
    if (class_exists($class)) {
        $params = array($network . '_user_id' => $user);
        if ($cachetype = 'album' && isset($_POST['album']))
            $params[$network . '_album_id'] = $_POST['album'];
        $cache = new $class($params);
        if (!$cache->cache && $update) {
            if (!$cache->updateCache())
                $updated = true;
        }
    }
    header("Content-Type: application/json");
    echo json_encode(array('name' => ucfirst($network_name) . ucfirst($cachetype), 'cache' => $cache->cache));
    die();
}

function appsol_get_active_social_streams_widgets() {
    $social_streams_widgets = array();
    $all_widgets = wp_get_sidebars_widgets();
    foreach ($all_widgets as $sidebar => $widgets)
        if ($sidebar != 'wp_inactive_widgets')
            foreach ($widgets as $widget)
                if (strpos($widget, 'appsol-social-streams-widget') !== false)
                    $social_streams_widgets[] = $widget;
    return $social_streams_widgets;
}

function appsol_social_streams_load_widgets() {
//    unregister_widget('appsolSocialPromoWidget');
    register_widget('appsolSocialStreamsWidget');
}

function appsol_social_streams_set_style() {
    wp_enqueue_style('social-streams', WP_PLUGIN_URL . '/' . str_replace(basename(__FILE__), "", plugin_basename(__FILE__)) . 'default.css');
}

function appsol_social_streams_set_script() {
    global $post;
    wp_enqueue_script('appsol_social_streams_ajax', plugins_url('js/social_streams.js', __FILE__), array('jquery'), '', true);
    wp_localize_script('appsol_social_streams_ajax', 'appsolSocialStreams', array('ajaxurl' => admin_url('admin-ajax.php'), 'pluginurl' => plugin_dir_url(__FILE__), 'postid' => $post->ID));
}

function appsol_social_streams_admin_resources() {
    wp_enqueue_style('appsol_social_streams_admin_css');
    wp_enqueue_script('appsol_social_streams_admin_js');
}

add_action('init', 'appsol_social_streams_create_post_types');
add_action('widgets_init', 'appsol_social_streams_load_widgets');
if (is_admin()) { // admin actions
    add_action('admin_menu', 'appsol_social_streams_create_menu');
    add_action('admin_init', 'appsol_social_streams_register_settings');
    add_action('wp_ajax_update_social_streams_posts', 'appsol_update_social_streams_posts');
    add_action('wp_ajax_nopriv_update_social_streams_posts', 'appsol_update_social_streams_posts');
    add_action('wp_ajax_update_social_streams_profiles', 'appsol_update_social_streams_profiles');
    add_action('wp_ajax_nopriv_update_social_streams_profiles', 'appsol_update_social_streams_profiles');
    add_action('wp_ajax_update_social_streams_connections', 'appsol_update_social_streams_connections');
    add_action('wp_ajax_nopriv_update_social_streams_connections', 'appsol_update_social_streams_connections');
    add_action('wp_ajax_update_social_streams_galleries', 'appsol_update_social_streams_galleries');
    add_action('wp_ajax_nopriv_update_social_streams_galleries', 'appsol_update_social_streams_galleries');
    add_action('wp_ajax_appsol_social_streams_users', 'appsol_social_streams_users_ajax');
    add_action('wp_ajax_nopriv_appsol_social_streams_users', 'appsol_social_streams_users_ajax');
    add_action('wp_ajax_appsol_social_streams_cache', 'appsol_social_streams_cache_ajax');
    add_action('wp_ajax_noprive_appsol_social_streams_cache', 'appsol_social_streams_cache_ajax');
    add_action('admin_enqueue_scripts', 'appsol_social_streams_admin_resources');
} else {
    add_action('init', 'appsol_social_streams_set_style');
    add_action('wp_enqueue_scripts', 'appsol_social_streams_set_script');
}

if (is_admin())
    require_once 'appsol_social_streams_options.php';
?>
