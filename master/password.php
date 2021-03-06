<?php
session_start();
require_once('core/framework/framework.core.php');
$HTML = '';

if($core->framework->auth->isLoggedIn($_SERVER['REMOTE_ADDR'], $core->framework->auth->getCookie('pp_auth_token')) === true){
	$core->framework->page->redirect('servers.php');
}

require_once("core/captcha/recaptchalib.php");
$statusMessage = ''; $noShow = false;

if(isset($_GET['do']) && $_GET['do'] == 'recover'){

	$resp = recaptcha_check_answer($core->framework->settings->get('captcha_priv'), $_SERVER["REMOTE_ADDR"], $_POST["recaptcha_challenge_field"], $_POST["recaptcha_response_field"]);
		
	if($resp->is_valid){
	
		/*
		 * Find User
		 */
		$query = $mysql->prepare("SELECT * FROM `users` WHERE `email` = ?");
		$query->execute(array($_POST['email']));
		
			if($query->rowCount() == 1){
	
				$pKey = $core->framework->auth->keygen('30');
				
				$accountChangeInsert = $mysql->prepare("INSERT INTO `account_change` VALUES(NULL, NULL, 'password', :email, :pkey, :expires, 0)");
				$accountChangeInsert->execute(array(
					':email' => $_POST['email'],
					':pkey' => $pKey,
					':expires' => time() + 14400
				));
				
					/*
					 * Send Email
					 */
					$message = $core->framework->email->generateForgottenPasswordEmail(array('IP_ADDRESS' => $_SERVER['REMOTE_ADDR'], 'GETHOSTBY_IP_ADDRESS' => gethostbyaddr($_SERVER['REMOTE_ADDR']), 'PKEY' => $pKey));
					
					$core->framework->email->dispatch($_POST['email'], $core->framework->settings->get('company_name').' - Reset Your Password', $message);
				
				$statusMessage = '<div class="confirmation-box round">We have sent an email to the address you provided in the previous step. Please follow the instructions included in that email to continue. The verification key will expire in 4 hours.</div>';
				$noShow = true;
			
			}else{
			
				$statusMessage = '<div class="error-box round">We couldn\'t find that email in our database.</div>';
			
			}
	
	}else{
	
		$statusMessage = '<div class="error-box round">The spam prevention was not filled out correctly.<br />Go back and try again.</div>';
	
	}

}else if(isset($_GET['key'])){

	/*
	 * Change Password
	 */
	$key = $_GET['key'];
	$query = $mysql->prepare("SELECT * FROM `account_change` WHERE `key` = :key AND `verified` = '0' AND `time` > :time");
	$query->execute(array(
		':key' => $_GET['key'],
		':time' => time()
	));
		
		if($query->rowCount() ==  1){
		
			$row = $query->fetch();
			
			$raw_newpassword = $core->framework->auth->keygen('12');
			
			$updateAccountChange = $mysql->prepare("UPDATE `account_change` SET `verified` = 1 WHERE `key` = ?");
			$updateAccountChange->execute(array($key));
			
			$updateUsers = $mysql->prepare("UPDATE `users` SET `password` = :newpass WHERE `email` = :email");
			$updateUsers->execute(array(
				':newpass' => $core->framework->auth->encrypt($raw_newpassword),
				':email' => $row['content']
			));
			
			$statusMessage = '<div class="confirmation-box round">You should recieve an email within the next 5 minutes (usually instantly) with your new account password. We suggest changing this once you log in.</div>';
			$noShow = true;
		
				/*
				 * Send Email
				 */
				$message = $core->framework->email->generateNewPasswordEmail(array('NEW_PASS' => $raw_newpassword, 'EMAIL' => $row['content']));
				
				$core->framework->email->dispatch($row['content'], $core->framework->settings->get('company_name').' - New Password', $message);
		
		}else{
		
			$statusMessage = '<div class="error-box round">Unable to verify password recovery request.<br />Did the key expire? Please contact support for more help or try again.</div>';
		
		}
		
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title><?php echo $core->framework->settings->get('company_name'); ?> - Forgotten Password</title>
	<link href='http://fonts.googleapis.com/css?family=Droid+Sans:400,700' rel='stylesheet'>
	<link rel="stylesheet" href="assets/css/style.css">
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<script type="text/javascript">
		var RecaptchaOptions = {
			theme : 'clean'
		};
	</script>  
</head>
<body>
	<div id="top-bar">
		<div class="page-full-width">
			<a href="<?php echo $core->framework->settings->get('main_website'); ?>" class="round button dark ic-left-arrow image-left">Return to website</a>
		</div> <!-- end full-width -->	
	</div> <!-- end top-bar -->
	<div id="header">
		<div class="page-full-width cf">
			<div id="login-intro" class="fl">
				<h1>Password Recovery</h1>
				<h5>Enter your email below</h5>
			</div> <!-- login-intro -->
		</div> <!-- end full-width -->	
	</div> <!-- end header -->
	<!-- MAIN CONTENT -->
	<div id="content">
		<form action="password.php?do=recover" method="POST" id="login-form">
			<fieldset style="margin-left:-65px">
				<?php echo $statusMessage; ?>
				<?php if($noShow === false) { ?>
				<p>
					<label for="login-email">email</label>
					<input type="text" id="login-email" name="email" autocomplete="off" class="round full-width-input" autofocus />
				</p>
				<p>
					<label for="login-email">bot protection</label>
					<?php echo recaptcha_get_html($core->framework->settings->get('captcha_pub')); ?>
				</p>
				<input type="submit" value="RESET PASSWORD" class="button round blue image-right ic-right-arrow" />
				<?php } ?>
			</fieldset>
		</form>
	</div> <!-- end content -->
	<!-- FOOTER -->
	<div id="footer">
		<p>Copyright &copy; 2012 - 2013. All Rights Reserved.<br />Running PufferPanel Version 0.3 Beta distributed by <a href="http://pufferfi.sh">Puffer Enterprises</p>	
	</div> <!-- end footer -->
</body>
</html>