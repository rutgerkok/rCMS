<?php

// The account status page extends the password page, since they are highly similar
require_once($this->get_uri_page("edit_password"));

class EditAccountStatusPage extends EditPasswordPage {

    const MAXIMUM_STATUS_TEXT_LENGTH = 255;

    public function get_page_title(Website $oWebsite) {
        return $oWebsite->t("editor.status.edit");
    }

    public function get_minimum_rank(Website $oWebsite) {
        return Authentication::$MODERATOR_RANK;
    }
    
    // Overrided to allow moderators to (un)block someone else
    public function can_user_edit_someone_else(Website $oWebsite) {
        return $oWebsite->logged_in_staff(false);
    }

    public function get_page_content(Website $oWebsite) {
        // Check selected user
        if ($this->user == null) {
            return "";
        }

        // Don't allow to edit your own status (why would admins want to downgrade
        // themselves?)
        if (!$this->editing_someone_else) {
            $oWebsite->add_error($oWebsite->t("users.account") . " " . $oWebsite->t("errors.not_editable"));
            return "";
        }

        $show_form = true;
        $text_to_display = "";
        if (isset($_REQUEST["status"])) {
            // Sent
            $status = $oWebsite->get_request_int("status");
            $status_text = $oWebsite->get_request_string("status_text");
            $oAuth = $oWebsite->get_authentication();

            $valid = true;

            // Check status id
            if (!$oAuth->is_valid_status($status)) {
                $oWebsite->add_error($oWebsite->t("users.status") . ' ' . $oWebsite->t("errors.not_found"));
                $valid = false;
            }

            // Check status text
            if (!Validate::string_length($status_text, 1, self::MAXIMUM_STATUS_TEXT_LENGTH)) {
                $oWebsite->add_error($oWebsite->t("users.status_text") . " " . Validate::get_last_error($oWebsite));
                $valid = false;
            }

            if ($valid) {
                // Valid status
                $this->user->set_status($status);
                $this->user->set_status_text($status_text);
                if ($this->user->save()) {
                    // Saved
                    $text_to_display.='<p>' . $oWebsite->t("users.status") . ' ' . $oWebsite->t("editor.is_changed") . '</p>';
                    // Don't show form
                    $show_form = false;
                } else {
                    // Database error
                    $text_to_display.='<p><em>' . $oWebsite->t("users.status") . ' ' . $oWebsite->t("errors.not_saved") . '</em></p>';
                }
            } else {
                // Invalid status
                $text_to_display.='<p><em>' . $oWebsite->t_replaced_key("errors.your_input_has_not_been_changed", "users.status", true) . '</em></p>';
            }
        }

        // Show form
        if ($show_form) {
            // Variables
            $status = $oWebsite->get_request_int("status", $this->user->get_status());
            $statuses = array(Authentication::NORMAL_STATUS, Authentication::BANNED_STATUS, Authentication::DELETED_STATUS);
            $status_text = htmlspecialchars($oWebsite->get_request_string("status_text", $this->user->get_status_text()));

            // Form itself
            $text_to_display.=<<<EOT
                <p>
                    {$oWebsite->t("editor.status.edit.explained")}
                    {$oWebsite->t_replaced("editor.account.edit_other", "<strong>" . $this->user->get_display_name() . "</strong>")}
                </p>  
                <p>
                    {$oWebsite->t("main.fields_required")}
                </p>
                <form action="{$oWebsite->get_url_main()}" method="get">
                    <p>
                        <label for="status">{$oWebsite->t("users.status")}</label>:<span class="required">*</span><br />
                        {$this->get_statuses_box_html($oWebsite->get_authentication(), $statuses, $status)}
                    </p>
                    <p>
                        <label for="status_text">{$oWebsite->t("users.status_text")}</label>:<span class="required">*</span><br />
                        <input type="text" name="status_text" id="status_text" size="80" value="$status_text" />
                    </p>
                    <p>
                        <input type="hidden" name="p" value="edit_account_status" />
                        <input type="hidden" name="id" value="{$this->user->get_id()}" />
                        <input type="submit" value="{$oWebsite->t('editor.save')} " class="button" />
                    </p>
                </form>
EOT;
        }

        // Links
        $text_to_display.= $this->get_account_links_html($oWebsite);

        return $text_to_display;
    }

    protected function get_statuses_box_html(Authentication $oAuth, $statuss, $selected) {
        $selection_box = '<select name="status" id="status">';
        foreach ($statuss as $id) {
            $label = $oAuth->get_status_name($id);
            $selection_box.= '<option value="' . $id . '"';
            if ($selected == $id) {
                $selection_box.= ' selected="selected"';
            }
            $selection_box.= '>' . $label . "</option>\n";
        }
        $selection_box.= "</select>\n";
        return $selection_box;
    }

}

$this->register_page(new EditAccountStatusPage());
?>