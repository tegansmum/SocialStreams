<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
<ul>
    <?php foreach ($this->list as $item): ?>
        <li>
            <a href="<?php echo $item->link; ?>"><?php echo $item->network; ?></a>
        </li>
    <?php endforeach; ?>
</ul>