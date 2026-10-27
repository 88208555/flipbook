<?php
if(!defined('IN_DISCUZ') || !defined('IN_ADMINCP')) exit('Access Denied');
\flipbook_sync\admin_controller::run(($_GET['view']??'')==='data'?'data':'guide');
