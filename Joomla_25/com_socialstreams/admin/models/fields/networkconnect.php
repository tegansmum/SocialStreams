<?php

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
jimport('joomla.form.formfield');

/**
 * Book form field class
 */
class JFormFieldNetworkConnect extends JFormField {

    /**
     * field type
     * @var string
     */
    protected $type = 'networkconnect';

    /**
     * Method to get the field input markup
     */
    protected function getInput() {
        // Load modal behavior
        JHtml::_('behavior.modal', 'a.modal');

        $rowid = JRequest::getInt('id', 0);

        // Build the script
        $script = array();
        $jparams = JComponentHelper::getParams('com_socialstreams');
        $networks = explode(',', $jparams->get('networks'));
        $script[] = "\nvar requestUrls = {";
        $urls = array();
        foreach ($networks as $network) {
            $urls[] = '   ' . $network . ': "' . 'index.php?option=com_socialstreams&view=socialstream&layout=edit&task=socialstream.setauth&network=' . $network . '&id=' . $rowid . '"';
        }
        $script[] = implode(",\n", $urls);
        $script[] = '}';
        $script[] = 'window.addEvent("domready", function(){';
        $script[] = '   var network = document.getElementById("jform_network").value';
        $script[] = '   if(network != 0){';
        $script[] = '       networkConnect = document.getElementById("' . $this->id . '_network_connect")';
        $script[] = '       networkConnect.setAttribute("href", requestUrls[network])';
        $script[] = "   }";
        $script[] = "})\n";

        // Add to document head
        JFactory::getDocument()->addScriptDeclaration(implode("\n", $script));
        // Setup variables for display
        $html = array();

        $title = JText::_('COM_VISITMANAGER_NETWORK_CONNECT_TITLE');

        $title = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');

        // The Network Authenticate button
        $html[] = '<div class="button2-left">';
        $html[] = '  <div class="blank">';
        $html[] = '    <a id="' . $this->id . '_network_connect" title="' . JText::_('COM_VISITMANAGER_NETWORK_CONNECT_TITLE') . '" href="#" >' . JText::_('COM_VISITMANAGER_NETWORK_CONNECT_TITLE') . '</a>';
        $html[] = '  </div>';
        $html[] = '</div>';

        // The current Auth Token if we have one
        if (!$this->value) {
            $value = '';
        } else {
            $value = $this->value;
        }

        return implode("\n", $html);
    }

}

?>
