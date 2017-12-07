<?php

namespace Rcms\Page;

use Rcms\Core\Text;
use Rcms\Core\Validate;
use Rcms\Core\Request;
use Rcms\Core\Website;

class EditDisplayNamePage extends EditPasswordPage {

    public function getPageTitle(Text $text) {
        return $text->t("users.display_name.edit");
    }

    public function getPageContent(Website $website, Request $request) {
        $show_form = true;
        $textToDisplay = "";
        if (isSet($_REQUEST["display_name"])) {
            // Sent
            $display_name = $request->getRequestString("display_name");
            if (Validate::displayName($display_name)) {
                // Valid display_name
                $this->user->setDisplayName($display_name);
                $userRepo = $website->getUserRepository();
                $userRepo->save($this->user);
                // Saved
                $textToDisplay.='<p>' . $website->t("users.display_name") . ' ' . $website->t("editor.is_changed") . '</p>';
                // Don't show form
                $show_form = false;
            } else {
                // Invalid display_name
                $website->addError($website->t("users.display_name") . ' ' . Validate::getLastError($website));
                $textToDisplay.='<p><em>' . $website->tReplacedKey("errors.your_input_has_not_been_changed", "users.display_name", true) . '</em></p>';
            }
        }
        // Show form
        if ($show_form) {
            // Text above form
            $textToDisplay.= "<p>" . $website->t("users.display_name.edit.explained") . "</p>\n";
            if ($this->editing_someone_else) {
                $textToDisplay.= "<p><em>" . $website->tReplaced("users.edit_other", $this->user->getDisplayName()) . "</em></p>\n";
            }

            // Form itself
            $display_name = isSet($_POST['display_name']) ? htmlSpecialChars($_POST['display_name']) : $this->user->getDisplayName();
            $textToDisplay.=<<<EOT
                <p>{$website->t("main.fields_required")}</p>
                <form action="{$website->getUrlMain()}" method="post">
                    <p>
                        <label for="display_name">{$website->t('users.display_name')}:</label><span class="required">*</span><br />
                            <input type="text" id="display_name" name="display_name" value="$display_name"/><br />
                    </p>
                    <p>
                        <input type="hidden" name="id" value="{$this->user->getId()}" />
                        <input type="hidden" name="p" value="edit_display_name" />
                        <input type="submit" value="{$website->t('users.display_name.edit')} " class="button" />
                    </p>
                </form>
EOT;
        }

        // Links
        $textToDisplay.= $this->get_account_links_html($website, $request);

        return $textToDisplay;
    }

}
