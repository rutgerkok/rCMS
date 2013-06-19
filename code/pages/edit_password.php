<?php

class EditPasswordPage extends Page {

    /** @var User $user_to_edit */
    protected $user;
    protected $editing_someone_else = false;

    /** Fills the class variables, adds errors if needed. */
    public function determine_user_to_edit(Website $oWebsite) {
        $this->user = $oWebsite->get_authentication()->get_current_user();
        $user_id = $oWebsite->get_request_int("id", 0);
        // Id given to edit someone else, check for permissions
        if ($user_id > 0 && $user_id != $this->user->get_id()) {
            $this->editing_someone_else = true;
            if ($oWebsite->logged_in_staff(true)) {
                // Editing someone else
                $this->user = User::get_by_id($oWebsite, $user_id);
                if($this->user == null) {
                    // User not found
                    $oWebsite->add_error($oWebsite->t("users.account") . " " . $oWebsite->t("errors.not_found"));
                }
            } else {
                // No permissions to edit someone else
                // Set user to null to trigger an error later on
                $this->user = null;
                $oWebsite->add_error($oWebsite->t("users.account") . " " . $oWebsite->t("errors.not_editable"));
            }
        }
    }

    public function get_page_title(Website $oWebsite) {
        return $oWebsite->t("editor.password.edit");
    }

    public function get_minimum_rank(Website $oWebsite) {
        return Authentication::$USER_RANK;
    }

    public function get_page_type() {
        return "BACKSTAGE";
    }

    public function get_page_content(Website $oWebsite) {
        // Get and check selected user
        $this->determine_user_to_edit($oWebsite);
        if ($this->user == null) {
            return "";
        }
        
        $show_form = true;
        $text_to_display = "";
        if (isset($_REQUEST["password"])) {
            // Sent
            $password = $oWebsite->get_request_string("password");
            $password2 = $oWebsite->get_request_string("password2");
            if (Validate::password($password, $password2)) {
                // Valid password
                $this->user->set_password($password);
                if ($this->user->save()) {
                    // Saved
                    $text_to_display.='<p>' . $oWebsite->t("users.password") . ' ' . $oWebsite->t("editor.is_changed") . '</p>';
                    // Don't show form
                    $show_form = false;
                } else {
                    // Database error
                    $text_to_display.='<p><em>' . $oWebsite->t("users.password") . ' ' . $oWebsite->t("errors.not_saved") . '</em></p>';
                }
            } else {
                // Invalid password
                $oWebsite->add_error($oWebsite->t("users.password") . ' ' . Validate::get_last_error($oWebsite));
                $text_to_display.='<p><em>' . $oWebsite->t_replaced_key("errors.your_input_has_not_been_changed", "users.password", true) . '</em></p>';
            }
        }
        // Show form
        if ($show_form) {
            // Text above form
            $text_to_display.= "<p>" . $oWebsite->t_replaced("editor.password.edit.explained", Validate::$MIN_PASSWORD_LENGHT) . "</p>\n";
            if ($this->editing_someone_else) {
                $text_to_display.= "<p><em>" . $oWebsite->t_replaced("editor.account.edit_other", $this->user->get_display_name()) . "</em></p>\n";
            }

            // Form itself
            $text_to_display.=<<<EOT
                <form action="{$oWebsite->get_url_main()}" method="post">
                    <p>
                        <label for="password">{$oWebsite->t('users.password')}:</label><br />
                        <input type="password" id="password" name="password" value=""/><br />
                        <label for="password2">{$oWebsite->t('editor.password.repeat')}:</label><br />
                        <input type="password" id="password2" name="password2" value=""/><br />
                    </p>
                    <p>
                        <input type="hidden" name="p" value="edit_password" />
                        <input type="submit" value="{$oWebsite->t('editor.password.edit')} " class="button" />
                    </p>
                </form>
EOT;
        }

        // Links
        if($this->editing_someone_else) {
            // Editing someone else, don't show "My account" link
            $text_to_display .= <<<EOT
            <p>
                <a class="arrow" href="{$oWebsite->get_url_page("account", $this->user->get_id())}">
                    {$oWebsite->t_replaced("users.profile_page_of", $this->user->get_display_name())}
                </a><br />
                <a class="arrow" href="{$oWebsite->get_url_page("account_management")}">
                    {$oWebsite->t("main.account_management")}
                </a>
EOT;
        } else {
            $text_to_display .= '<p><a class="arrow" href="' . $oWebsite->get_url_page("account") . '">' . $oWebsite->t("main.my_account") . "</a>\n";
            if($oWebsite->logged_in_staff(true)) {
                $text_to_display .= '<br /><a class="arrow" href="' . $oWebsite->get_url_page("account_management") . '">' . $oWebsite->t("main.account_management") . "</a>\n";
            }
            $text_to_display.= "</p>";
        }

        return $text_to_display;
    }

}

$this->register_page(new EditPasswordPage());
?>