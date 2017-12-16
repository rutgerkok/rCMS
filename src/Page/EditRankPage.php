<?php

namespace Rcms\Page;

use Rcms\Core\Ranks;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Website;

class EditRankPage extends EditPasswordPage {

    public function getPageTitle(Text $text) {
        return $text->t("users.rank.edit");
    }

    public function getMinimumRank() {
        return Ranks::ADMIN;
    }

    public function getPageContent(Website $website, Request $request) {
        // Don't allow to edit your own rank (why would admins want to downgrade
        // themselves?)
        if (!$this->editing_someone_else) {
            $website->addError($website->t("users.account") . " " . $website->t("errors.not_editable"));
            return "";
        }

        $show_form = true;
        $textToDisplay = "";
        if ($request->hasRequestValue("rank")) {
            // Sent
            $rank = $request->getRequestInt("rank");
            $oAuth = $website->getRanks();
            if ($oAuth->isValidRankForAccounts($rank)) {
                // Valid rank id
                $this->user->setRank($rank);
                $userRepo = $website->getUserRepository();
                $userRepo->save($this->user);
                // Saved
                $textToDisplay.='<p>' . $website->t("users.rank") . ' ' . $website->t("editor.is_changed") . '</p>';
                // Don't show form
                $show_form = false;
            } else {
                // Invalid rank
                $website->addError($website->t("users.rank") . ' ' . $website->t("errors.not_found"));
                $textToDisplay.='<p><em>' . $website->tReplacedKey("errors.your_input_has_not_been_changed", "users.rank", true) . '</em></p>';
            }
        }

        // Show form
        if ($show_form) {
            // Variables
            $rank = $request->getRequestInt("rank", $this->user->getRank());
            $ranks = array(Ranks::USER, Ranks::MODERATOR, Ranks::ADMIN);

            // Form itself
            $textToDisplay.=<<<EOT
                <p>
                    {$website->t("users.rank.edit.explained")}
                    {$website->tReplaced("accounts.edit_other", "<strong>" . $this->user->getDisplayName() . "</strong>")}
                </p>
                <p>
                    {$website->t("main.fields_required")}
                </p>
                <form action="{$website->getUrlMain()}" method="post">
                    <p>
                        <label for="rank">{$website->t("users.rank")}</label>:<span class="required">*</span><br />
                        {$this->get_ranks_box_html($website, $request, $ranks, $rank)}
                    </p>
                    <p>
                        <input type="hidden" name="p" value="edit_rank" />
                        <input type="hidden" name="id" value="{$this->user->getId()}" />
                        <input type="submit" value="{$website->t('users.rank.edit')} " class="button" />
                    </p>
                </form>
EOT;
        }

        // Links
        $textToDisplay.= $this->get_account_links_html($website, $request);

        return $textToDisplay;
    }

    protected function get_ranks_box_html(Website $website, Request $request,
            $ranks, $selected) {
        $oAuth = $website->getRanks();
        $text = $website->getText();

        $selection_box = '<select name="rank" id="rank">';
        foreach ($ranks as $id) {
            $label = $text->t($oAuth->getRankName($id));
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
