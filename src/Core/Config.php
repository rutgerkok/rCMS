<?php

namespace Rcms\Core;

use PDO;
use PDOException;

/**
 * Holds all settings of the site, both from options.php and the database.
 */
class Config {

    /**
     * Schema version of the database.
     * @see #isDatabaseUpToDate
     */
    const CURRENT_DATABASE_VERSION = 6;

    const DEFAULT_LANGUAGE = "en";
    const DEFAULT_THEME = "temp";
    const DEFAULT_TITLE = "Welcome!";

    const OPTION_CKEDITOR_URL = "url_ckeditor";
    const OPTION_CKFINDER_URL = "url_ckfinder";

    const OPTION_DATABASE_NAME = "database_name";
    const OPTION_DATABASE_HOST = "database_location";
    const OPTION_DATABASE_USER = "database_user";
    const OPTION_DATABASE_PASSWORD = "database_password";
    const OPTION_DATABASE_TABLE_PREFIX = "database_table_prefix";
    const OPTION_DATABASE_VERSION = "database_version";

    const OPTION_ACCESS_CODE = "password";
    const OPTION_THEME = "theme";
    const OPTION_COPYRIGHT = "copyright";
    const OPTION_MAIN_MENU_ID = "main_menu_id";
    const OPTION_SITE_TITLE = "title";
    const OPTION_USER_ACCOUNT_CREATION = "user_account_creation";

    const OPTION_MAIL_TYPE = "mail_type";
    const OPTION_MAIL_HOST = "mail_host";
    const OPTION_MAIL_PORT = "mail_port";
    const OPTION_MAIL_ENCRYPTION = "mail_encryption";
    const OPTION_MAIL_USERNAME = "mail_username";
    const OPTION_MAIL_PASSWORD = "mail_password";
    const OPTION_MAIL_FROM = "mail_from";

    private $config = [];

    /**
     * Creates a new Config instance. The settings will be read from the
     * provided file. Make sure to call the readFromDatabase method after this
     * to get all remaining settings that are stored in the database.
     * @param string $configFile Path to the options.php file. Warning: this
     * path is not checked, since it the path is assumed to be static.
     */
    public function __construct($configFile) {
        $this->readFromFile($configFile);
    }

    protected function readFromFile($file) {
        // Apply some standard settings to get the site running (in case those
        // cannot be loaded from the database)
        $this->config["language"] = self::DEFAULT_LANGUAGE;
        $this->config["theme"] = self::DEFAULT_THEME;
        $this->config["title"] = self::DEFAULT_TITLE;

        // Load other settings from config.php (required)
        if (file_exists($file)) {
            require($file);
        } else {
            echo "<code>" . $file . "</code> not found. Please create a config file. See the <code>config.sample.php</code> file for details.";
            die();
        }
    }

    /**
     * Loads the settings from the database. Requires that there is a table
     * called `settings` in the database with the columns `setting_name` and
     * `setting_value`. If not, this method will do nothing.
     * @param PDO $database The database to load the settings from.
     */
    public function readFromDatabase(PDO $database) {
        try {
            $result = $database->query("SELECT `setting_name`, `setting_value` FROM `settings`");
            while (list($key, $value) = $result->fetch(PDO::FETCH_NUM)) {
                $this->config[$key] = $value;
            }
        } catch (PDOException $e) {
            // No settings table in database - either database is not installed,
            // or severly outdated, from a time when there was no settings table
        }
    }

    /**
     * Gets a setting from either the options.php or the settings table.
     * Requires that there is a table called `settings` in the database
     * with the columns `setting_name` and `setting_value`.
     * @param string $name Name of the setting.
     * @param mixed $default Default value for the setting
     * @return mixed default value if not found, otherwise the value.
     */
    public function get($name, $default = false) {
        if (isSet($this->config[$name])) {
            $value = $this->config[$name];
            if (strToLower($value) === "false") {
                // Because "false" == true
                return false;
            }
            return $value;
        } else {
            return $default;
        }
    }

    /**
     * Changes a setting. Saves a setting to the database. Does nothing if the
     * setting is unchanged.
     * @param PDO $database The database to save the setting to.
     * @param string $name The name of the setting.
     * @param string $value The value of the setting.
     */
    public function set(PDO $database, $name, $value) {
        if (isSet($this->config[$name]) && $this->config[$name] == $value) {
            // No need to update
            return;
        }

        // Save to database
        if (isSet($this->config[$name])) {
            // Update setting
            $sql = "UPDATE `settings` SET ";
            $sql.= "`setting_value` = :value ";
            $sql.= "WHERE `setting_name` = :name";
            $database->prepare($sql)->execute([":name" => $name, ":value" => $value]);
        } else {
            // New setting
            $sql = "INSERT INTO `settings` (`setting_name`, `setting_value`) ";
            $sql.= " VALUES (:name, :value)";
            $database->prepare($sql)->execute([":name" => $name, ":value" => $value]);
        }

        // Apply on current page
        $this->config[$name] = $value;
    }

    /**
     * Returns whether the database is up to date with the current database
     * schema. If the database is not installed yet, it is considered outdated.
     * @return boolean True if the database is installed and up to date, false otherwise.
     */
    public function isDatabaseUpToDate() {
        $version = $this->get("database_version");
        return $version == self::CURRENT_DATABASE_VERSION;
    }

}
