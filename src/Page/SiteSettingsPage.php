<?php

namespace Rcms\Page;

use Rcms\Core\Authentication;
use Rcms\Core\Text;
use Rcms\Core\Request;
use Rcms\Core\RequestToken;
use Rcms\Core\Validate;
use Rcms\Core\Website;

class SiteSettingsPage extends Page {

    protected $title;
    protected $copyright;
    protected $password;
    protected $language;
    protected $theme;
    protected $user_account_creation;
    protected $saved = false;
    protected $token;

    public function init(Website $website, Request $request) {
        $this->title = $website->getConfig()->get("title");
        $this->copyright = $website->getConfig()->get("copyright");
        $this->password = $website->getConfig()->get("password");
        $this->language = $website->getConfig()->get("language");
        $this->theme = $website->getConfig()->get("theme");
        $this->user_account_creation = $website->getConfig()->get("user_account_creation");

        if (isSet($_REQUEST["submit"]) && Validate::requestToken($request)) {
            $this->save_values($website);
            $this->saved = true;
        }

        // Refresh token
        $this->token = RequestToken::generateNew();
        $this->token->saveToSession();
    }

    public function getPageType() {
        return Page::TYPE_BACKSTAGE;
    }

    public function getPageTitle(Text $text) {
        return $text->t("site_settings.editing_site_settings");
    }

    public function getShortPageTitle(Text $text) {
        return $text->t("main.site_settings");
    }

    public function getMinimumRank(Request $request) {
        return Authentication::RANK_ADMIN;
    }

    public function getPageContent(Website $website, Request $request) {
        $themes = $website->getThemeManager()->getAllThemeNames();
        $languages = $this->get_sub_directory_names($website->getUriTranslations());
        $user_account_creation_checked = $this->user_account_creation ? 'checked="checked"' : '';
        $top_message = $website->t("site_settings.editing_site_settings.explained");
        $tokenName = RequestToken::FIELD_NAME;
        $tokenHtml = htmlSpecialChars($this->token->getTokenString());

        if ($this->saved) {
            $top_message = <<<EOT
                <em>{$website->t("site_settings.site_settings")} {$website->t("editor.are_changed")}</em>
                <a class="arrow" href="{$website->getUrlPage("admin")}">
                    {$website->t("main.admin")}
                </a>
EOT;
        }

        return <<<EOT
            <p>
                $top_message
            </p>
            <p>
                {$website->t("main.fields_required")}
            </p>
            <form action="{$website->getUrlPage("site_settings")}" method="post">
                <p>
                    <label for="option_title">{$website->t("site_settings.title")}</label>:<span class="required">*</span>
                    <br />
                    <input type="text" name="option_title" id="option_title" value="{$this->title}" />
                    <br />
                    <em>{$website->t("site_settings.title.explained")}</em>
                </p>
                <p>
                    <label for="option_copyright">{$website->t("site_settings.copyright")}</label>:
                    <br />
                    <input type="text" name="option_copyright" id="option_copyright" value="{$this->copyright}" />
                    <br />
                    <em>{$website->t("site_settings.copyright.explained")}</em>
                </p>
                <p>
                    <label for="option_password">{$website->t("site_settings.password")}</label>:
                    <br />
                    <input type="text" name="option_password" id="option_password" value="{$this->password}" />
                    <br />
                    <em>{$website->t("site_settings.password.explained")}</em>
                </p>
                <p>
                    <label for="option_language">{$website->t("site_settings.language")}</label>:<span class="required">*</span>
                    <br />
                    {$this->get_dropdown_list("option_language", $languages, $this->language, true)}
                    <br />
                    <em>{$website->t("site_settings.language.explained")}</em>
                </p>
                <p>
                    <label for="option_theme">{$website->t("site_settings.theme")}</label>:<span class="required">*</span>
                    <br />
                    {$this->get_dropdown_list("option_theme", $themes, $this->theme, true)}
                    <br />
                    <em>{$website->t("site_settings.theme.explained")}</em>
                </p>
                <p>
                    <label for="option_user_account_creation">
                        <input class="checkbox" type="checkbox" name="option_user_account_creation" id="option_user_account_creation" $user_account_creation_checked />
                        {$website->t("site_settings.user_account_creation")}
                    </label>
                    <br />
                    <em>{$website->t("site_settings.user_account_creation.explained")}</em>
                </p>
                <p>
                    <input type="hidden" name="$tokenName" value="$tokenHtml" />
                    <input type="submit" name="submit" class="button primary_button" value="{$website->t("editor.save")}" />
                </p>
            </form>
            <p>
                <a class="arrow" href="{$website->getUrlPage("admin")}">
                    {$website->t("main.admin")}
                </a>
            </p>
EOT;
    }

    protected function save_values(Website $website) {
        $config = $website->getConfig();
        $database = $website->getDatabase();

        // Title, copyright, password
        $this->save_string($website, "title", false);
        $this->save_string($website, "copyright", true);
        $this->save_string($website, "password", true);

        // If a password is set, pass it as a parameter, to avoid getting locked out
        if (!empty($this->password)) {
            $_POST["key"] = $this->password;
            setCookie("key", $this->password, time() + 3600 * 24 * 365, "/");
        }

        // Whether users can create accounts
        if (isSet($_REQUEST["option_user_account_creation"])) {
            $this->user_account_creation = true;
            $config->set($database, "user_account_creation", true);
        } else {
            $this->user_account_creation = false;
            $config->set($database, "user_account_creation", false);
        }

        // Language
        $language = $website->getRequestString("option_language", $this->language);
        if (is_dir($website->getUriTranslations($language))) {
            $this->language = $language;
            $config->set($database, "language", $language);
        } else {
            $website->addError($website->t("site_settings.language") . " " . $website->t("errors.not_found"));
        }

        // Theme
        $theme = $website->getRequestString("option_theme", $this->theme);
        if ($website->getThemeManager()->themeExists($theme)) {
            $this->theme = $theme;
            $config->set($database, "theme", $theme);
        } else {
            $website->addError($website->t("site_settings.theme") . " " . $website->t("errors.not_found"));
        }
    }

    protected function save_string(Website $website, $name, $optional) {
        $value = trim($website->getRequestString("option_$name", $this->$name));
        if ($optional || !empty($value)) {
            $this->$name = substr($value, 0, Website::MAX_SITE_OPTION_LENGTH);
            $website->getConfig()->set($website->getDatabase(), $name, $this->$name);
        } else {
            $website->addError($website->t("site_settings.$name") . " " . $website->t("errors.not_found"));
        }
    }

    /**
     * Gets all non-hidden direct subdirectories as a 1-dimensional
     * array in the given directory. If the given directory doesn't exist, an
     * empty array is returned.
     * @param string $directory_to_scan THe directory to scan.
     * @return \string The file names, without the path.
     */
    protected function get_sub_directory_names($directory_to_scan) {
        $results = array();
        if (is_dir($directory_to_scan)) {
            $files = scanDir($directory_to_scan);
            foreach ($files as $file_name) {
                if ($file_name{0} != '.') {
                    // Ignore hidden files and directories above this one
                    if (is_dir($directory_to_scan . $file_name)) {
                        $results[] = $file_name;
                    }
                }
            }
        }

        return $results;
    }

    /**
     * Gets a dropdown list using the select tag.
     * @param string $name Name and id of the box.
     * @param array $options The options.
     * @param string $selected The id of the currently selected option.
     * @param boolean $options_one_dimensional Set this to true if the options
     * are a one-dimensional array.
     * @return string The selection box.
     */
    protected function get_dropdown_list($name, $options, $selected,
            $options_one_dimensional) {
        $returnValue = '<select id="' . $name . '" name="' . $name . '">' . "\n";
        foreach ($options as $id => $value) {
            if ($options_one_dimensional) {
                $id = $value; // One dimensional array, so those are the same
            }
            $returnValue.= '<option value="' . $id . '"';
            if ($id == $selected) {
                $returnValue.= ' selected="selected"';
            }
            $returnValue.= '>' . $value . "</option>\n";
        }
        $returnValue.= "</select>\n";
        return $returnValue;
    }

}
