<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Request;
use Rcms\Core\Validate;
use Rcms\Core\Website;

class EditAccountStatusPage extends EditPasswordPage {

    const MAXIMUM_STATUS_TEXT_LENGTH = 255;

    public function getPageTitle(Request $request) {
        return $request->getWebsite()->t("editor.status.edit");
    }

    public function getMinimumRank(Request $request) {
        return Authentication::$MODERATOR_RANK;
    }

    // Overrided to allow moderators to (un)block someone else
    public function can_user_edit_someone_else(Website $oWebsite) {
        return $oWebsite->isLoggedInAsStaff(false);
    }

    public function getPageContent(Request $request) {
        $oWebsite = $request->getWebsite();
        // Check selected user
        if ($this->user == null) {
            return "";
        }

        // Don't allow to edit your own status (why would admins want to downgrade
        // themselves?)
        if (!$this->editing_someone_else) {
            $oWebsite->addError($oWebsite->t("users.account") . " " . $oWebsite->t("errors.not_editable"));
            return "";
        }

        $show_form = true;
        $textToDisplay = "";
        if ($request->hasRequestValue("status")) {
            // Sent
            $status = $request->getRequestInt("status");
            $status_text = $request->getRequestString("status_text");
            $oAuth = $oWebsite->getAuth();

            $valid = true;

            // Check status id
            if (!$oAuth->isValidStatus($status)) {
                $oWebsite->addError($oWebsite->t("users.status") . ' ' . $oWebsite->t("errors.not_found"));
                $valid = false;
            }

            // Check status text
            if (!Validate::stringLength($status_text, 1, self::MAXIMUM_STATUS_TEXT_LENGTH)) {
                $oWebsite->addError($oWebsite->t("users.status_text") . " " . Validate::getLastError($oWebsite));
                $valid = false;
            }

            if ($valid) {
                // Valid status
                $this->user->setStatus($status);
                $this->user->setStatusText($status_text);
                if ($this->user->save()) {
                    // Saved
                    $textToDisplay.='<p>' . $oWebsite->t("users.status") . ' ' . $oWebsite->t("editor.is_changed") . '</p>';
                    // Don't show form
                    $show_form = false;
                } else {
                    // Database error
                    $textToDisplay.='<p><em>' . $oWebsite->t("users.status") . ' ' . $oWebsite->t("errors.not_saved") . '</em></p>';
                }
            } else {
                // Invalid status
                $textToDisplay.='<p><em>' . $oWebsite->tReplacedKey("errors.your_input_has_not_been_changed", "users.status", true) . '</em></p>';
            }
        }

        // Show form
        if ($show_form) {
            // Variables
            $status = $oWebsite->getRequestInt("status", $this->user->getStatus());
            $statuses = array(Authentication::NORMAL_STATUS, Authentication::BANNED_STATUS, Authentication::DELETED_STATUS);
            $status_text = htmlSpecialChars($request->getRequestString("status_text", $this->user->getStatusText()));

            // Form itself
            $textToDisplay.=<<<EOT
                <p>
                    {$oWebsite->t("editor.status.edit.explained")}
                    {$oWebsite->tReplaced("editor.account.edit_other", "<strong>" . $this->user->getDisplayName() . "</strong>")}
                </p>  
                <p>
                    {$oWebsite->t("main.fields_required")}
                </p>
                <form action="{$oWebsite->getUrlMain()}" method="get">
                    <p>
                        <label for="status">{$oWebsite->t("users.status")}</label>:<span class="required">*</span><br />
                        {$this->get_statuses_box_html($oWebsite->getAuth(), $statuses, $status)}
                    </p>
                    <p>
                        <label for="status_text">{$oWebsite->t("users.status_text")}</label>:<span class="required">*</span><br />
                        <input type="text" name="status_text" id="status_text" size="80" value="$status_text" />
                    </p>
                    <p>
                        <input type="hidden" name="p" value="edit_account_status" />
                        <input type="hidden" name="id" value="{$this->user->getId()}" />
                        <input type="submit" value="{$oWebsite->t('editor.save')} " class="button" />
                    </p>
                </form>
EOT;
        }

        // Links
        $textToDisplay.= $this->get_account_links_html($oWebsite);

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
