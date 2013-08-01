<?php

// The display_name extends the password page, since they are highly similar
require_once($this->get_uri_page("edit_password"));

class EditDisplayNamePage extends EditPasswordPage {

    public function get_page_title(Website $oWebsite) {
        return $oWebsite->t("editor.display_name.edit");
    }

    public function get_page_content(Website $oWebsite) {
        // Check selected user
        if ($this->user == null) {
            return "";
        }

        $show_form = true;
        $text_to_display = "";
        if (isset($_REQUEST["display_name"])) {
            // Sent
            $display_name = $oWebsite->get_request_string("display_name");
            if (Validate::display_name($display_name)) {
                // Valid display_name
                $this->user->set_display_name($display_name);
                if ($this->user->save()) {
                    // Saved
                    $text_to_display.='<p>' . $oWebsite->t("users.display_name") . ' ' . $oWebsite->t("editor.is_changed") . '</p>';
                    // Don't show form
                    $show_form = false;
                } else {
                    // Database error
                    $text_to_display.='<p><em>' . $oWebsite->t("users.display_name") . ' ' . $oWebsite->t("errors.not_saved") . '</em></p>';
                }
            } else {
                // Invalid display_name
                $oWebsite->add_error($oWebsite->t("users.display_name") . ' ' . Validate::get_last_error($oWebsite));
                $text_to_display.='<p><em>' . $oWebsite->t_replaced_key("errors.your_input_has_not_been_changed", "users.display_name", true) . '</em></p>';
            }
        }
        // Show form
        if ($show_form) {
            // Text above form
            $text_to_display.= "<p>" . $oWebsite->t("editor.display_name.edit.explained") . "</p>\n";
            if ($this->editing_someone_else) {
                $text_to_display.= "<p><em>" . $oWebsite->t_replaced("editor.account.edit_other", $this->user->get_display_name()) . "</em></p>\n";
            }

            // Form itself
            $display_name = isset($_POST['display_name']) ? htmlspecialchars($_POST['display_name']) : $this->user->get_display_name();
            $text_to_display.=<<<EOT
                <p>{$oWebsite->t("main.fields_required")}</p>
                <form action="{$oWebsite->get_url_main()}" method="post">
                    <p>
                        <label for="display_name">{$oWebsite->t('users.display_name')}:</label><span class="required">*</span><br />
                            <input type="text" id="display_name" name="display_name" value="$display_name"/><br />
                    </p>
                    <p>
                        <input type="hidden" name="id" value="{$this->user->get_id()}" />
                        <input type="hidden" name="p" value="edit_display_name" />
                        <input type="submit" value="{$oWebsite->t('editor.display_name.edit')} " class="button" />
                    </p>
                </form>
EOT;
        }

        // Links
        $text_to_display.= $this->get_account_links_html($oWebsite);

        return $text_to_display;
    }

}

$this->register_page(new EditDisplayNamePage());
?>