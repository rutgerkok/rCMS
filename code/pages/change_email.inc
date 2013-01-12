<h2>Change your email address</h2>
<?php
$text_to_display = '';

//OBJECTEN
$oAuth = new Authentication($this);

//INLOGGEN
$logged_in = $oAuth->check(false, true);


if ($logged_in) {
    $display_form = true; //vooralsnog weergeven
    
    if (isset($_POST['email'])) {
        if (Validate::email($_POST['email'])) {
            $user = $oAuth->get_current_user();
            $user->set_email($_POST['email']);

            if ($user->save()) {
                $oAuth->set_current_user($user);

                $text_to_display.='<p>Your email address has been changed.</p>';
                $text_to_display.='<p><a href="' . $this->get_url_page("account_management") . '" class="arrow">Account management</a></p>';

                $display_form = false;
            } else {
                $text_to_display.='<p>Your email address has <strong>not</strong> been changed.</p>';
                $display_form = true;
            }
        } else {
            $this->add_error($this->t("users.the_email") . " " . Validate::get_last_error($this));
            $text_to_display.='<p>Your email address has <strong>not</strong> been changed.</p>';
            $display_form = true;
        }
    }
    if ($display_form) {
        $email = isset($_POST['email']) ? $_POST['email'] : $oAuth->get_current_user()->get_email();

        $text_to_display.=<<<EOT
		<p>
			Fill out the form below to change your email address. 
		</p>
		
		<form action="{$this->get_url_main()}" method="post">
			<p>
				<label for="email">Email address:</label><br /><input type="text" id="email" name="email" value="$email"/><br />
			</p>
			<p>
				<input type="hidden" name="p" value="change_email" />
				<input type="submit" value="Change email address" class="button" />
			</p>
		</form>
		
		<p>
			<a href="{$this->get_url_main('account_management')}" class="arrow">Account management</a>
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