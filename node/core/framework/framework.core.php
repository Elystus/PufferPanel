<?php

/*
 * PufferPanel Node Core Framework File
 */

/*
 * Cloudflare IP Fix
 */
$_SERVER['REMOTE_ADDR'] = (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) ? $_SERVER['HTTP_CF_CONNECTING_IP'] : $_SERVER['REMOTE_ADDR'];

/* 
 * Include Required Global Framework Files
 */
require_once('framework.database.connect.php');
require_once('framework.auth.php');
require_once('framework.settings.php');
require_once('framework.user.php');
require_once('framework.page.php');
require_once('framework.folder.php');
require_once('framework.server.php');


/*
 * Include Email Sending Files
 */
require_once('email/core.email.php');

/*
 * Initalize Global Framework
 */
$core = new stdClass();
$core->framework = new stdClass();

/*
 * Initalize Frameworks
 */
$core->framework->settings = new getSettings();
$core->framework->auth = new auth();
$core->framework->user = new user($_SERVER['REMOTE_ADDR'], $core->framework->auth->getCookie('pp_auth_token'), $core->framework->auth->getCookie('pp_server_hash'));
$core->framework->page = new page();
$core->framework->email = new tplMail($core->framework->settings);
$core->framework->server = new server($core->framework->auth->getCookie('pp_server_hash'), $core->framework->user->getData('id'), $core->framework->user->getData('root_admin'));
$core->framework->files = new files();

/*
 * Minecraft RCON
 */
include('rcon/rcon.connect.php');

$rcon = new stdClass();
$rcon->s = new MCOnline();

/*
 * MySQL PDO Connection Engine
 */
$mysql = dbConn::getConnection();

function pdo_exception_handler($exception) {
    if ($exception instanceof PDOException) {
        
        error_log($exception);
        
        die(json_encode(array('error' => 'A MySQL error was encountered with this request.', 'e_code' => $exception->getCode(), 'e_line' => $exception->getLine(), 'e_time' => date('d-M-Y H:i:s', time()))));
        
    } else {
    
    	die('Exception handler from unknown source.');
    
    }
}
set_exception_handler('pdo_exception_handler');

?>