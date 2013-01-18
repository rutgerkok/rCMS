<h2><?php echo $this->t("users.email.edit") ?></h2>
<?php
$text_to_display = '';

//OBJECTEN
$oAuth = new Authentication($this);

//INLOGGEN
$logged_in = $oAuth->check(Authentication::$USER_RANK, true);


if ($logged_in) {
    $display_form = true; //vooralsnog weergeven
    
    if (isset($_POST['email'])) {
        if (Validate::email($_POST['email'])) {
            $user = $oAuth->get_current_user();
            $user->set_email($_POST['email']);

            if ($user->save()) {
                $oAuth->set_current_user($user);

                $text_to_display.='<p>' . $this->t("users.email") . ' ' . $this->t("editor.is_changed") . '</p>'; //email changed
                $text_to_display.='<p><a href="' . $this->get_url_page("account_management") . '" class="arrow">' . $this->t("users.account_management") . '</a></p>'; //account management

                $display_form = false;
            } else {
                // Save error
                $text_to_display.='<p>' . $this->t("users.email") . ' ' . $this->t("errors.not_saved") . '</p>';
                $display_form = true;
            }
        } else {
            $this->add_error($this->t("users.email") . ' ' . Validate::get_last_error($this) . '.');
            $text_to_display.='<p>' . str_replace("#", $this->t("users.email"), $this->t("errors.your_input_has_not_been_changed")) . '</p>';
            $display_form = true;
        }
    }
    if ($display_form) {
        $email = isset($_POST['email']) ? $_POST['email'] : $oAuth->get_current_user()->get_email();

        $text_to_display.=<<<EOT
		<p>
			{$this->t('users.email.edit.explained')} 
		</p>
		
		<form action="{$this->get_url_main()}" method="post">
			<p>
				<label for="email">{$this->t('users.email')}:</label><br /><input type="text" id="email" name="email" value="$email"/><br />
			</p>
			<p>
				<input type="hidden" name="p" value="change_email" />
				<input type="submit" value="{$this->t('users.email.edit')} " class="button" />
			</p>
		</form>
		
		<p>
			<a href="'{$this->get_url_page("account_management")}.'" class="arrow">{$this->t("users.account_management")}</a>
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