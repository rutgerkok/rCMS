<?php

namespace Rcms\Core;

use PDO;
use PDOException;

/**
 * Holds all settings of the site, both from options.php and the database.
 */
class Config {

    const DEFAULT_LANGUAGE = "en";
    const DEFAULT_THEME = "rkok";
    const DEFAULT_TITLE = "Welcome!";

    private $config = array();

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
            echo "<code>" . $file . "</code> was not found! Please create a config file.";
            die();
        }
    }

    /**
     * Loads the settings from the database. Requires that there is a table
     * called `settings` in the database with the columns `setting_name` and
     * `setting_value`.
     * @param Database $database The database to load the settings from.
     */
    public function readFromDatabase(Database $database) {
        // Load settings from the database
        try {
            $result = $database->resultQuery("SELECT `setting_name`, `setting_value` FROM `settings`", false);
            while (list($key, $value) = $result->fetch(PDO::FETCH_NUM)) {
                $this->config[$key] = $value;
            }
        } catch (PDOException $e) {
            // No, database is not up to date
        }
    }

    /**
     * Gets a setting from either the options.php or the settings table.
     * Requires that there is a table called `settings` in the database 
     * with the columns `setting_name` and `setting_value`.
     * @param string $name Name of the setting.
     * @return mixed false if not found, otherwise the value.
     */
    public function get($name) {
        if (isSet($this->config[$name])) {
            $value = $this->config[$name];
            if (strToLower($value) == "false") {
                // Because "false" == true
                return false;
            }
            return $value;
        } else {
            return false;
        }
    }

    /**
     * Changes a setting. Saves a setting to the database. Does nothing if the
     * setting is unchanged.
     * @param Database $database The database to save the setting to.
     * @param string $name The name of the setting.
     * @param string $value The value of the setting.
     */
    public function set(Database $database, $name, $value) {
        if (isSet($this->config[$name]) && $this->config[$name] == $value) {
            // No need to update
            return;
        }

        // Apply on current page
        $this->config[$name] = $value;

        // Save to database
        if (isSet($this->config[$name])) {
            // Update setting
            $sql = "UPDATE `settings` SET ";
            $sql.= "`setting_value` = :value ";
            $sql.= "WHERE `setting_name` = :name";
            $database->prepareQuery($sql)->execute(array(":name" => $name, ":value" => $value));
        } else {
            // New setting
            $sql = "INSERT INTO `settings` (`setting_name`, `setting_value`) ";
            $sql.= " VALUES (:name, :value)";
            $database->prepareQuery($sql)->execute(array(":name" => $name, ":value" => $value));
        }
    }

}
