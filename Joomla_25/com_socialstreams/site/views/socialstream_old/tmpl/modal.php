<?php
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

?>
<script type="text/javascript">alert('Callback: <?php echo $this->escape($this->function); ?>(<?php echo $this->network; ?>,<?php echo $this->success; ?>)'); if (window.parent) window.parent.<?php echo $this->escape($this->function); ?>('<?php echo $this->network; ?>', '<?php echo $this->success; ?>');</script> 