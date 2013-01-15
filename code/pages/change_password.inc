<h2><?php echo $this->t("users.password.edit") /* verander wachtwoord */ ?></h2>
<?php
$text_to_display = '';

//OBJECTEN
$oAuth = new Authentication($this);

//INLOGGEN
$logged_in = $oAuth->check(false, true);


if ($logged_in) {
    $display_form = true; //vooralsnog weergeven
    
    if (isset($_POST['pass']) && isset($_POST['pass2'])) {
        if (Validate::password($_POST['pass'], $_POST['pass2'])) {
            $user = $oAuth->get_current_user();
            $user->set_password($_POST['pass']);
            
            if ($user->save()) {
                $oAuth->set_current_user($user);
                
                $text_to_display.='<p>' . $this->t("users.password") . ' ' . $this->t("editor.is_changed") . '</p>'; //password changed
                $text_to_display.='<p><a href="' . $this->get_url_page("account_management") . '" class="arrow">' . $this->t("users.account_management") . '</a></p>'; //account management

                $display_form = false;
            } else {
                // Save error
                $text_to_display.='<p>' . $this->t("users.password") . ' ' . $this->t("errors.not_saved") . '</p>';
                $display_form = true;
            }
        } else {
            $this->add_error($this->t("users.password") . ' ' . Validate::get_last_error($this) . '.');
            $text_to_display.='<p>' . str_replace("#", $this->t("users.password"), $this->t("errors.your_input_has_not_been_changed")) . '</p>';
            $display_form = true;
        }
    }
    if ($display_form) {
        $password_message ="<p>\n" . str_replace("#", Validate::$MIN_PASSWORD_LENGHT, $this->t("users.password.edit.explained")) . "\n</p>\n\n"; //hardcoded totdat gebruikersklasse opnieuw geschreven wordt
        
        $text_to_display.=<<<EOT
                <p>
                    $password_message
                </p>
		<form action="{$this->get_url_main()}" method="post">
			<p>
				<label for="pass">{$this->t("users.password")}:</label><br /><input type="password" id="pass" name="pass" value=""/><br />
				<label for="pass2">{$this->t("users.password.repeat")}</label><br /><input type="password" id="pass2" name="pass2" value=""/><br />
			</p>
			<p>
				<input type="hidden" name="p" value="change_password" />
				<input type="submit" value="{$this->t("users.password.edit")}" class="button" />
			</p>
		</form>
		
		<p>
			<a href="{$this->get_url_page("account_management")}" class="arrow">{$this->t("users.account_management")}</a>
		</p>
EOT;
    }
}
unset($oAuth);

//FOUTAFHANDELING
if (!$this->errorsdisplayed) {
    $this->echo_errors();
}

//WEERGEVEN
echo $text_to_display;
?>