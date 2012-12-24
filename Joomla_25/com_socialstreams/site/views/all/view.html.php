<?php

// no direct access

defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
jimport('joomla.application.component.view');

/**
 * Description of SocialcountViewSocialcount
 *
 * @author stuart
 */
class SocialcountViewAll extends JView {

    function display($tpl = null) {
        global $option;
        $model = &$this->getModel();
        $list = $model->getList();
        $response = "Hello World!";
        for ($i = 0; $i < count($list); $i++) {
            $row = & $list[$i];
            $row->link = JRoute::_('index.php?option=' . $option .
                            '&id=' . $row->id . '&view=review');
        }
        $this->assignRef('list', $list);
        parent::display($tpl);
    }

}

?>
