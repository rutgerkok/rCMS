<?php

namespace Rcms\Page;

use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\Request;
use Rcms\Core\Website;

class EditEmailPage extends EditPasswordPage {

    public function getPageTitle(Text $text) {
        return $text->t("users.email.edit");
    }

    public function getPageContent(Website $website, Request $request) {
        $show_form = true;
        $textToDisplay = "";
        if ($request->hasRequestValue("email")) {
            // Sent
            $email = $request->getRequestString("email");
            if (Validate::email($email)) {
                // Valid email
                $this->user->setEmail($email);
                $userRepo = $website->getAuth()->getUserRepository();
                $userRepo->save($this->user);
                // Saved
                $textToDisplay.='<p>' . $website->t("users.email") . ' ' . $website->t("editor.is_changed") . '</p>';
                // Don't show form
                $show_form = false;
            } else {
                // Invalid email
                $website->addError($website->t("users.email") . ' ' . Validate::getLastError($website));
                $textToDisplay.='<p><em>' . $website->tReplacedKey("errors.your_input_has_not_been_changed", "users.email", true) . '</em></p>';
            }
        }
        // Show form
        if ($show_form) {
            // Text above form
            $textToDisplay.= "<p>" . $website->t("users.email.edit.explained") . "</p>\n";
            if ($this->editing_someone_else) {
                $textToDisplay.= "<p><em>" . $website->tReplaced("users.edit_other", $this->user->getDisplayName()) . "</em></p>\n";
            }

            // Form itself
            $email = htmlSpecialChars($request->getRequestString("email", $this->user->getEmail()));
            $textToDisplay.=<<<EOT
                <form action="{$website->getUrlMain()}" method="post">
                    <p>
                        <label for="email">{$website->t('users.email')}:</label><br /><input type="text" id="email" name="email" value="$email"/><br />
                    </p>
                    <p>
                        <input type="hidden" name="id" value="{$this->user->getId()}" />
                        <input type="hidden" name="p" value="edit_email" />
                        <input type="submit" value="{$website->t('users.email.edit')} " class="button" />
                    </p>
                </form>
EOT;
        }

        // Links
        $textToDisplay.= $this->get_account_links_html($website);

        return $textToDisplay;
    }

}
