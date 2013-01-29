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
                echo $stream_item->item->display();
            ?>
        </ul>
    </div>
<?php endif; ?>
<div class="social-metrics">
    <ul>
        <?php foreach ($profiles as $account): ?>
            <?php $stats = $account->getStats(); ?>
            <li class="social-metric <?php echo $account->network; ?>">
                <a title="<?php echo $account->getConnectVerb(); ?> <?php echo $account->name; ?> on <?php echo $account->nicename; ?>" class="profile-link" href="<?php echo $account->url ?>" rel="nofollow"><span class="metric-count"><?php echo $stats['count']; ?></span> <?php echo $stats['name']; ?></a>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
<?php if (is_array($connections)): ?>
    <div class="connections">
        <ul>
            <?php
            foreach ($connections as $connection)
                echo $connection->profile->display();
            ?>
        </ul>
    </div>
<?php endif; ?>