<?php

/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */
class appsolSocialStreamProfile {

    public $id = '';
    public $network = '';
    public $user = '';
    public $name = '';
    public $link = '';
    public $image = '';

    public function setProfile($profile) {
        return true;
    }

    public function display() {
        $html = '';
        $html.= '<li class="connection ' . $this->network . '">';
        $html.= '<a href="' . $this->link . '" title="' . $this->name . '" rel="nofollow" target="_blank">';
        $html.= '<img src="' . $this->image . '" alt="' . $this->name . '" width="48px" height="48px" />';
        $html.= '<span class="social-network-icon"></span></a></li>';
        return $html;
    }

}

class appsolFacebookProfile extends appsolSocialStreamProfile {

    public function __construct() {
        $this->network = 'facebook';
    }

    public function setProfile($profile) {
        $profile_image = 'http://graph.facebook.com/' . $profile['id'] . '/picture?type=square';
        $this->id = $profile['id'];
        $this->user = $profile['id'];
        $this->name = $profile['name'];
        $this->link = $profile['link'];
        $this->image = $profile_image;
    }

}

class appsolTwitterProfile extends appsolSocialStreamProfile {

    public function __construct() {
        $this->network = 'twitter';
    }

    public function setProfile($profile) {
        $this->id = $profile->id_str;
        if (isset($profile->name)) {
            $this->user = $profile->screen_name;
            $this->name = $profile->name;
            $this->link = 'http://twitter.com#!/' . $profile->screen_name;
            $this->image = str_ireplace(array('_bigger', '_mini', '_original'), '_normal', $profile->profile_image_url);
        }
    }

}

class appsolLinkedinProfile extends appsolSocialStreamProfile {

    public function __construct() {
        $this->network = 'linkedin';
    }

    public function setProfile($profile) {
        $this->id = $profile->id;
        $this->user = $profile->id;
        $this->name = $profile->firstName . ' ' . $profile->lastName;
        $this->link = isset($profile->publicProfileUrl) ? $profile->publicProfileUrl : 'https://www.linkedin.com/';
        $this->image = isset($profile->pictureUrl) ? $profile->pictureUrl : 'http://s4.licdn.com/scds/common/u/img/icon/icon_no_photo_50x50.png';
    }

}

class appsolGooglePlusProfile extends appsolSocialStreamProfile {

    public function __construct() {
        $this->network = 'gplus';
    }

    public function setProfile($profile) {
        $this->id = $profile['id'];
        $this->user = $profile['id'];
        $this->name = $profile['displayName'];
        $this->link = $profile['url'];
        $this->image = $profile['image']['url'];
    }

}
?>
