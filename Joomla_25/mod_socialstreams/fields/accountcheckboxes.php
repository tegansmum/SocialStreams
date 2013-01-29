<?php

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

// import the list field type
jimport('joomla.form.helper');
jimport('joomla.form.formfield');

class JFormFieldAccountCheckboxes extends JFormField {

    /**
     * The form field type.
     *
     * @var    string
     * @since  11.1
     */
    protected $type = 'accountcheckboxes';

    /**
     * Flag to tell the field to always be in multiple values mode.
     *
     * @var    boolean
     * @since  11.1
     */
    protected $forceMultiple = true;

    /**
     * Method to get the field input markup for check boxes.
     *
     * @return  string  The field input markup.
     *
     * @since   11.1
     */
    protected function getInput() {
        $html = array();

        // Initialize some field attributes.
        $class = $this->element['class'] ? ' class="checkboxes ' . (string) $this->element['class'] . '"' : ' class="checkboxes"';
        $checkedOptions = explode(',', (string) $this->element['checked']);

        // Start the checkbox field output.
        $html[] = '<fieldset id="' . $this->id . '"' . $class . '>';

        // Get the field options.
        $options = $this->getOptions();

        // Build the checkbox field output.
        $html[] = '<ul>';
        foreach ($options as $i => $option) {
            // Initialize some option attributes.
            if (!isset($this->value) || empty($this->value)) {
                $checked = (in_array((string) $option->value, (array) $checkedOptions) ? ' checked="checked"' : '');
            } else {
                $value = !is_array($this->value) ? explode(',', $this->value) : $this->value;
                $checked = (in_array((string) $option->value, $value) ? ' checked="checked"' : '');
            }
            $class = !empty($option->class) ? ' class="' . $option->class . '"' : '';
            $disabled = !empty($option->disable) ? ' disabled="disabled"' : '';

            // Initialize some JavaScript option attributes.
            $onclick = !empty($option->onclick) ? ' onclick="' . $option->onclick . '"' : '';

            $html[] = '<li>';
            $html[] = '<input type="checkbox" id="' . $this->id . $i . '" name="' . $this->name . '"' . ' value="'
                    . htmlspecialchars($option->value, ENT_COMPAT, 'UTF-8') . '"' . $checked . $class . $onclick . $disabled . '/>';

            $html[] = '<label for="' . $this->id . $i . '"' . $class . '>' . JText::_($option->text) . '</label>';
            $html[] = '</li>';
        }
        $html[] = '</ul>';

        // End the checkbox field output.
        $html[] = '</fieldset>';

        return implode($html);
    }

    /**
     * Method to get the field options.
     *
     * @return  array  The field option objects.
     *
     * @since   11.1
     */
    protected function getOptions() {
        JLoader::import('components.com_socialstreams.helpers.socialstreams', JPATH_ADMINISTRATOR);
        $networks = SocialStreamsHelper::getAuthenticatedNetworks();
        $options = array();
        foreach ($networks as $network) {
            // Create a new option object based on the <option /> element.
            $tmp = JHtml::_(
                            'select.option', (string) $network['id'], (string) $network['name'], 'value', 'text', empty($network['access_token'])
            );
            $options[] = $tmp;
        }

        return $options;
    }

}

?>
