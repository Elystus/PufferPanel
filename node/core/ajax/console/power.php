<?php
/*
 * Server Power Manager
 */
session_start();
require_once('../../../core/framework/framework.core.php');

if($core->framework->auth->isLoggedIn($_SERVER['REMOTE_ADDR'], $core->framework->auth->getCookie('pp_auth_token'), $core->framework->auth->getCookie('pp_server_hash')) === true){


	if(isset($_POST['process'])){

		$s = '';
		/*
		 * Verify that Server Port is set Correctly
		 */
		$SFTPConnection = ssh2_connect($core->framework->server->getData('ftp_host'), 22);
		ssh2_auth_password($SFTPConnection, $core->framework->server->getData('ftp_user'), openssl_decrypt($core->framework->server->getData('ftp_pass'), 'AES-256-CBC', file_get_contents(HASH), 0, base64_decode($core->framework->server->getData('encryption_iv'))));
		
			$sftp = ssh2_sftp($SFTPConnection);
				
				$rewrite = false;							
				$stream = fopen("ssh2.sftp://".$sftp."/server/server.properties", 'r');
				
					if(!$stream){
					
						/*
						 * Create server.properties
						 */
						fclose($stream);
						$newStream = fopen("ssh2.sftp://".$sftp."/server/server.properties", 'w+');
						$newProps = '
#Minecraft server properties
#Auto-Generated by PufferPanel
allow-nether=true
level-name=world
enable-query=true
query.port='.$core->framework->server->getData('server_port').'
allow-flight=false
server-port='.$core->framework->server->getData('server_port').'
level-type=DEFAULT
enable-rcon=false
level-seed=
server-ip='.$core->framework->server->getData('server_ip').'
max-build-height=256
spawn-npcs=true
white-list=false
spawn-animals=true
snooper-enabled=true
texture-pack=
online-mode=true
pvp=true
difficulty=1
gamemode=0
max-players=20
spawn-monsters=true
generate-structures=true
view-distance=10
motd=A Minecraft Server';
						
							if(!fwrite($newStream, $newProps)){
					
								exit('Unable to create new server.properties. Contact support ASAP.');
					
							}
						
						fclose($newStream);
						/*
						 * Re-Open
						 */
						$stream = fopen("ssh2.sftp://".$sftp."/server/server.properties", 'r');
						$contents = fread($stream, filesize("ssh2.sftp://".$sftp."/server/server.properties"));
						
					
					}
					
						/*
						 * Passed Inital Checks
						 */
						
						$contents = fread($stream, filesize("ssh2.sftp://".$sftp."/server/server.properties"));
						
						/*
						 * Generate Save File
						 */
						$saveDir = '/tmp/'.$core->framework->server->getData('hash').'/';
						if(!is_dir($saveDir)){
							mkdir($saveDir);
						}
						
						$fp = fopen($saveDir.'server.properties.savefile', 'w');
						fwrite($fp, $contents);
						fclose($fp);
						
						$newContents = $contents;
						fclose($stream);
						$lines = file($saveDir.'server.properties.savefile');
						
							foreach($lines as $line){
							
								$var = explode('=', $line);
								
									if($var[0] == 'server-port' && $var[1] != $core->framework->server->getData('server_port')){
										//Reset Port
										$newContents = str_replace('server-port='.$var[1], "server-port=".$core->framework->server->getData('server_port')."\n", $newContents);
										$rewrite = true;
									}else if($var[0] == 'online-mode' && $var[1] == 'false'){
										//Force Online Mode
										$newContents = str_replace('online-mode='.$var[1], "online-mode=true\n", $newContents);
										$rewrite = true;
									}else if($var[0] == 'query.port' && $var[1] != $core->framework->server->getData('server_port')){
										//Reset Query Port
										$newContents = str_replace('query.port='.$var[1], "server-port=".$core->framework->server->getData('server_port')."\n", $newContents);
										$rewrite = true;
									}else if($var[0] == 'enable-query' && $var[1] != 'true'){
										//Reset Query Port
										$newContents = str_replace('enable-query='.$var[1], "enable-query=true\n", $newContents);
										$rewrite = true;
									}else if($var[0] == 'server-ip' && $var[1] != $core->framework->server->getData('server_ip')){
										//Reset Query Port
										$newContents = str_replace('server-ip='.$var[1], "server-ip=".$core->framework->server->getData('server_ip')."\n", $newContents);
										$rewrite = true;
									}
							
							}
							
								/*
								 * Write New Data
								 */
								if($rewrite === true){
								
									$stream = fopen("ssh2.sftp://".$sftp."/server/server.properties", 'w+');
								
										if(!fwrite($stream, $newContents)){
								
											exit('Unable to fix broken server.properties. Please contact support.');
								
										}
								
									fclose($stream);
									
								}

		/*
		 * Connect and Run Function
		 */
		$selectNode = $mysql->prepare("SELECT * FROM `nodes` WHERE `node_name` = ? LIMIT 1");
		$selectNode->execute(array($core->framework->server->getData('node')));
		
		$node = $selectNode->fetch();
		
		$con = ssh2_connect($node['node_ip'], 22);
		ssh2_auth_password($con, $node['username'], openssl_decrypt($node['password'], 'AES-256-CBC', file_get_contents(HASH), 0, base64_decode($node['encryption_iv'])));
				
				if(isset($_POST['command'])){
				
					/*
					 * This Start Command is not working from PHP
					 */
					if($_POST['command'] == 'start'){
						
						if($rcon->s->isOnline($core->framework->server->getData('server_ip'), $core->framework->server->getData('server_port')) === true){
						
							$stream = ssh2_exec($con, 'exit');
							stream_set_blocking($stream, true);
							
							echo "Server is already running!";
							fclose($stream);
						
						}else{
												
							$stream = ssh2_exec($con, 'cd /srv/scripts; ./start_server.sh "/srv/servers/'.$core->framework->server->getData('name').'/server" "'.$core->framework->server->getData('max_ram').'" "'.$core->framework->server->getData('name').'"');

							echo "Server Started.";
							fclose($stream);
													
						}
					
					}else if($_POST['command'] == 'stop'){
					
						if($rcon->s->isOnline($core->framework->server->getData('server_ip'), $core->framework->server->getData('server_port')) !== true){
						
							$stream = ssh2_exec($con, 'exit');
							stream_set_blocking($stream, true);
							
							echo "Server is already Stopped!";
							fclose($stream);
						
						}else{
						
							$stream = ssh2_exec($con, 'cd /srv/scripts; ./send_command.sh "'.$core->framework->server->getData('name').'" "stop"');
							stream_set_blocking($stream, true);
							
							echo "Server Stopped.";
							fclose($stream);
							
						}
					
					}else if($_POST['command'] == 'kill'){
					
						if($rcon->s->isOnline($core->framework->server->getData('server_ip'), $core->framework->server->getData('server_port')) !== true){
						
							$stream = ssh2_exec($con, 'exit');
							stream_set_blocking($stream, true);
							
							echo "Server is already Stopped!";
							fclose($stream);
						
						}else{
						
							$stream = ssh2_exec($con, 'cd /srv/scripts; ./kill_server.sh "'.$core->framework->server->getData('name').'"');
							stream_set_blocking($stream, true);
							
							echo "Server Killed.";
							fclose($stream);
							
						}
					
					}else{
					
						exit('Unknown.');
					
					}
					
				}
				
	}

}else{

	die('Invalid Authentication.');

}
?>
