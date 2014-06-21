<?php

namespace Rcms\Page;

use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\Request;

class EditEmailPage extends EditPasswordPage {

    public function getPageTitle(Text $text) {
        return $text->t("editor.email.edit");
    }

    public function getPageContent(Request $request) {
        // Check selected user
        if ($this->user == null) {
            return "";
        }

        $oWebsite = $request->getWebsite();
        $show_form = true;
        $textToDisplay = "";
        if ($request->hasRequestValue("email")) {
            // Sent
            $email = $request->getRequestString("email");
            if (Validate::email($email)) {
                // Valid email
                $this->user->setEmail($email);
                if ($this->user->save()) {
                    // Saved
                    $textToDisplay.='<p>' . $oWebsite->t("users.email") . ' ' . $oWebsite->t("editor.is_changed") . '</p>';
                    // Don't show form
                    $show_form = false;
                } else {
                    // Database error
                    $textToDisplay.='<p><em>' . $oWebsite->t("users.email") . ' ' . $oWebsite->t("errors.not_saved") . '</em></p>';
                }
            } else {
                // Invalid email
                $oWebsite->addError($oWebsite->t("users.email") . ' ' . Validate::getLastError($oWebsite));
                $textToDisplay.='<p><em>' . $oWebsite->tReplacedKey("errors.your_input_has_not_been_changed", "users.email", true) . '</em></p>';
            }
        }
        // Show form
        if ($show_form) {
            // Text above form
            $textToDisplay.= "<p>" . $oWebsite->t("editor.email.edit.explained") . "</p>\n";
            if ($this->editing_someone_else) {
                $textToDisplay.= "<p><em>" . $oWebsite->tReplaced("editor.account.edit_other", $this->user->getDisplayName()) . "</em></p>\n";
            }

            // Form itself
            $email = htmlSpecialChars($request->getRequestString("email", $this->user->getEmail()));
            $textToDisplay.=<<<EOT
                <form action="{$oWebsite->getUrlMain()}" method="post">
                    <p>
                        <label for="email">{$oWebsite->t('users.email')}:</label><br /><input type="text" id="email" name="email" value="$email"/><br />
                    </p>
                    <p>
                        <input type="hidden" name="id" value="{$this->user->getId()}" />
                        <input type="hidden" name="p" value="edit_email" />
                        <input type="submit" value="{$oWebsite->t('editor.email.edit')} " class="button" />
                    </p>
                </form>
EOT;
        }

        // Links
        $textToDisplay.= $this->get_account_links_html($oWebsite);

        return $textToDisplay;
    }

}
