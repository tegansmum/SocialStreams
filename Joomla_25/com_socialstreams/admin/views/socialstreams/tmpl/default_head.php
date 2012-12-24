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
    <th width="auto">
        <?php echo JHtml::_('grid.sort', 'COM_SOCIALSTREAMS_ACCOUNT_HEADING_NETWORK', 'a.network', $listDirn, $listOrder); ?>
    </th>
    <th width="5%">
        <?php echo JHtml::_('grid.sort', 'JSTATUS', 'a.state', $listDirn, $listOrder); ?>
    </th>
    <th width="5%">
        <?php echo JHtml::_('grid.sort', 'COM_SOCIALSTREAMS_ACCOUNT_HEADING_EXPIRES', 'a.expires', $listDirn, $listOrder); ?>
    </th>
    <th width="10%">
        <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_CREATED_BY', 'a.created_by', $listDirn, $listOrder); ?>
    </th>
    <th width="5%">
        <?php echo JHtml::_('grid.sort', 'JDATE', 'a.created', $listDirn, $listOrder); ?>
    </th>
    <th width="1%" class="nowrap">
        <?php echo JHtml::_('grid.sort', 'JGRID_HEADING_ID', 'a.id', $listDirn, $listOrder); ?>
    </th>			
</tr>