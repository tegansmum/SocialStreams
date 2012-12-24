<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
$session = & JFactory::getSession();
?>
<pre>
<?php print_r($this->cache); ?>
</pre>
<p>Facebook: <?php echo $session->get('facebook_last_msg', '', 'socialstreams'); ?></p>
<p>Twitter: <?php echo $session->get('twitter_last_msg', '', 'socialstreams'); ?></p>
<p>Stumbleupon: <?php echo $session->get('stumbleupon_last_msg', '', 'socialstreams'); ?></p>