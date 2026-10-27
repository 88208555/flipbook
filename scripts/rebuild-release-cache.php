<?php
if(PHP_SAPI !== 'cli') exit(1);
function rebuildReleaseCache() {
$complete = false;
register_shutdown_function(static function() use (&$complete) {
    if(!$complete) { fwrite(STDERR, "Application cache rebuild did not finish.\n"); exit(1); }
});
chdir('/var/www/html');
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTP_HOST'] = parse_url(getenv('SITE_URL') ?: 'http://localhost', PHP_URL_HOST) ?: 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';
require './source/class/class_core.php';
$app = C::app();
$app->init_user = $app->init_session = $app->init_cron = $app->init_misc = $app->init_mobile = false;
$app->init();
require_once libfile('function/cache');
updatecache();
$complete = true;
echo "Application cache rebuilt.\n";
}
rebuildReleaseCache();
