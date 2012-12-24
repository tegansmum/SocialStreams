<?php
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
//$params = $this->form->getFieldsets('params');
?>
<form action="<?php echo JRoute::_('index.php?option=com_socialstreams&view=socialstream&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="site-form" class="form-validate">
    <div class="width-60 fltlft">
        <fieldset class="adminform">
            <legend><?php echo JText::_('COM_SOCIALSTREAMS_AUTH_DETAILS'); ?></legend>
            <ul class="adminformlist">
                <?php foreach ($this->form->getFieldset('details') as $field): ?>
                    <?php if (!$field->hidden): ?>
                        <li><?php
                echo $field->label;
                echo $field->input;
                        ?></li>
                    <?php else: ?>
                        <?php echo $field->input; ?>
                    <?php endif; ?>
                <?php endforeach; ?>
                <?php if ($this->form->getValue('network') && $this->form->getValue('clientid')): ?>
                    <li><?php
                echo $this->form->getLabel('access_token', 'request');
                echo $this->form->getInput('access_token', 'request');
                    ?></li>
                <?php endif; ?>
            </ul>
        </fieldset>
    </div>
    <div class="width-40 fltrt">
        <?php
        echo JHtml::_('sliders.start', 'socialstream-slider');
        $fieldset = $this->form->getFieldset('publish');
        echo JHtml::_('sliders.panel', JText::_('COM_SOCIALSTREAMS_GROUP_LABEL_PUBLISHING_DETAILS'), 'socialstream-publish');
        if (isset($fieldset->description) && trim($fieldset->description)):
            ?>
            <p class="tip"><?php echo $this->escape(JText::_($fieldset->description)); ?></p>
        <?php endif; ?>
        <fieldset class="panelform" >
            <ul class="adminformlist">
                <?php foreach ($fieldset as $field): ?>
                    <li><?php echo $field->label; ?><?php echo $field->input; ?></li>
                <?php endforeach; ?>
            </ul>
        </fieldset>

        <?php echo JHtml::_('sliders.end'); ?>
    </div>
    <div>
        <input type="hidden" name="task" value="socialstream.edit" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>
