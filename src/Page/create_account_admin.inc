<?php 

namespace Rcms\Core;

$textToDisplay = '';

//KOPREGEL
echo '<h2>Create a new account</h2>';

//ACCOUNTGEGEVENS CONTROLEREN
$oAuth = $this->getAuth();
$userRepo = $oAuth->getUserRepository();
$loggedIn = $oAuth->check(true, true); //admin verplicht

if ($loggedIn) {
//variabelen voor formulier en het maken van een account
    $user = isSet($_POST['user']) ? $_POST['user'] : '';
    $name = isSet($_POST['name']) ? $_POST['name'] : '';
    $pass = isSet($_POST['pass']) ? $_POST['pass'] : '';
    $pass2 = isSet($_POST['pass2']) ? $_POST['pass2'] : '';
    $email = isSet($_POST['email']) ? $_POST['email'] : '';
    $rank = isSet($_POST['rank']) ? (int) $_POST['rank'] : '';


    $display_form = true; //vooralsnog

    if ($_SERVER['REQUEST_METHOD'] == 'POST' && isSet($_POST['pass2'])) {
        $valid = true;
        if (!Validate::username($user)) {
            $valid = false;
            $this->addError($this->t("users.the_username") . " " . Validate::getLastError($this));
        }
        if (!Validate::displayName($name)) {
            $valid = false;
            $this->addError($this->t("users.the_display_name") . " " . Validate::getLastError($this));
        }
        if (!Validate::password($pass, $pass2)) {
            $valid = false;
            $this->addError($this->t("users.the_password") . " " . Validate::getLastError($this));
        }
        if (!Validate::email($email)) {
            $valid = false;
            $this->addError($this->t("users.the_email") . " " . Validate::getLastError($this));
        }
        if (!$oAuth->isValidRankForAccounts($rank)) {
            $valid = false;
            $this->addError("Invalid rank");
        }
        if ($userRepo->isUsernameInUse($user)) {
            // User with that name already exists
            $valid = false;
            $this->addError($this->t("users.the_username") . " " . $this->t("errors.already_in_use_on_this_site"));
        }
        if (!empty($email) && $userRepo->isEmailInUse($email)) {
            // User with that email already exists
            $valid = false;
            $this->addError($this->t("users.the_email") . " " . $this->t("errors.already_in_use_on_this_site"));
        }

        if ($valid) { //maak gebruiker aan
            $display_form = false;

            $userObj = User::createNewUser($user, $name, $pass);
            $userObj->setEmail($email);
            $userObj->setRank($rank);
            $userRepo->save($userObj);

            $textToDisplay .= "<p>User with the name " . $userObj->getUsername() . " is created!</p>";
        } else { //fout opgetreden
            $display_form = true;
        }
    }

    if ($display_form) { //geef formulier weer
        $textToDisplay .= <<<EOT
	<p>
		You can create a new account here. 
		Fields with a <span class="required">*</span> are required.
		Please note that the username must be at least 4 characters long and the that the password must be at least 5 characters long.
	</p>
	
	<form action="{$this->getUrlMain()}" method="post">
		<p>
			<label for="user">Username<span class="required">*</span>:</label><br /><input type="text" id="user" name="user" value="$user" /><br />
			<label for="user">Display name<span class="required">*</span>:</label><br /><input type="text" id="name" name="name" value="$name" /><br />
			<label for="pass">Password<span class="required">*</span>:</label><br /><input type="password" id="pass" name="pass" value="$pass"/><br />
			<label for="pass2">Repeat password<span class="required">*</span>:</label><br /><input type="password" id="pass2" name="pass2" value=""/><br />
			<label for="email">E-mail address:</label><br /><input type="email" id="email" name="email" value="$email" /><br />
			<label for="rank">Rank:</label><br />
				<select class="button" id="rank" name="rank">
EOT;
        // Echo all ranks
        foreach ($oAuth->getRanks() as $rankId => $rankName) {
            $textToDisplay .= '<option value="' . $rankId . '">' . $rankName . '</option>' . "\n";
        }
        $textToDisplay .= <<<EOT
				</select>
		</p>
		<p>
			<input type="hidden" name="p" value="create_account_admin" />
			<input type="submit" value="Create account" class="button primary_button" />
		</p>
	</form>
EOT;
    }
    unset($oAuth);

    //nog wat links
    $textToDisplay.='<p><a href="' . $this->getUrlPage("account_management") . '" class="arrow">Accounts</a></p>';
    $textToDisplay.='<p><a href="' . $this->getUrlPage("admin") . '" class="arrow">Admin</a></p>';
}

//FOUTAFHANDELING
if (!$this->errorsDisplayed) {
    $this->echoErrors();
}

if ($loggedIn) {
    //BERICHT TONEN
    echo $textToDisplay;
}
?>