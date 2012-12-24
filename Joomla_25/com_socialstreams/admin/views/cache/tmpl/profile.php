<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
//if (is_array($this->cache))
//    foreach ($this->cache as $item)
//        print_r($item);
?>
<h4>Profiles</h4>
<form action="index.php" method="post">
    <label for="profile"><input checked="checked" type="radio" name="cachetype" id="profile" value="profile" />Profile</label>
    <label for="item"><input type="radio" name="cachetype" id="item" value="item" />Item</label>

    <label for="facebook"><input checked="checked" type="radio" name="network" id="facebook" value="facebook" />Facebook</label>
    <label for="twitter"><input type="radio" name="network" id="twitter" value="twitter" />Twitter</label>

    <input type="submit" name="submit">Update</button>

    <input type="hidden" name="option" value="com_socialstreams" />
    <input type="hidden" name="controller" value="cache" />
    <input type="hidden" name="task" value="update" />
</form>
<?php if (is_array($this->cache[$this->network])): ?>
    <ul>
        <?php foreach ($this->cache[$this->network] as $profile): ?>
            <li>
                <h4><?php echo $profile->name; ?></h4>
                <div class="image">
                    <a href="<?php echo $profile->link; ?>" target="_blank">
                        <img src="<?php echo $profile->image; ?>" alt="<?php echo $profile->name; ?> profile image" title="<?php echo $profile->name; ?>" />
                    </a>
                </div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
