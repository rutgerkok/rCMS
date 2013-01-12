<?php

$oDB = $this->get_database();
$oDB->create_tables();
exit;
if ($oDB->rows($oDB->query('SELECT gebruiker_wachtwoord FROM `gebruikers` LIMIT 0,1', false)) == 0 || $this->logged_in(true)) { //Je bent ingelogd als admin, of de site is nog leeg. 
    if (isset($_REQUEST['confirm']) && $_REQUEST['confirm'] == 1) { //installeer de database!/reset de site!
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
					<td colspan="2"><strong><em>Please <a href="{$this->get_url_page('change_password')}">change your password</a> as soon as possible</em></strong></td>
				</tr>
				<tr>
					<th>Username</th><td>admin</td>
				</tr>
				<tr>
					<th>Password</th><td>admin</td>
				</tr>
			</table>		
			
			
EOT;
    } else { //toon bevestigingsvraag
        $link_confirm = $this->get_url_page("installing_database", 0, array("confirm" => 1));
        echo <<<EOT
			
			<h2>Installing database</h2>
			<p>
				Are you sure to (re-)install the database? If the database is already installed, <strong><em>the site will be reset!</em></strong>
			</p>
			<p>
				<a  href="$link_confirm">Yes</a>|<a href="{$this->get_url_main()}">No</a>
			</p>
EOT;
    }
}
?>