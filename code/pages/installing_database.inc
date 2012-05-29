<?php
$oDB = new Database($this);
$oAuth = new Authentication($oDB,$this);
if(!$oDB->dbc)
{	//niet verbonden!
	echo "
	<h2>Installing database</h2>
	<p>
		No database-connection! The database could not be installed. Please check 'scripts/class_database.php'. You can find the database settings there on lines 6-9.
	</p>
	";
}
else
{	//wel verbonden
	if($oDB->rows($oDB->query('SELECT gebruiker_wachtwoord FROM `gebruikers` LIMIT 0,1',false))==0||$oAuth->check(true,true))
	{	//Je bent ingelogd als admin, of de site is nog leeg. 
		if(isset($_REQUEST['confirm'])&&$_REQUEST['confirm']==1)
		{	//installeer de database!/reset de site!
			$oDB->create_tables();
			echo <<<EOT
			
			
			<h2>Installing database</h2>
			<p>
				The database has been (re-)installed. You can now remove the file 'modules/installing_database.inc'.
			</p>
			<p>
				A admin account has been created.<br />
			</p>
			<table>
				<tr>
					<td colspan="2"><strong><em>Please <a href="index.php?p=change_password">change your password</a> as soon as possible</em></strong></td>
				</tr>
				<tr>
					<th>Username</th><td>admin</td>
				</tr>
				<tr>
					<th>Password</th><td>admin</td>
				</tr>
			</table>		
			
			
EOT;
		}
		else
		{	//toon bevestigingsvraag
			echo <<<EOT
			
			
			
			<h2>Installing database</h2>
			<p>
				Are you sure to (re-)install the database? If the database is already installed, <strong><em>the site will be reset!</em></strong>
			</p>
			<p>
				<a  href="index.php?p=installing_database&amp;confirm=1">Yes</a>|<a href="index.php">No</a>
			</p>
EOT;
		}
	}
}	
?>
	