<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\Validate;
use Rcms\Core\Website;

class EditAccountStatusPage extends EditPasswordPage {

    const MAXIMUM_STATUS_TEXT_LENGTH = 255;

    public function getPageTitle(Text $text) {
        return $text->t("editor.status.edit");
    }

    public function getMinimumRank(Request $request) {
        return Authentication::RANK_MODERATOR;
    }

    // Overrided to allow moderators to (un)block someone else
    public function can_user_edit_someone_else(Website $website) {
        return $website->isLoggedInAsStaff(false);
    }

    public function getPageContent(Website $website, Request $request) {
        // Don't allow to edit your own status (why would admins want to downgrade
        // themselves?)
        if (!$this->editing_someone_else) {
            $website->addError($website->t("users.account") . " " . $website->t("errors.not_editable"));
            return "";
        }

        $show_form = true;
        $textToDisplay = "";
        if ($request->hasRequestValue("status")) {
            // Sent
            $status = $request->getRequestInt("status");
            $status_text = $request->getRequestString("status_text");
            $oAuth = $website->getAuth();

            $valid = true;

            // Check status id
            if (!$oAuth->isValidStatus($status)) {
                $website->addError($website->t("users.status") . ' ' . $website->t("errors.not_found"));
                $valid = false;
            }

            // Check status text
            if (!Validate::stringLength($status_text, 1, self::MAXIMUM_STATUS_TEXT_LENGTH)) {
                $website->addError($website->t("users.status_text") . " " . Validate::getLastError($website));
                $valid = false;
            }

            if ($valid) {
                // Valid status
                $this->user->setStatus($status);
                $this->user->setStatusText($status_text);
                $oAuth->getUserRepository()->save($this->user);
                // Saved
                $textToDisplay.='<p>' . $website->t("users.status") . ' ' . $website->t("editor.is_changed") . '</p>';
                // Don't show form
                $show_form = false;
            } else {
                // Invalid status
                $textToDisplay.='<p><em>' . $website->tReplacedKey("errors.your_input_has_not_been_changed", "users.status", true) . '</em></p>';
            }
        }

        // Show form
        if ($show_form) {
            // Variables
            $status = $website->getRequestInt("status", $this->user->getStatus());
            $statuses = array(Authentication::STATUS_NORMAL, Authentication::STATUS_BANNED, Authentication::STATUS_DELETED);
            $status_text = htmlSpecialChars($request->getRequestString("status_text", $this->user->getStatusText()));

            // Form itself
            $textToDisplay.=<<<EOT
                <p>
                    {$website->t("editor.status.edit.explained")}
                    {$website->tReplaced("editor.account.edit_other", "<strong>" . $this->user->getDisplayName() . "</strong>")}
                </p>  
                <p>
                    {$website->t("main.fields_required")}
                </p>
                <form action="{$website->getUrlMain()}" method="get">
                    <p>
                        <label for="status">{$website->t("users.status")}</label>:<span class="required">*</span><br />
                        {$this->get_statuses_box_html($website->getAuth(), $statuses, $status)}
                    </p>
                    <p>
                        <label for="status_text">{$website->t("users.status_text")}</label>:<span class="required">*</span><br />
                        <input type="text" name="status_text" id="status_text" size="80" value="$status_text" />
                    </p>
                    <p>
                        <input type="hidden" name="p" value="edit_account_status" />
                        <input type="hidden" name="id" value="{$this->user->getId()}" />
                        <input type="submit" value="{$website->t('editor.save')} " class="button" />
                    </p>
                </form>
EOT;
        }

        // Links
        $textToDisplay.= $this->get_account_links_html($website);

        return $textToDisplay;
    }

    protected function get_statuses_box_html(Authentication $oAuth, $statuss,
            $selected) {
        $selection_box = '<select name="status" id="status">';
        foreach ($statuss as $id) {
            $label = $oAuth->getStatusName($id);
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
