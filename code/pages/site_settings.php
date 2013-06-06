<?php

class SiteSettingsPage extends Page {

    protected $title;
    protected $copyright;
    protected $password;
    protected $language;
    protected $theme;
    protected $user_account_creation;
    
    protected $saved = false;

    public function init(Website $oWebsite) {
        $this->title = $oWebsite->get_sitevar("title");
        $this->copyright = $oWebsite->get_sitevar("copyright");
        $this->password = $oWebsite->get_sitevar("password");
        $this->language = $oWebsite->get_sitevar("language");
        $this->theme = $oWebsite->get_sitevar("theme");
        $this->user_account_creation = $oWebsite->get_sitevar("user_account_creation");

        if (isset($_REQUEST["submit"])) {
            $this->save_values($oWebsite);
            $this->saved = true;
        }
    }

    public function get_page_type() {
        return "BACKSTAGE";
    }

    public function get_page_title(Website $oWebsite) {
        return $oWebsite->t("site_settings.editing_site_settings");
    }
    
    public function get_short_page_title(Website $oWebsite) {
        return $oWebsite->t("main.site_settings");
    }

    public function get_minimum_rank() {
        return Authentication::$ADMIN_RANK;
    }

    public function get_page_content(Website $oWebsite) {
        $themes = $this->get_sub_directory_names($oWebsite->get_uri_themes());
        $languages = $this->get_sub_directory_names($oWebsite->get_uri_translations());
        $user_account_creation_checked = $this->user_account_creation? 'checked="checked"' : '';
        $top_message = $oWebsite->t("site_settings.editing_site_settings.explained");

        if($this->saved) {
            $top_message = <<<EOT
                <em>{$oWebsite->t("site_settings.site_settings")} {$oWebsite->t("editor.are_changed")}</em>
                <a class="arrow" href="{$oWebsite->get_url_page("admin")}">
                    {$oWebsite->t("main.admin")}
                </a>
EOT;
        }
        
        return <<<EOT
            <p>
                $top_message
            </p>
            <p>
                {$oWebsite->t("main.fields_required")}
            </p>
            <form action="{$oWebsite->get_url_main()}" method="post">
                <p>
                    <label for="option_title">{$oWebsite->t("site_settings.title")}</label>:<span class="required">*</span>
                    <br />
                    <input type="text" name="option_title" id="option_title" value="{$this->title}" />
                    <br />
                    <em>{$oWebsite->t("site_settings.title.explained")}</em>
                </p>
                <p>
                    <label for="option_copyright">{$oWebsite->t("site_settings.copyright")}</label>:
                    <br />
                    <input type="text" name="option_copyright" id="option_copyright" value="{$this->copyright}" />
                    <br />
                    <em>{$oWebsite->t("site_settings.copyright.explained")}</em>
                </p>
                <p>
                    <label for="option_password">{$oWebsite->t("site_settings.password")}</label>:
                    <br />
                    <input type="text" name="option_password" id="option_password" value="{$this->password}" />
                    <br />
                    <em>{$oWebsite->t("site_settings.password.explained")}</em>
                </p>
                <p>
                    <label for="option_language">{$oWebsite->t("site_settings.language")}</label>:<span class="required">*</span>
                    <br />
                    {$this->get_dropdown_list("option_language", $languages, $this->language, true)}
                    <br />
                    <em>{$oWebsite->t("site_settings.language.explained")}</em>
                </p>
                <p>
                    <label for="option_theme">{$oWebsite->t("site_settings.theme")}</label>:<span class="required">*</span>
                    <br />
                    {$this->get_dropdown_list("option_theme", $themes, $this->theme, true)}
                    <br />
                    <em>{$oWebsite->t("site_settings.theme.explained")}</em>
                </p>
                <p>
                    <label for="option_user_account_creation">
                        <input class="checkbox" type="checkbox" name="option_user_account_creation" id="option_user_account_creation" $user_account_creation_checked />
                        {$oWebsite->t("site_settings.user_account_creation")}
                    </label>
                    <br />
                    <em>{$oWebsite->t("site_settings.user_account_creation.explained")}</em>
                </p>
                <p>
                    <input type="hidden" name="p" value="{$oWebsite->get_page_id()}" />
                    <input type="submit" name="submit" class="button" value="{$oWebsite->t("editor.save")}" />
                </p>
            </form>
            <p>
                <a class="arrow" href="{$oWebsite->get_url_page("admin")}">
                    {$oWebsite->t("main.admin")}
                </a>
            </p>
EOT;
    }

    protected function save_values(Website $oWebsite) {
        // Title, copyright, password
        $this->save_string($oWebsite, "title", false);
        $this->save_string($oWebsite, "copyright", true);
        $this->save_string($oWebsite, "password", true);
        
        // If a password is set, pass it as a parameter, to avoid getting locked out
        if(!empty($this->password)) {
            $_POST["key"] = $this->password;
            setcookie("key", $this->password, time() + 3600 * 24 * 365, "/");
        }
        
        // Whether users can create accounts
        if(isset($_REQUEST["option_user_account_creation"])) {
            $this->user_account_creation = true;
            $oWebsite->set_sitevar("user_account_creation", true);
        } else {
            $this->user_account_creation = false;
            $oWebsite->set_sitevar("user_account_creation", false);
        }

        // Language
        $language = $oWebsite->get_request_string("option_language", $this->language);
        if (is_dir($oWebsite->get_uri_translations() . $language . '/')) {
            $this->language = $language;
            $oWebsite->set_sitevar("language", $language);
        } else {
            $oWebsite->add_error($oWebsite->t("site_settings.language") . " " . $oWebsite->t("errors.not_found"));
        }

        // Theme
        $theme = $oWebsite->get_request_string("option_theme", $this->theme);
        if (is_dir($oWebsite->get_uri_themes() . $theme . '/')) {
            $this->theme = $theme;
            $oWebsite->set_sitevar("theme", $theme);
        } else {
            $oWebsite->add_error($oWebsite->t("site_settings.theme") . " " . $oWebsite->t("errors.not_found"));
        }
    }

    protected function save_string(Website $oWebsite, $name, $optional) {
        $value = trim($oWebsite->get_request_string("option_$name", $this->$name));
        if ($optional || !empty($value)) {
            $this->$name = substr($value, 0, Website::MAX_SITE_OPTION_LENGTH);
            $oWebsite->set_sitevar($name, $this->$name);
        } else {
            $oWebsite->add_error($oWebsite->t("site_settings.$name") . " " . $oWebsite->t("errors.not_found"));
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
            $files = scandir($directory_to_scan);
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
    protected function get_dropdown_list($name, $options, $selected, $options_one_dimensional) {
        $return_value = '<select id="' . $name . '" name="' . $name . '">' . "\n";
        foreach ($options as $id => $value) {
            if ($options_one_dimensional) {
                $id = $value; // One dimensional array, so those are the same
            }
            $return_value.= '<option value="' . $id . '"';
            if ($id == $selected) {
                $return_value.= ' selected="selected"';
            }
            $return_value.= '>' . $value . "</option>\n";
        }
        $return_value.= "</select>\n";
        return $return_value;
    }

}

$this->register_page(new SiteSettingsPage());
?>