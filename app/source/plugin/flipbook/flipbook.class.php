<?php
if(!defined('IN_DISCUZ')) exit('Access Denied');

class plugin_flipbook {
 public function template($context) {
  return \flipbook\admin_navigation::template($context);
 }
}
