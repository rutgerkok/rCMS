<?php

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class EditDisplayNamePage extends EditPasswordPage {

    public function getPageTitle(Website $oWebsite) {
        return $oWebsite->t("editor.display_name.edit");
    }

    public function getPageContent(Website $oWebsite) {
        // Check selected user
        if ($this->user == null) {
            return "";
        }

        $show_form = true;
        $textToDisplay = "";
        if (isSet($_REQUEST["display_name"])) {
            // Sent
            $display_name = $oWebsite->getRequestString("display_name");
            if (Validate::display_name($display_name)) {
                // Valid display_name
                $this->user->setDisplayName($display_name);
                if ($this->user->save()) {
                    // Saved
                    $textToDisplay.='<p>' . $oWebsite->t("users.display_name") . ' ' . $oWebsite->t("editor.is_changed") . '</p>';
                    // Don't show form
                    $show_form = false;
                } else {
                    // Database error
                    $textToDisplay.='<p><em>' . $oWebsite->t("users.display_name") . ' ' . $oWebsite->t("errors.not_saved") . '</em></p>';
                }
            } else {
                // Invalid display_name
                $oWebsite->addError($oWebsite->t("users.display_name") . ' ' . Validate::get_last_error($oWebsite));
                $textToDisplay.='<p><em>' . $oWebsite->tReplacedKey("errors.your_input_has_not_been_changed", "users.display_name", true) . '</em></p>';
            }
        }
        // Show form
        if ($show_form) {
            // Text above form
            $textToDisplay.= "<p>" . $oWebsite->t("editor.display_name.edit.explained") . "</p>\n";
            if ($this->editing_someone_else) {
                $textToDisplay.= "<p><em>" . $oWebsite->tReplaced("editor.account.edit_other", $this->user->getDisplayName()) . "</em></p>\n";
            }

            // Form itself
            $display_name = isSet($_POST['display_name']) ? htmlSpecialChars($_POST['display_name']) : $this->user->getDisplayName();
            $textToDisplay.=<<<EOT
                <p>{$oWebsite->t("main.fields_required")}</p>
                <form action="{$oWebsite->getUrlMain()}" method="post">
                    <p>
                        <label for="display_name">{$oWebsite->t('users.display_name')}:</label><span class="required">*</span><br />
                            <input type="text" id="display_name" name="display_name" value="$display_name"/><br />
                    </p>
                    <p>
                        <input type="hidden" name="id" value="{$this->user->getId()}" />
                        <input type="hidden" name="p" value="edit_display_name" />
                        <input type="submit" value="{$oWebsite->t('editor.display_name.edit')} " class="button" />
                    </p>
                </form>
EOT;
        }

        // Links
        $textToDisplay.= $this->get_account_links_html($oWebsite);

        return $textToDisplay;
    }

}
