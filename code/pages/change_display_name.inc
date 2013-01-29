<h2><?php echo $this->t("editor.display_name.edit") ?></h2>
<?php
$text_to_display = '';

//OBJECTEN
$oAuth = new Authentication($this);

//INLOGGEN
$logged_in = $oAuth->check(Authentication::$USER_RANK, true);


if ($logged_in) {
    $display_form = true; //vooralsnog weergeven
    
    if (isset($_POST['display_name'])) {
        if (Validate::display_name($_POST['display_name'])) {
            $user = $oAuth->get_current_user();
            $user->set_display_name($_POST['display_name']);

            if ($user->save()) {
                $oAuth->set_current_user($user);

                $text_to_display.='<p>' . $this->t("users.display_name") . ' ' . $this->t("editor.is_changed") . '</p>'; //email changed
                $text_to_display.='<p><a href="' . $this->get_url_page("account_management") . '" class="arrow">' . $this->t("users.account_management") . '</a></p>'; //account management

                $display_form = false;
            } else {
                // Save error
                $text_to_display.='<p>' . $this->t("users.display_name") . ' ' . $this->t("errors.not_saved") . '</p>';
                $display_form = true;
            }
        } else {
            $this->add_error($this->t("users.display_name") . ' ' . Validate::get_last_error($this) . '.');
            $text_to_display.='<p>' . str_replace("#", $this->t("users.display_name"), $this->t("errors.your_input_has_not_been_changed")) . '</p>';
            $display_form = true;
        }
    }
    if ($display_form) {
        $display_name = isset($_POST['display_name']) ? $_POST['display_name'] : $oAuth->get_current_user()->get_display_name();

        $text_to_display.=<<<EOT
		<p>
			{$this->t('editor.display_name.edit.explained')} 
		</p>
		
		<form action="{$this->get_url_main()}" method="post">
			<p>
				<label for="display_name">{$this->t('users.display_name')}:</label><br /><input type="text" id="display_name" name="display_name" value="$display_name"/><br />
			</p>
			<p>
				<input type="hidden" name="p" value="change_display_name" />
				<input type="submit" value="{$this->t('editor.display_name.edit')} " class="button" />
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