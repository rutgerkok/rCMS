<?php
$text_to_display = '';

//KOPREGEL
echo '<h2>'.$this->translations[88].'</h2>';//accountbeheer

//INLOGGEN
$oAuth = new Authentication(new Database($this),$this);
$logged_in = $oAuth->check(false,true);//wel formulier, geen admin
$is_admin = $oAuth->check(true,false);//geen formulier, wel admin



//ACCOUNT WIJZIGEN
if($logged_in)
{
	$user = $oAuth->get_current_username();
	$email = $oAuth->get_current_email();
	if(empty($email))
		$email = '<em>'.$this->translations[89].'</em>';//niet ingesteld

	$password_text = strtolower($this->translations[59]);
	$email_text = strtolower($this->translations[72]);
	
	$text_to_display.= <<<EOT
	<p>
		<table>
			<tr>
				<th>{$this->translations[58]}</th><td>$user</td>
			</tr>
			<tr>
				<th>{$this->translations[72]}</th><td>$email</td>
			</tr>
		</table>
	</p>
	
	<h3>{$this->translations[91]}</h3><!-- bewerk account -->
	<p>
		<a class="arrow" href="index.php?p=change_password">{$this->translations[94]} {$password_text}</a><br /><!-- verander wachtwoord -->
		<a class="arrow" href="index.php?p=change_email">{$this->translations[94]} {$email_text}</a>
	</p>
EOT;
}
if($is_admin)
{
	$text_to_display.= "<h3>{$this->translations[92]}</h3>";
	$text_to_display.= $oAuth->get_users_table();
	$text_to_display.= "<p><br /><a class=\"arrow\" href=\"index.php?p=admin\">{$this->translations[2]}</a></p>";
}
unset($oAuth);

//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//TEKST WEERGEVEN
echo $text_to_display;
?>