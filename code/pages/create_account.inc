<?php
$text_to_display = '';

//KOPREGEL
echo '<h2>Create a new account</h2>';

//ACCOUNTGEGEVENS CONTROLEREN
$oAuth = new Authentication($this);
$logged_in = $oAuth->check(true,true);//admin verplicht

//variabelen voor formulier en het maken van een account
$user = isset($_POST['user'])? $_POST['user'] : '';
$name = isset($_POST['name'])? $_POST['name'] : '';
$pass = isset($_POST['pass'])? $_POST['pass'] : '';
$pass2 = isset($_POST['pass2'])? $_POST['pass2'] : '';
$email = isset($_POST['email'])? $_POST['email'] : '';
$rank = isset($_POST['rank'])? (int) $_POST['rank'] : '';


$display_form = true;//vooralsnog

if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['user']))
{
    $valid = true;
    if(!Validate::username($user, $this)) {
        $valid = false;
        $this->add_error($this->t("users.the_username") . " " . Validate::get_last_error($this));
    }
    if(!Validate::display_name($name)) {
        $valid = false;
        $this->add_error($this->t("users.the_display_name") . " " . Validate::get_last_error($this));
    }
    if(!Validate::password($pass, $pass2)) {
        $valid = false;
        $this->add_error($this->t("users.the_password") . " " . Validate::get_last_error($this));
    }
    if(!Validate::email($email)) {
        $valid = false;
        $this->add_error($this->t("users.the_email") . " " . Validate::get_last_error($this));
    }
	
	if($valid)
	{	//maak gebruiker aan
		$display_form = false;
		
		$user = new User($this, 0, $user, $name, '', $email, $rank);
                $user->set_password($pass);
                $user->save();
                
                $text_to_display .= "<p>User with the name " . $user->get_username() . " is created!</p>";
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
			<label for="rank">Rank:</label><br />
				<select class="button" id="rank" name="rank">
EOT;
        // Echo all ranks
        for($i = 0; $i <=$oAuth->get_highest_rank_id(); $i++) {
            $text_to_display .= '<option value="' . $i . '">' . $oAuth->get_rank_name($i) . '</option>' . "\n";
        }
        $text_to_display .= <<<EOT
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