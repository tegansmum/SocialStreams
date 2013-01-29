<?php
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

class appsolSocialStreamsWidget extends WP_Widget {

    private $instance = null;
    private $accounts = array();

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
        $this->accounts = SocialStreamsHelper::getAuthenticatedAccounts();
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
        <?php //if (!is_admin()) $this->profiles($this->instance); ?>
        <div class="social-metrics">
            <ul>
                <?php foreach ($this->profiles($this->instance) as $account): ?>
                    <?php $stats = $account->getStats(); ?>
                    <li class="social-metric <?php echo $account->network; ?>">
                        <a title="<?php echo $account->getConnectVerb(); ?> <?php echo $account->name; ?> on <?php echo $account->nicename; ?>" class="profile-link" href="<?php echo $account->url ?>" rel="nofollow"><span class="metric-count"><?php echo $stats['count']; ?></span> <?php echo $stats['name']; ?></a>
                    </li>
                <?php endforeach; ?>
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
                socialStreams.Init('<?php echo $this->id; ?>')
            })
            /* ]]> */
        </script>
        <?php
        echo $after_widget;
    }

    function update($new_instance, $old_instance) {
        $instance = array();
        $instance['title'] = strip_tags($new_instance['title']);
        $instance['show_connections'] = strip_tags($new_instance['show_connections']);
        if ($instance['show_connections'] === '')
            $instance['show_connections'] = 0;
        $instance['connection_image_size'] = $new_instance['connection_image_size'];
        foreach ($this->accounts as $account)
            $instance[$account['network'] . '_' . $account['clientid']] = $new_instance[$account['network'] . '_' . $account['clientid']];

        return $instance;
    }

    function form($instance) {
        $defaults = array(
            'title' => 'Social Activity',
            'show_connections' => 6,
            'connection_image_size' => 'normal',
        );
        foreach ($this->accounts as $account)
            $defaults[$account['network'] . '_' . $account['clientid']] = 0;

        $instance = wp_parse_args((array) $instance, $defaults);
        $this->instance = $instance;
        ?>
        <p>
            <label for="<?php echo $this->get_field_id('title'); ?>">Title:</label>
            <input type="text" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" value="<?php echo $instance['title']; ?>" style="width:100%;" />
        </p>

        <h3>Accounts</h3>
        <?php foreach ($this->accounts as $account): ?>
            <?php if ($profile = SocialStreamsProfileCache::getProfiles($account['network'], $account['clientid'], $account['clientid'])): ?>
                <p>
                    <label for="<?php echo $this->get_field_id($account['network'] . '_' . $account['clientid']); ?>">
                        <input type="checkbox" id="<?php echo $this->get_field_id($account['network'] . '_' . $account['clientid']); ?>_true" name="<?php echo $this->get_field_name($account['network'] . '_' . $account['clientid']); ?>" <?php checked($instance[$account['network'] . '_' . $account['clientid']], 1); ?> value="1" />
                        <?php echo $profile->nicename . ' ' . $profile->name ?></label>
                </p>
            <?php endif; ?>
        <?php endforeach; ?>
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
        $profiles = array();
        $accounts = SocialStreamsHelper::getAuthenticatedAccounts();
        foreach ($accounts as $account)
            if (!empty($instance[$account['network'] . '_' . $account['clientid']]))
                $profiles[$account['clientid']] = SocialStreamsProfileCache::getProfiles($account['network'], $account['clientid'], $account['clientid']);
        return $profiles;
    }

    function stream($instance) {
        $stream = array();
        $accounts = SocialStreamsHelper::getAuthenticatedAccounts();
        SocialStreamsHelper::log($instance);
        foreach ($accounts as $account) {
            if (!empty($instance[$account['network'] . '_' . $account['clientid']])) {
                $cache = SocialStreamsItemCache::getItems($account['network'], $account['clientid']);
                foreach ($cache as $item) {
                    $item->wraptag = 'li';
                    $published = strtotime($item->published);
                    while (isset($stream[$published]))
                        $published++;
                    $stream[$published] = $item;
                }
            }
        }
        SocialStreamsHelper::log($stream);
        krsort($stream, SORT_NUMERIC);
        return $stream;
    }

    function connections($instance) {
        $connections = array();
        $accounts = SocialStreamsHelper::getAuthenticatedAccounts();
        foreach ($accounts as $account) {
            if (!empty($instance[$account['network'] . '_' . $account['clientid']])) {
                $cache = SocialStreamsProfileCache::getProfiles($account['network'], $account['clientid']);
                $connections = array_merge($connections, $cache);
            }
        }

        // Shuffle together all the connections and return a subset
        shuffle($connections);
        $connections = array_slice($connections, 0, $instance['show_connections'], true);
        return $connections;
    }

}
?>
