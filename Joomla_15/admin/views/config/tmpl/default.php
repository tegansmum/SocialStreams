<?php
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
                <?php if (is_array($this->social_networks)): ?>
                    <?php foreach ($this->social_networks as $network => $values): ?>
                        <?php $callback = JRoute::_('index.php?option=com_socialstreams&controller=config&task=setaccess&network=' . $network); ?>
                        <tr>
                            <td>
                                <?php echo $values['name']; ?>
                            </td>
                            <td>
                                <?php if (isset($values['clientid'])): ?>
                                    <input type="text" name="<?php echo $network; ?>_clientid" value="<?php echo $values['clientid']; ?>" />
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($values['apikey'])): ?>
                                    <input type="text" name="<?php echo $network; ?>_apikey" value="<?php echo $values['apikey']; ?>" />
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (isset($values['apisecret'])): ?>
                                    <input type="text" name="<?php echo $network; ?>_apisecret" value="<?php echo $values['apisecret']; ?>" />
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($values['state'] == 'not-authenticated'): ?>
                                    <a class="button" href="<?php echo $values['loginurl']; ?>">Authenticate with <?php echo $values['name']; ?></a>
                                <?php elseif ($values['state'] == 'authenticated'): ?>
                                    Authenticated as <?php echo $values['user']->name; ?> <a class="button" href="<?php echo $values['logouturl']; ?>">Revoke <?php echo $values['name']; ?> Authentication</a>
                                <?php else: ?>
                                    Save <?php echo $values['name']; ?> details before authenticating
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php echo $values['message']; ?>
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
    <input type="hidden" name="controller" value="config" />
</form>