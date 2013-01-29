<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
JHtml::addIncludePath(JPATH_COMPONENT . '/helpers/html');
JHtml::_('behavior.tooltip');
//JHtml::_('behavior.multiselect');
JLoader::import('components.com_socialstreams.helpers.socialstreams', JPATH_ADMINISTRATOR);
?>
<form action="<?php echo JRoute::_('index.php?option=com_socialstreams&view=items'); ?>" method="post" name="adminForm" id="adminForm">
    <fieldset id="filter-bar">
        <div class="filter-search fltlft">
            <label class="filter-search-lbl" for="filter_search"><?php echo JText::_('JSEARCH_FILTER_LABEL'); ?></label>
            <input type="text" name="filter_search" id="filter_search" value="<?php echo $this->escape($this->state->get('filter.search')); ?>" title="<?php echo JText::_('COM_SOCIALSTREAMS_SEARCH_IN_TITLE'); ?>" />
            <button type="submit"><?php echo JText::_('JSEARCH_FILTER_SUBMIT'); ?></button>
            <button type="button" onclick="document.id('filter_search').value='';this.form.submit();"><?php echo JText::_('JSEARCH_FILTER_CLEAR'); ?></button>
        </div>
        <div class="filter-select fltrt">
            <select name="filter_account" class="inputbox" onchange="this.form.submit()">
                <option value=""><?php echo JText::_('COM_SOCIALSTREAMS_SELECT_NETWORK'); ?></option>
                <?php echo JHtml::_('select.options', SocialStreamsHelper::getAuthenticatedNetworks(), 'id', 'name', $this->state->get('filter.account'), true); ?>
            </select>
        </div>
    </fieldset>
    <div class="clr"> </div>
    <table class="adminlist">
        <thead><?php echo $this->loadTemplate('head'); ?></thead>
        <tfoot><?php echo $this->loadTemplate('foot'); ?></tfoot>
        <tbody><?php echo $this->loadTemplate('body'); ?></tbody>
    </table>
    <div>
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="boxchecked" value="0" />
        <input type="hidden" name="filter_order" value="<?php echo $listOrder; ?>" />
        <input type="hidden" name="filter_order_Dir" value="<?php echo $listDirn; ?>" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>