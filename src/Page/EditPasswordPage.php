<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\User;
use Rcms\Core\Validate;
use Rcms\Core\Website;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class EditPasswordPage extends Page {

    /** @var User $user_to_edit */
    protected $user;
    protected $editing_someone_else = false;

    /** Fills the class variables, adds errors if needed. */
    public function init(Website $oWebsite) {
        $this->user = $oWebsite->getAuth()->getCurrentUser();
        $user_id = $oWebsite->getRequestInt("id", 0);
        // Id given to edit someone else, check for permissions
        if ($user_id > 0 && $user_id != $this->user->getId()) {
            $this->editing_someone_else = true;
            if ($this->can_user_edit_someone_else($oWebsite)) {
                // Editing someone else
                $this->user = User::getById($oWebsite, $user_id);
                if ($this->user == null) {
                    // User not found
                    $oWebsite->addError($oWebsite->t("users.account") . " " . $oWebsite->t("errors.not_found"));
                }
            } else {
                // No permissions to edit someone else
                // Set user to null to trigger an error later on
                $this->user = null;
                $oWebsite->addError($oWebsite->t("users.account") . " " . $oWebsite->t("errors.not_editable"));
            }
        }
    }

    /**
     * Returns whether the user viewing this page can edit the account of
     * someone else. By default, only admins can edit someone else, but this
     * can be overriden.
     * @param Website $oWebsite The website object.
     * @return boolean Whether the user can edit someone else.
     */
    public function can_user_edit_someone_else(Website $oWebsite) {
        return $oWebsite->isLoggedInAsStaff(true);
    }

    public function getPageTitle(Website $oWebsite) {
        return $oWebsite->t("editor.password.edit");
    }

    public function getMinimumRank(Website $oWebsite) {
        return Authentication::$USER_RANK;
    }

    public function getPageType() {
        return "BACKSTAGE";
    }

    public function getPageContent(Website $oWebsite) {
        // Check selected user
        if ($this->user == null) {
            return "";
        }

        $show_form = true;
        $textToDisplay = "";
        if (isSet($_REQUEST["password"])) {
            // Sent
            $old_password = $oWebsite->getRequestString("old_password");
            if ($this->editing_someone_else || $this->user->verifyPassword($old_password)) {
                // Old password entered correctly
                $password = $oWebsite->getRequestString("password");
                $password2 = $oWebsite->getRequestString("password2");
                if (Validate::password($password, $password2)) {
                    // Valid password
                    $this->user->setPassword($password);
                    if ($this->user->save()) {
                        // Saved
                        $textToDisplay.='<p>' . $oWebsite->t("users.password") . ' ' . $oWebsite->t("editor.is_changed") . '</p>';
                        // Update login cookie (only when changing your own password)
                        if (!$this->editing_someone_else) {
                            $oWebsite->getAuth()->setLoginCookie();
                        }
                        // Don't show form
                        $show_form = false;
                    } else {
                        // Database error
                        $textToDisplay.='<p><em>' . $oWebsite->t("users.password") . ' ' . $oWebsite->t("errors.not_saved") . '</em></p>';
                    }
                } else {
                    // Invalid new password
                    $oWebsite->addError($oWebsite->t("users.password") . ' ' . Validate::getLastError($oWebsite));
                    $textToDisplay.='<p><em>' . $oWebsite->tReplacedKey("errors.your_input_has_not_been_changed", "users.password", true) . '</em></p>';
                }
            } else {
                // Invalid old password
                $oWebsite->addError($oWebsite->t("users.old_password") . ' ' . $oWebsite->t("errors.not_correct"));
                $textToDisplay.='<p><em>' . $oWebsite->tReplacedKey("errors.your_input_has_not_been_changed", "users.password", true) . '</em></p>';
            }
        }
        // Show form
        if ($show_form) {
            // Text above form
            $textToDisplay.= "<p>" . $oWebsite->tReplaced("editor.password.edit.explained", Validate::$MIN_PASSWORD_LENGHT) . "</p>\n";
            if ($this->editing_someone_else) {
                $textToDisplay.= "<p><em>" . $oWebsite->tReplaced("editor.account.edit_other", $this->user->getDisplayName()) . "</em></p>\n";
            }

            // Form itself
            $old_password_text = "";
            if (!$this->editing_someone_else) {
                // Add field to verify old password when editing yourself
                $old_password_text = <<<EOT
                    <label for="old_password">{$oWebsite->t('users.old_password')}:</label><span class="required">*</span><br />
                    <input type="password" id="old_password" name="old_password" value=""/><br />
EOT;
            }
            $textToDisplay.=<<<EOT
                <p>{$oWebsite->t("main.fields_required")}</p>
                <form action="{$oWebsite->getUrlMain()}" method="post">
                    <p>
                        $old_password_text
                        <label for="password">{$oWebsite->t('users.password')}:</label><span class="required">*</span><br />
                        <input type="password" id="password" name="password" value=""/><br />
                        <label for="password2">{$oWebsite->t('editor.password.repeat')}:</label><span class="required">*</span><br />
                        <input type="password" id="password2" name="password2" value=""/><br />
                    </p>
                    <p>
                        <input type="hidden" name="p" value="edit_password" />
                        <input type="hidden" name="id" value="{$this->user->getId()}" />
                        <input type="submit" value="{$oWebsite->t('editor.password.edit')} " class="button" />
                    </p>
                </form>
EOT;
        }

        // Links
        $textToDisplay.= $this->get_account_links_html($oWebsite);

        return $textToDisplay;
    }

    /** Gets the links for the bottom of the page */
    public function get_account_links_html(Website $oWebsite) {
        $textToDisplay = "";
        if ($this->editing_someone_else) {
            // Editing someone else, don't show "My account" link
            $textToDisplay .= <<<EOT
            <p>
                <a class="arrow" href="{$oWebsite->getUrlPage("account", $this->user->getId())}">
                    {$oWebsite->tReplaced("users.profile_page_of", $this->user->getDisplayName())}
                </a><br />
                <a class="arrow" href="{$oWebsite->getUrlPage("account_management")}">
                    {$oWebsite->t("main.account_management")}
                </a>
EOT;
        } else {
            $textToDisplay .= '<p><a class="arrow" href="' . $oWebsite->getUrlPage("account") . '">' . $oWebsite->t("main.my_account") . "</a>\n";
            if ($oWebsite->isLoggedInAsStaff(true)) {
                $textToDisplay .= '<br /><a class="arrow" href="' . $oWebsite->getUrlPage("account_management") . '">' . $oWebsite->t("main.account_management") . "</a>\n";
            }
            $textToDisplay.= "</p>";
        }
        return $textToDisplay;
    }

}
