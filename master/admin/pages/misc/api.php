<?php
session_start();
require_once('../../../core/framework/framework.core.php');

if($core->framework->auth->isLoggedIn($_SERVER['REMOTE_ADDR'], $core->framework->auth->getCookie('pp_auth_token'), true) !== true){
	$core->framework->page->redirect('../../../index.php');
}

if(isset($_GET['do']) && $_GET['do'] == 'toggle_api_on'){

	/*
	 * Generate API Key
	 */
	$apiKey = $core->framework->auth->keygen(6).'-'.$core->framework->auth->keygen(8).'-'.$core->framework->auth->keygen(8).'-'.md5($core->framework->auth->keygen(6));

	$mysql->exec("UPDATE `acp_settings` SET `setting_val` = '1' WHERE `setting_ref` = 'use_api'");
	$mysql->exec("UPDATE `acp_settings` SET `setting_val` = '".$apiKey."' WHERE `setting_ref` = 'api_key'");
	$mysql->exec("UPDATE `acp_settings` SET `setting_val` = '*' WHERE `setting_ref` = 'api_allowed_ips'");

}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title>PufferPanel - API Settings</title>
	
	<!-- Stylesheets -->
	<link href='http://fonts.googleapis.com/css?family=Droid+Sans:400,700' rel='stylesheet'>
	<link rel="stylesheet" href="../../../assets/css/style.css">
	
	<!-- Optimize for mobile devices -->
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	
	<!-- jQuery & JS files -->
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	
</head>
<body>
	<div id="top-bar">
		<div class="page-full-width cf">
			<ul id="nav" class="fl">
				<li><a href="../../../account.php" class="round button dark"><i class="icon-user"></i>&nbsp;&nbsp; <strong><?php echo $core->framework->user->getData('username'); ?></strong></a></li>
			</ul>
			<ul id="nav" class="fr">
				<li><a href="../../../servers.php" class="round button dark"><i class="icon-signout"></i></a></li>
				<li><a href="../../../logout.php" class="round button dark"><i class="icon-off"></i></a></li>
			</ul>
		</div>	
	</div>
	<div id="header-with-tabs">
		<div class="page-full-width cf">
		</div>
	</div>
	<div id="content">
		<div class="page-full-width cf">
			<?php include('../../../core/templates/admin_sidebar.php'); ?>
			<div class="side-content fr">
				<div class="content-module">
					<div class="content-module-heading cf">
						<h3 class="fl">API Settings</h3>
					</div>
					<div class="content-module-main cf">
						<p>In order to use automatic setup modules with PufferPanel you must first enable this API. When enabled it will generate API keys for you to use in your programs, as well as allow you to whitelist IPs that are allowed to execute commands on the API server.</p>
						<div class="error-box round">You do not currently have the API enabled! Would you like to <a href="api.php?do=toggle_api_on">enable it now</a>?</div>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div id="footer">
		<p>Copyright &copy; 2012 - 2013. All Rights Reserved.<br />Running PufferPanel Version 0.3 Beta distributed by <a href="http://pufferfi.sh">Puffer Enterprises</a>.</p>
	</div>
</body>
</html>