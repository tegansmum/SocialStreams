<?php
// no direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
<?php if (is_array($stream)): ?>
    <div  class="stream">
        <ul>
            <?php
            foreach ($stream as $stream_item)
                echo $stream_item->display();
            ?>
        </ul>
    </div>
<?php endif; ?>
<div class="social-metrics">
    <ul>
        <?php if (isset($facebook_user->name)): ?>
            <li class="social-metric facebook">
                <a title="Friend <?php echo $facebook_user->name; ?> on Facebook" class="profile-link" href="<?php echo $facebook_user->link ?>" rel="nofollow"><span class="metric-count"><?php echo $facebook_friends; ?></span> Friends</a>
            </li>
        <?php endif; ?>
        <?php if (isset($twitter->user->name)): ?>
            <li class="social-metric twitter">
                <a title="Follow <?php echo $twitter->user->name; ?> on Twitter" class="profile-link" href="<?php echo $twitter->user->link; ?>" rel="nofollow"><span class="metric-count"><?php echo $twitter_followers; ?></span> Followers</a>
            </li>
        <?php endif; ?>
        <?php if (isset($linkedin->user->name)): ?>
            <li class="social-metric linkedin">
                <a title="Connect with <?php echo $linkedin->user->name; ?> on LinkedIn" class="profile-link" href="<?php echo $linkedin->user->link; ?>" rel="nofollow"><span class="metric-count"><?php echo $linkedin_connections; ?></span> Connections</a>
            </li>
        <?php endif; ?>
        <?php if (isset($google->user->name)): ?>
            <li class="social-metric gplus">
                <a title="Add <?php echo $google->user->name; ?> to your Google+ Circles" class="profile-link" href="<?php echo $google->user->link; ?>" rel="nofollow"> In <span class="metric-count"><?php echo $google_circles; ?></span> Circles</a>
            </li>
        <?php endif; ?>
    </ul>
</div>
<?php if (is_array($connections)): ?>
<div class="connections">
    <ul>
        <?php
        foreach ($connections as $connection)
            echo $connection->display();
        ?>
    </ul>
</div>
<?php endif; ?>