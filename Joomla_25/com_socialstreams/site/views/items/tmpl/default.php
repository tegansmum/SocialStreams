<?php
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$app = JFactory::getApplication();
$menu = $app->getMenu();
$title = 'Social Activity Updates';
if($menuItem = $menu->getActive())
    $title = $menuItem->title;
?>
<h1><?php echo $title; ?></h1>
<div class="stream">
    <ul>
    <?php foreach($this->items as $item): ?>
    <?php echo $item->item->display(); ?>
    <?php endforeach; ?>
    </ul>
</div>

<div class="item-pagination pagination-container">
    <p class="counter">
                    <?php echo $this->pagination->getPagesCounter(); ?>
    </p>
    <?php echo $this->pagination->getPagesLinks(); ?>
</div>
