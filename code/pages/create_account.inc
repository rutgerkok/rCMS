<?php
$text_to_display = '';

//KOPREGEL
echo '<h2>Create a new account</h2>';

//ACCOUNTGEGEVENS CONTROLEREN
$oAuth = new Authentication(new Database($this),$this);
$logged_in = $oAuth->check(true,true);//admin verplicht

//variabelen voor formulier en het maken van een account
$user = isset($_POST['user'])? $_POST['user'] : '';
$name = isset($_POST['name'])? $_POST['name'] : '';
$pass = isset($_POST['pass'])? $_POST['pass'] : '';
$pass2 = isset($_POST['pass2'])? $_POST['pass2'] : '';
$email = isset($_POST['email'])? $_POST['email'] : '';
$admin = isset($_POST['admin'])? (int) $_POST['admin'] : '';


$display_form = true;//vooralsnog

if($_SERVER['REQUEST_METHOD'] == 'POST')
{
	$oAuth->valid_username($user);//genereert zelf foutmeldingen
	$oAuth->valid_name($name);//idem
	$oAuth->valid_password($pass,$pass2);//idem
	$oAuth->valid_email($email);//idem
	
	if($this->error_count()==0)
	{	//maak gebruiker aan
		$display_form = false;
		
		if($oAuth->add_user($user,$pass,$email,$admin,$name))
		{
			$text_to_display.='<p>Account is created.</p>';
		}
		else
		{	//account kan niet gemaakt worden
			$this->add_error('Account could not be created.');
			$display_form = true;
		}
	}
	else
	{	//fout opgetreden
		
		$display_form = true;
	}
}

if($display_form)
{	//geef formulier weer
	$text_to_display .= <<<EOT
	<p>
		You can create a new account here. 
		Fields with a <span class="required">*</span> are required.
		Please note that the username must be at least 4 characters long and the that the username must be at least 5 characters long.
	</p>
	
	<form action="{$this->get_url_main()}" method="post">
		<p>
			<label for="user">Username<span class="required">*</span>:</label><br /><input type="text" id="user" name="user" value="$user" /><br />
			<label for="user">Display name<span class="required">*</span>:</label><br /><input type="text" id="name" name="name" value="$name" /><br />
			<label for="pass">Password<span class="required">*</span>:</label><br /><input type="password" id="pass" name="pass" value="$pass"/><br />
			<label for="pass2">Repeat password<span class="required">*</span>:</label><br /><input type="password" id="pass2" name="pass2" value="$pass2"/><br />
			<label for="email">E-mail address:</label><br /><input type="email" id="email" name="email" value="$email" /><br />
			<label for="admin">Admin:</label><br />
				<select class="button" id="admin" name="admin">
					<option value="0">No</option>
					<option value="1">Yes (cannot be undone!)</option>
				</select>
		</p>
		<p>
			<input type="hidden" name="p" value="create_account" />
			<input type="hidden" name="confirm" value="0" />
			<input type="submit" value="Create account" class="button" />
		</p>
	</form>
EOT;

}
unset($oAuth);

//nog wat links
$text_to_display.='<p><a href="'.$this->get_url_page("account_management").'" class="arrow">Accounts</a></p>';
$text_to_display.='<p><a href="'.$this->get_url_page("admin").'" class="arrow">Admin</a></p>';


//FOUTAFHANDELING
if(!$this->errorsdisplayed)
{
	$this->echo_errors();
}

//EVENTUEEL BERICHT TONEN
if($logged_in)
{
	echo $text_to_display;
}
?>