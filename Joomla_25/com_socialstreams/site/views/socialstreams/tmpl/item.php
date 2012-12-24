<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
?>
<<?php echo$this->wraptag; ?> class="stream-item <?php echo $this->source['class']; ?>>
    <span class="profile-image">
        <a href="<?php echo$this->profile->link; ?>" rel="nofollow" target="_blank" title="<?php echo $this->profile->name; ?>'s <?php echo $this->source['name']; ?> Profile">
            <img width="48px" height="48px" src="<?php echo $this->profile->image; ?>" />
        </a>
    </span>
    <span class="message"><?php echo $this->loadTemplate($this->network); ?></span>';
        $html.= '<span class="meta">Posted <span class="post-date">' . $this->display_date . '</span> by <a class="profile-name" href="' . $this->profile->link . '" rel="nofollow">' . $this->profile->name . '</a></span>';
        $html.= '<span class="actions">';
        foreach ($this->actions as $action)
            $html.= '<span class="action">' . $action . '</span>';
        $html.= '</span>';
</<?php echo$this->wraptag; ?>>