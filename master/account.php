<?php
session_start();
require_once('core/framework/framework.core.php');
$error = '';

if($core->framework->auth->isLoggedIn($_SERVER['REMOTE_ADDR'], $core->framework->auth->getCookie('pp_auth_token')) !== true){
	$core->framework->page->redirect('index.php');
	exit();
}

/*
 * Lah-de-dah
 */
$outputMessage = '';

/*
 * Changing Account Details
 */
if(isset($_GET['action'])){

	if($_GET['action'] == 'notifications'){

		/*
		 * Update Notification Settings
		 */
		$selectAccount = $mysql->prepare("SELECT * FROM `users` WHERE `password` = :password AND `email` = :email");
		$selectAccount->execute(array(
			':password' => $core->framework->auth->encrypt($_POST['password']),
			':email' => $core->framework->user->getData('email')
		));
		
			if($selectAccount->rowCount() == 1){
			
				$updateUsers = $mysql->prepare("UPDATE `users` SET `notify_login_s` = :e_s, `notify_login_f` = :e_f WHERE `id` = :uid AND `password` = :password");
				$updateUsers->execute(array(
					':e_s' => $_POST['e_s'],
					':e_f' => $_POST['e_f'],
					':uid' => $core->framework->user->getData('id'),
					':password' => $core->framework->auth->encrypt($_POST['password'])
				));
				
				$outputMessage = '<div class="confirmation-box round">Your notification preferences have been updated.</div>';
			
			}else{
			
				$outputMessage = '<div class="error-box round">We were unable to verify your password. Please try again.</div>';
			
			}


	}else if($_GET['action'] == 'email'){
	
		/*
		 * Update Email Address
		 */
		$emailKey = $core->framework->auth->keygen('30');
		$expire = time() + 14400;
		
		if($_POST['newemail'] == $core->framework->user->getData('email')){
		
			$outputMessage = '<div class="error-box round">Sorry, you can\'t change your email to the email address you are currently using for the account, that wouldn\'t make sense!</div>';
		
		}else{
		
			$selectAccount = $mysql->prepare("SELECT * FROM `users` WHERE `password` = :password AND `email` = :email");
			$selectAccount->execute(array(
				':password' => $core->framework->auth->encrypt($_POST['password']),
				':email' => $core->framework->user->getData('email')
			));
			
				if($selectAccount->rowCount() == 1){
						
						$updateEmail = $mysql->prepare("INSERT INTO `account_change` VALUES(NULL, :uid, 'email', :nemail, :ekey, :expires, 0)");
						$updateEmail->execute(array(
							':uid' => $core->framework->user->getData('id'),
							':nemail' => $_POST['newemail'],
							':ekey' => $emailKey,
							':expires' => $expire
						));
											
							/*
							 * Send Email
							 */
							$message = $core->framework->email->generateEmailChangedNotification(array('EMAIL_KEY' => $emailKey, 'IP_ADDRESS' => $_SERVER['REMOTE_ADDR'], 'GETHOSTBY_IP_ADDRESS' => gethostbyaddr($_SERVER['REMOTE_ADDR'])));
							 
							$core->framework->email->dispatch($_POST['newemail'], $core->framework->settings->get('company_name').' Email Updated', $message);	
					
					$outputMessage = '<div class="information-box round">We have sent an email to the address you provided in the previous step. Please follow the instructions included in that email to continue. The verification key will expire in 4 hours.</div>';
					
				}else{
				
					$outputMessage = '<div class="error-box round">We were unable to verify your password. Please try again.</div>';
				
				}
				
		}
	
	}else if($_GET['action'] == 'password'){
	
		/*
		 * Update Account Password
		 */
		$oldPassword = $core->framework->auth->encrypt($_POST['p_password']);
		 
			$selectAccount = $mysql->prepare("SELECT * FROM `users` WHERE `password` = :oldpass AND `email` = :email");
			$selectAccount->execute(array(
				':oldpass' => $core->framework->auth->encrypt($_POST['p_password']),
				':email' => $core->framework->user->getData('email')
			));
			
				if($selectAccount->rowCount() == 1){
					if(preg_match("#.*^(?=.{8,200})(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9]).*$#", $_POST['p_password_new'])){
					
						if($_POST['p_password_new'] == $_POST['p_password_new_2']){
						
							$newPassword = $core->framework->auth->encrypt($_POST['p_password_new']);
							
								/*
								 * Change Password
								 */
								$updatePassword = $mysql->prepare("UPDATE `users` SET `password` = :password, `session_id` = NULL, `session_ip` = NULL, `session_expires` = NULL WHERE `id` = :uid");
								$updatePassword->execute(array(
									':password' => $core->framework->auth->encrypt($_POST['p_password_new']),
									':uid' => $core->framework->user->getData('id')
								));
								
									
								/*
								 * Send Email
								 */
								$message = $core->framework->email->generatePasswordChangedNotification(array('IP_ADDRESS' => $_SERVER['REMOTE_ADDR'], 'GETHOSTBY_IP_ADDRESS' => gethostbyaddr($_SERVER['REMOTE_ADDR'])));
								
								$core->framework->email->dispatch($_POST['email'], $core->framework->settings->get('company_name').' - Password Change Notification', $message);
								
							$outputMessage = '<div class="confirmation-box round">Your password has been sucessfully changed!</div>';
								
						
						}else{
						
							$outputMessage = '<div class="error-box round">Your passowrds did not match.</div>';
						
						}
					
					}else{
					
						$outputMessage = '<div class="error-box round">Your password is not complex enough. Please make sure to include at least one number, and some type of mixed case.</div>';
					
					}
				
				}else{
				
					$outputMessage = '<div class="error-box round">Current account password is not correct.</div>';
				
				}
	
	}

}

/*
 * Get Notification Preferences
 */
if($core->framework->user->getData('notify_login_s') == 1){ $ns1 = 'checked="checked"'; $ns0 = ''; }else{ $ns0 = 'checked="checked"'; $ns1 = ''; }
if($core->framework->user->getData('notify_login_f') == 1){ $nf1 = 'checked="checked"'; $nf0 = ''; }else{ $nf0 = 'checked="checked"'; $nf1 = ''; }

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<title><?php echo $core->framework->settings->get('company_name'); ?> - Account Dashboard</title>
	
	<!-- Stylesheets -->
	<link href='http://fonts.googleapis.com/css?family=Droid+Sans:400,700' rel='stylesheet'>
	<link rel="stylesheet" href="assets/css/style.css">
	
	<!-- Optimize for mobile devices -->
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	
	<!-- jQuery & JS files -->
	<script src="//ajax.googleapis.com/ajax/libs/jquery/1.10.2/jquery.min.js"></script>
	
</head>
<body>
	<div id="top-bar">
		<div class="page-full-width cf">
			<ul id="nav" class="fl">
				<li><a href="#" class="round button dark"><i class="icon-user"></i>&nbsp;&nbsp; <strong><?php echo $core->framework->user->getData('username'); ?></strong></a></li>
			</ul>
			<ul id="nav" class="fr">
				<?php if($core->framework->user->getData('root_admin') == 1){ echo '<li><a href="admin/index.php" class="round button dark"><i class="icon-bar-chart"></i>&nbsp;&nbsp; Admin CP</a></li>'; } ?>
				<li><a href="logout.php" class="round button dark"><i class="icon-off"></i></a></li>
			</ul>
		</div>	
	</div>
	<div id="header-with-tabs">
		<div class="page-full-width cf">
		</div>
	</div>
	<div id="content">
		<div class="page-full-width cf">
			<div class="side-menu fl">
				<h3>Account Actions</h3>
				<ul>
					<li><a href="account.php"><i class="icon-double-angle-right pull-right menu-arrows"></i> Edit Settings</a></li>
					<li><a href="servers.php"><i class="icon-double-angle-right pull-right menu-arrows"></i> My Servers</a></li>
				</ul>
				<h3>Server Actions</h3>
			</div>
			<div class="side-content fr">
				<?php echo $outputMessage; ?>
				<div class="half-size-column fl">
					<div class="content-module">
						<div class="content-module-heading cf">
							<h3 class="fl">Change Your Password</h3>
						</div>
						<div class="content-module-main">
							<form action="account.php?action=password" method="post">
								<fieldset>
									<p>
										<label for="p_password">Current Password</label>
										<input type="password" name="p_password" class="round full-width-input" />
									</p>
									<p>
										<label for="p_password_new">New Password</label>
										<input type="password" name="p_password_new" class="round full-width-input"/>
										<em>Your password must be at least 8 characters and contain mixed case and a number.</em>								
									</p>
									<p>
										<label for="p_password_new_2">New Password Again</label>
										<input type="password" name="p_password_new_2" class="round full-width-input"/>
									</p>
									<div class="stripe-separator"><!--  --></div>
									<input type="submit" value="Change Password" class="round blue ic-right-arrow" />
								</fieldset>
							</form>							
						</div>
					</div>
				</div>
				<div class="half-size-column fr">
					<div class="content-module">
						<div class="content-module-heading cf">
							<h3 class="fl">Update Your Email</h3>
						</div>
						<div class="content-module-main cf">
							<form action="account.php?action=email" method="post">
								<fieldset>
									<p>
										<label for="newemail">New Email</label>
										<input type="text" name="newemail" class="round full-width-input" />
									</p>
									<p>
										<label for="password">Current Password</label>
										<input type="password" name="password" class="round full-width-input"/>
									</p>
									<div class="stripe-separator"><!--  --></div>
									<input type="submit" value="Update Email" class="round blue ic-right-arrow" />
								</fieldset>
							</form>	
						</div>
					</div>
					<div class="content-module">
						<div class="content-module-heading cf">
							<h3 class="fl">Notification Preferences</h3>
						</div>
						<div class="content-module-main cf">
							<form action="account.php?action=notifications" method="post">
								<fieldset>
									<p>
										<label>Successful Login</label>
										<label for="e_s" class="alt-label"><input type="radio" id="e_s" name="e_s" value="1" <?php echo $ns1; ?>/>Please Email Me</label>
										<label for="e_s_2" class="alt-label"><input type="radio" id="e_s_2" name="e_s" value="0" <?php echo $ns0; ?>/>Don't Email Me</label>
									</p>
									<p>
										<label>Failed Login</label>
										<label for="e_f" class="alt-label"><input type="radio" id="e_f" name="e_f" value="1" <?php echo $nf1; ?>/>Please Email Me</label>
										<label for="e_f_2" class="alt-label"><input type="radio" id="e_f_2" name="e_f" value="0" <?php echo $nf0; ?>/>Don't Email Me</label>
									</p>
									<p>
										<label for="password">Current Password</label>
										<input type="password" name="password" class="round full-width-input"/>
									</p>
									<div class="stripe-separator"><!--  --></div>
									<input type="submit" value="Update Preferences" class="round blue ic-right-arrow" />
								</fieldset>
							</form>	
						</div>
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