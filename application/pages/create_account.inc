<?php

// Get user object
$oAuth = new Authentication($this);
$loggedIn = $oAuth->check(false, false);

$display_page = $this->getSiteSetting("user_account_creation") && !$oAuth->check(true, false);

if (!$display_page) {
    // Delegate to admin account creation screen if
    // - not having permissions to create accounts (so that there is a login screen)
    // - the user is logged in as an admin, so that he can create an account
    if (file_exists($this->getUriPage("create_account_admin"))) {
        require($this->getUriPage("create_account_admin"));
    } else {
        $this->addError("Cannot load page!", "Cannot load page " . $this->getUriPage("create_account_admin") . ", create_account_admin not found!");
    }
} else {
    // Display header
    echo '<h2>' . $this->t("users.create.title") . '</h2>';

    $textToDisplay = '';

    if ($loggedIn) {
        // Logged in, but not as an admin
        $textToDisplay = "<p>" . $this->t("errors.must_be_logged_out") . "</p>\n\n";
        $textToDisplay.= '<p><a class="arrow" href="' . $this->getUrlPage("log_out") . '">' . $this->t("main.log_out") . "</a></p>\n\n";
    } else {
        $creating_username = isSet($_POST["creating_username"])? $_POST["creating_username"] : "";
        $creating_display_name = isSet($_POST["creating_display_name"])? $_POST["creating_display_name"] : "";
        $creating_password1 = isSet($_POST["creating_password1"])? $_POST["creating_password1"] : "";
        $creating_password2 = isSet($_POST["creating_password2"])? $_POST["creating_password2"] : "";
        $creating_email = isSet($_POST["creating_email"])? $_POST["creating_email"] : "";
        
        $display_form = true;
        
        if(isSet($_POST["creating_username"])) {
            // Handle input
            $valid = true;
            if (!Validate::username($creating_username, $this)) {
                $valid = false;
                $this->addError($this->t("users.the_username") . " " . Validate::get_last_error($this));
            }
            if (!Validate::display_name($creating_display_name)) {
                $valid = false;
                $this->addError($this->t("users.the_display_name") . " " . Validate::get_last_error($this));
            }
            if (!Validate::password($creating_password1, $creating_password2)) {
                $valid = false;
                $this->addError($this->t("users.the_password") . " " . Validate::get_last_error($this));
            }
            if (!Validate::email($creating_email)) {
                $valid = false;
                $this->addError($this->t("users.the_email") . " " . Validate::get_last_error($this));
            }

            if ($valid) {
                // Create user
                $display_form = false;

                $user = new User(
                            $this, 0, $creating_username, $creating_display_name, 
                            '', $creating_email, $oAuth->get_standard_rank_id(),
                            0, 0, Authentication::NORMAL_STATUS, ""
                        );
                $user->setPassword($creating_password1);
                if($user->save()) {
                    $textToDisplay .= "<p>" . $this->t("users.account") . " " . $this->t("editor.is_created") . "</p>";
                    $textToDisplay .= '<p><a class="arrow" href="' . $this->getUrlPage("log_in") . '">' . $this->t("main.log_in") . "</a></p>";
                } else {
                    // Internal error occured
                    $textToDisplay .= "<p><strong>" . $this->tReplacedKey("errors.your_input_has_not_been_created", "users.account", true) . "</strong></p>";
                    $display_form = true;
                }
            } else {
                // User error occured
                $display_form = true;
            }
        }
        
        if($display_form) {
            // Show form
            $textToDisplay .= <<<EOT
                <p>
                    {$this->t("users.create.explained")}
                </p>
                <p>
                    {$this->t("main.fields_required")}
                </p>

                <form action="{$this->getUrlMain()}" method="post">
                    <p>
                        <label for="creating_username">{$this->t("users.username")}<span class="required">*</span>:</label><br /><input type="text" id="creating_username" name="creating_username" value="$creating_username" /><br />
                        <label for="creating_display_name">{$this->t("users.display_name")}<span class="required">*</span>:</label><br /><input type="text" id="creating_display_name" name="creating_display_name" value="$creating_display_name" /><br />
                        <label for="creating_password1">{$this->t("users.password")}<span class="required">*</span>:</label><br /><input type="password" id="creating_password1" name="creating_password1" value="$creating_password1"/><br />
                        <label for="creating_password2">{$this->t("editor.password.repeat")}<span class="required">*</span>:</label><br /><input type="password" id="creating_password2" name="creating_password2" value=""/><br />
                        <label for="creating_email">{$this->t("users.email")}:</label><br /><input type="email" id="creating_email" name="creating_email" value="$creating_email" /><br />
                    </p>
                    <p>
                        <input type="hidden" name="p" value="create_account" />
                        <input type="submit" value="{$this->t("main.create_account")}" class="button" />
                    </p>
                </form>
EOT;
        }
    }
}

// Handle errors
if (!$this->errorsDisplayed) {
    $this->echoErrors();
}

// Show page if needed
if ($display_page) {
    echo $textToDisplay;
}
?>