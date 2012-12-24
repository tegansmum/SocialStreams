<?php
defined('_JEXEC') or die('Restricted Access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$user = JFactory::getUser();
$userId = $user->get('id');
$listOrder = $this->escape($this->state->get('list.ordering'));
$listDirn = $this->escape($this->state->get('list.direction'));
$canOrder = $user->authorise('core.edit.state', 'com_socialstreams');
$saveOrder = $listOrder == 'ordering';
$params = (isset($this->state->params)) ? $this->state->params : new JObject();
?>
<?php
foreach ($this->items as $i => $item):
    $ordering = ($listOrder == 'ordering');
//    $item->cat_link = JRoute::_('index.php?option=com_categories&extension=com_visitmanager&task=edit&type=other&cid[]=' . $item->catid);
    $canCreate = $user->authorise('core.create', 'com_socialstreams.' . $item->id);
    $canEdit = $user->authorise('core.edit', 'com_socialstreams.' . $item->id);
    $canCheckin = $user->authorise('core.manage', 'com_checkin') || $item->checked_out == $userId || $item->checked_out == 0;
    $canChange = $user->authorise('core.edit.state', 'com_socialstreams.' . $item->id) && $canCheckin;
    ?>
    <tr class="row<?php echo $i % 2; ?>">
        <td class="center">
            <?php echo JHtml::_('grid.id', $i, $item->id); ?>
        </td>
        <td>
            <?php echo $this->escape($item->network); ?>
        </td>
        <td>
            <?php echo is_object($item->item)? $this->escape($item->item->profile->name) : 'anon'; ?>
        </td>
        <td>
            <?php echo is_object($item->item)? $item->item->display() : ''; ?>
        </td>
        <td class="center nowrap">
            <?php echo JHtml::_('date', $item->published, JText::_('DATE_FORMAT_LC4')); ?>
        </td>
        <td class="center nowrap">
            <?php echo JHtml::_('date', $item->expires, JText::_('DATE_FORMAT_LC4')); ?>
        </td>
        <td class="center nowrap">
            <?php echo JHtml::_('date', $item->created, JText::_('DATE_FORMAT_LC4')); ?>
        </td>
        <td class="center">
            <?php echo $item->id; ?>
        </td>
    </tr>
<?php endforeach; ?>