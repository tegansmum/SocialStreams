<?php
defined('_JEXEC') or die('Restricted Access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$user		= JFactory::getUser();
$userId		= $user->get('id');
$listOrder	= $this->escape($this->state->get('list.ordering'));
$listDirn	= $this->escape($this->state->get('list.direction'));
$canOrder	= $user->authorise('core.edit.state', 'com_socialstreams');
$saveOrder	= $listOrder=='ordering';
?>
<tr>   
    <th width="1%">
        <input type="checkbox" name="checkall-toggle" value="" title="<?php echo JText::_('JGLOBAL_CHECK_ALL'); ?>" onclick="Joomla.checkAll(this)" />
    </th>
    <th width="5%">
        <?php echo JHtml::_('grid.sort', 'COM_SOCIALSTREAMS_ACCOUNT_HEADING_NETWORK', 'i.network', $listDirn, $listOrder); ?>
    </th>
    <th width="10%">
        <?php echo JHtml::_('grid.sort', 'COM_SOCIALSTREAMS_PROFILE_NAME', 'p.name', $listDirn, $listOrder); ?>
    </th>
    <th width="auto">
        <?php echo JText::_('COM_SOCIALSTREAMS_ITEM_EXTRACT'); ?>
    </th>
    <th width="5%">
        <?php echo JHtml::_('grid.sort', 'COM_SOCIALSTREAMS_ITEMS_HEADING_PUBLISHED', 'i.published', $listDirn, $listOrder); ?>
    </th>
    <th width="5%">
        <?php echo JHtml::_('grid.sort', 'COM_SOCIALSTREAMS_ACCOUNT_HEADING_EXPIRES', 'i.expires', $listDirn, $listOrder); ?>
    </th>
    <th width="5%">
        <?php echo JHtml::_('grid.sort', 'JDATE', 'i.created', $listDirn, $listOrder); ?>
    </th>
    <th width="1%" class="nowrap">
        <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'i.id', $listDirn, $listOrder); ?>
    </th>			
</tr>