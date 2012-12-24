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
            <?php if ($item->checked_out) : ?>
                <?php echo JHtml::_('jgrid.checkedout', $i, $item->editor, $item->checked_out_time, 'socialstream.', $canCheckin); ?>
            <?php endif; ?>
            <?php if ($canEdit) : ?>
                <a href="<?php echo JRoute::_('index.php?option=com_socialstreams&task=socialstream.edit&id=' . (int) $item->id); ?>">
                    <?php echo $this->escape($item->network); ?></a>
            <?php else : ?>
                <?php echo $this->escape($item->network); ?>
            <?php endif; ?>
        </td>
        <td class="center">
            <?php echo JHtml::_('jgrid.published', $item->state, $i, 'socialstreams.', $canChange, 'cb'); ?>
        </td>
        <td class="center nowrap">
            <?php echo JHtml::_('date', $item->expires, JText::_('DATE_FORMAT_LC4')); ?>
        </td>
        <td>
            <?php echo $this->escape($item->author_name); ?>
        </td>
        <td class="center nowrap">
            <?php echo JHtml::_('date', $item->created, JText::_('DATE_FORMAT_LC4')); ?>
        </td>
        <td class="center">
            <?php echo $item->id; ?>
        </td>
    </tr>
<?php endforeach; ?>