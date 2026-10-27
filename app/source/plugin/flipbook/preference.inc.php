<?php
if(!defined('IN_DISCUZ'))exit('Access Denied');
// Existing bookmarks resolve to the member page; reading uses fixed defaults.
dheader('location:'.flipbook\front_helper::url('micro',['key'=>'mine']));
exit;
