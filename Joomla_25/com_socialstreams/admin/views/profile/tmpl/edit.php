<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
JHtml::_('behavior.tooltip');
JHtml::_('behavior.formvalidation');
//$params = $this->form->getFieldsets('params');
?>
<form action="<?php echo JRoute::_('index.php?option=com_socialstreams&view=profile&layout=edit&id=' . (int) $this->item->id); ?>"
      method="post" name="adminForm" id="site-form" class="form-validate">
    <div class="width-60 fltlft">
        <fieldset class="adminform">
            <legend><?php echo JText::_('COM_SOCIALSTREAMS_PROFILE_DETAILS'); ?></legend>
            <ul class="adminformlist">
                <li><?php
echo $this->form->getLabel('id');
echo $this->form->getInput('id');
                ?></li>
                <li><?php
                    echo $this->form->getLabel('network');
                    echo $this->form->getInput('network');
                ?></li>
                <li><?php
                    echo $this->form->getLabel('name');
                    echo $this->form->getInput('name');
                ?></li>
                <li>
                    <?php echo $this->form->getLabel('user'); ?>
                    <a class="inputbox readonly" href="<?php echo $this->form->getValue('url'); ?>"><?php echo $this->form->getValue('user'); ?></a>
                </li>
                <li>
                    <?php echo $this->form->getLabel('image'); ?>
                    <img src="<?php echo $this->form->getValue('image'); ?>" />
                </li>
                <?php if ($this->form->getValue('profile')): ?>
                    <li>
                        <pre><?php echo $this->form->getValue('profile'); ?></pre>
                    </li>
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
        <input type="hidden" name="task" value="profile.edit" />
        <?php echo JHtml::_('form.token'); ?>
    </div>
</form>