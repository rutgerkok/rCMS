<?php

// The rank page extends the password page, since they are highly similar
require_once($this->get_uri_page("edit_password"));

class EditRankPage extends EditPasswordPage {

    public function get_page_title(Website $oWebsite) {
        return $oWebsite->t("editor.rank.edit");
    }

    public function get_minimum_rank(Website $oWebsite) {
        return Authentication::$ADMIN_RANK;
    }

    public function get_page_content(Website $oWebsite) {
        // Check selected user
        if ($this->user == null) {
            return "";
        }

        // Don't allow to edit your own rank (why would admins want to downgrade
        // themselves?)
        if (!$this->editing_someone_else) {
            $oWebsite->add_error($oWebsite->t("users.account") . " " . $oWebsite->t("errors.not_editable"));
            return "";
        }

        $show_form = true;
        $text_to_display = "";
        if (isset($_REQUEST["rank"])) {
            // Sent
            $rank = $oWebsite->get_request_int("rank");
            $oAuth = $oWebsite->get_authentication();
            if ($oAuth->is_valid_rank($rank)) {
                // Valid rank id
                $this->user->set_rank($rank);
                if ($this->user->save()) {
                    // Saved
                    $text_to_display.='<p>' . $oWebsite->t("users.rank") . ' ' . $oWebsite->t("editor.is_changed") . '</p>';
                    // Don't show form
                    $show_form = false;
                } else {
                    // Database error
                    $text_to_display.='<p><em>' . $oWebsite->t("users.rank") . ' ' . $oWebsite->t("errors.not_saved") . '</em></p>';
                }
            } else {
                // Invalid rank
                $oWebsite->add_error($oWebsite->t("users.rank") . ' ' . $oWebsite->t("errors.not_found"));
                $text_to_display.='<p><em>' . $oWebsite->t_replaced_key("errors.your_input_has_not_been_changed", "users.rank", true) . '</em></p>';
            }
        }

        // Show form
        if ($show_form) {
            // Variables
            $rank = $oWebsite->get_request_int("rank", $this->user->get_rank());
            $ranks = array(Authentication::$USER_RANK, Authentication::$MODERATOR_RANK, Authentication::$ADMIN_RANK);

            // Form itself
            $text_to_display.=<<<EOT
                <p>
                    {$oWebsite->t("editor.rank.edit.explained")}
                    {$oWebsite->t_replaced("editor.account.edit_other", "<strong>" . $this->user->get_display_name() . "</strong>")}
                </p>  
                <p>
                    {$oWebsite->t("main.fields_required")}
                </p>
                <form action="{$oWebsite->get_url_main()}" method="post">
                    <p>
                        <label for="rank">{$oWebsite->t("users.rank")}</label>:<span class="required">*</span><br />
                        {$this->get_ranks_box_html($oWebsite->get_authentication(), $ranks, $rank)}
                    </p>
                    <p>
                        <input type="hidden" name="p" value="edit_rank" />
                        <input type="hidden" name="id" value="{$this->user->get_id()}" />
                        <input type="submit" value="{$oWebsite->t('editor.rank.edit')} " class="button" />
                    </p>
                </form>
EOT;
        }

        // Links
        $text_to_display.= $this->get_account_links_html($oWebsite);

        return $text_to_display;
    }

    protected function get_ranks_box_html(Authentication $oAuth, $ranks, $selected) {
        $selection_box = '<select name="rank" id="rank">';
        foreach ($ranks as $id) {
            $label = $oAuth->get_rank_name($id);
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

$this->register_page(new EditRankPage());
?>