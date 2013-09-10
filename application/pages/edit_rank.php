<?php

// The rank page extends the password page, since they are highly similar
require_once($this->getUriPage("edit_password"));

class EditRankPage extends EditPasswordPage {

    public function getPageTitle(Website $oWebsite) {
        return $oWebsite->t("editor.rank.edit");
    }

    public function getMinimumRank(Website $oWebsite) {
        return Authentication::$ADMIN_RANK;
    }

    public function getPageContent(Website $oWebsite) {
        // Check selected user
        if ($this->user == null) {
            return "";
        }

        // Don't allow to edit your own rank (why would admins want to downgrade
        // themselves?)
        if (!$this->editing_someone_else) {
            $oWebsite->addError($oWebsite->t("users.account") . " " . $oWebsite->t("errors.not_editable"));
            return "";
        }

        $show_form = true;
        $textToDisplay = "";
        if (isSet($_REQUEST["rank"])) {
            // Sent
            $rank = $oWebsite->getRequestInt("rank");
            $oAuth = $oWebsite->getAuth();
            if ($oAuth->is_valid_rank($rank)) {
                // Valid rank id
                $this->user->setRank($rank);
                if ($this->user->save()) {
                    // Saved
                    $textToDisplay.='<p>' . $oWebsite->t("users.rank") . ' ' . $oWebsite->t("editor.is_changed") . '</p>';
                    // Don't show form
                    $show_form = false;
                } else {
                    // Database error
                    $textToDisplay.='<p><em>' . $oWebsite->t("users.rank") . ' ' . $oWebsite->t("errors.not_saved") . '</em></p>';
                }
            } else {
                // Invalid rank
                $oWebsite->addError($oWebsite->t("users.rank") . ' ' . $oWebsite->t("errors.not_found"));
                $textToDisplay.='<p><em>' . $oWebsite->tReplacedKey("errors.your_input_has_not_been_changed", "users.rank", true) . '</em></p>';
            }
        }

        // Show form
        if ($show_form) {
            // Variables
            $rank = $oWebsite->getRequestInt("rank", $this->user->getRank());
            $ranks = array(Authentication::$USER_RANK, Authentication::$MODERATOR_RANK, Authentication::$ADMIN_RANK);

            // Form itself
            $textToDisplay.=<<<EOT
                <p>
                    {$oWebsite->t("editor.rank.edit.explained")}
                    {$oWebsite->tReplaced("editor.account.edit_other", "<strong>" . $this->user->getDisplayName() . "</strong>")}
                </p>  
                <p>
                    {$oWebsite->t("main.fields_required")}
                </p>
                <form action="{$oWebsite->getUrlMain()}" method="post">
                    <p>
                        <label for="rank">{$oWebsite->t("users.rank")}</label>:<span class="required">*</span><br />
                        {$this->get_ranks_box_html($oWebsite->getAuth(), $ranks, $rank)}
                    </p>
                    <p>
                        <input type="hidden" name="p" value="edit_rank" />
                        <input type="hidden" name="id" value="{$this->user->getId()}" />
                        <input type="submit" value="{$oWebsite->t('editor.rank.edit')} " class="button" />
                    </p>
                </form>
EOT;
        }

        // Links
        $textToDisplay.= $this->get_account_links_html($oWebsite);

        return $textToDisplay;
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

$this->registerPage(new EditRankPage());
?>