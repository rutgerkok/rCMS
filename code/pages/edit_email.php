<?php

// The email extends the password page, since they are highly similar
require_once($this->get_uri_page("edit_password"));

class EditEmailPage extends EditPasswordPage {

    public function get_page_title(Website $oWebsite) {
        return $oWebsite->t("editor.email.edit");
    }

    public function get_page_content(Website $oWebsite) {
        // Get and check selected user
        $this->determine_user_to_edit($oWebsite);
        if ($this->user == null) {
            return "";
        }

        $show_form = true;
        $text_to_display = "";
        if (isset($_REQUEST["email"])) {
            // Sent
            $email = $oWebsite->get_request_string("email");
            if (Validate::email($email)) {
                // Valid email
                $this->user->set_email($email);
                if ($this->user->save()) {
                    // Saved
                    $text_to_display.='<p>' . $oWebsite->t("users.email") . ' ' . $oWebsite->t("editor.is_changed") . '</p>';
                    // Don't show form
                    $show_form = false;
                } else {
                    // Database error
                    $text_to_display.='<p><em>' . $oWebsite->t("users.email") . ' ' . $oWebsite->t("errors.not_saved") . '</em></p>';
                }
            } else {
                // Invalid email
                $oWebsite->add_error($oWebsite->t("users.email") . ' ' . Validate::get_last_error($oWebsite));
                $text_to_display.='<p><em>' . $oWebsite->t_replaced_key("errors.your_input_has_not_been_changed", "users.email", true) . '</em></p>';
            }
        }
        // Show form
        if ($show_form) {
            // Text above form
            $text_to_display.= "<p>" . $oWebsite->t("editor.email.edit.explained") . "</p>\n";
            if ($this->editing_someone_else) {
                $text_to_display.= "<p><em>" . $oWebsite->t_replaced("editor.account.edit_other", $this->user->get_display_name()) . "</em></p>\n";
            }

            // Form itself
            $email = isset($_POST['email']) ? htmlspecialchars($_POST['email']) : $this->user->get_email();
            $text_to_display.=<<<EOT
                <form action="{$oWebsite->get_url_main()}" method="post">
                    <p>
                        <label for="email">{$oWebsite->t('users.email')}:</label><br /><input type="text" id="email" name="email" value="$email"/><br />
                    </p>
                    <p>
                        <input type="hidden" name="id" value="{$this->user->get_id()}" />
                        <input type="hidden" name="p" value="edit_email" />
                        <input type="submit" value="{$oWebsite->t('editor.email.edit')} " class="button" />
                    </p>
                </form>
EOT;
        }

        // Links
        $text_to_display.= $this->get_account_links_html($oWebsite);

        return $text_to_display;
    }

}

$this->register_page(new EditEmailPage());
?>