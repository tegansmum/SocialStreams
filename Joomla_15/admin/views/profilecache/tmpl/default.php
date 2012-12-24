<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
<form action="index.php" method="post" name="adminForm">
    <div id="editcell">
        <table class="adminlist">
            <thead>
                <tr>
                    <th width="10%">
                        <?php echo JText::_('Network'); ?>
                    </th>
                    <th width="15%">
                        <?php echo JText::_('Client ID'); ?>
                    </th>
                    <th width="15%">
                        <?php echo JText::_('API Key'); ?>
                    </th>
                    <th width="15%">
                        <?php echo JText::_('API Secret'); ?>
                    </th>
                    <th width="15%">
                        <?php echo JText::_('Authenticate'); ?>
                    </th>
                    <th width="30%">
                        <?php echo JText::_('Message'); ?>
                    </th>
                </tr>            
            </thead>
            <tbody>
                <?php if (is_array($this->cache[$this->network])): ?>
                    <?php foreach ($this->cache[$this->network] as $user => $profile): ?>
                        <tr>
                            <td>
                                <?php echo $profile->id; ?>
                            </td>
                            <td>
                                <?php echo $profile->name; ?>
                            </td>
                            <td>
                                <img src="<?php echo $profile->image; ?>" />
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <input type="hidden" name="option" value="com_socialstreams" />
    <input type="hidden" name="task" value="" />
    <input type="hidden" name="boxchecked" value="0" />
    <input type="hidden" name="controller" value="cache" />
</form>