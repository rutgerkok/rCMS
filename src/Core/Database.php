<?php

namespace Rcms\Core;

use DateTime;
use PDO;
use PDOException;
use PDOStatement;

class Database {

    const CURRENT_DATABASE_VERSION = 3;

    /**
     * @var PDO The database connection.
     */
    protected $dbc;
    protected $websiteObject;
    protected $prefix = "";
    // Replacing table names in queries
    private $tableNamesToReplace;
    private $replacingTableNames;

    public function __construct(Website $oWebsite) {
        // Save website object in this object
        $this->websiteObject = $oWebsite;

        $config = $oWebsite->getConfig();

        // Connect
        try {
            $dataSource = "mysql:dbname={$config->get("database_name")};host={$config->get("database_location")}";
            $this->dbc = new PDO($dataSource, $config->get("database_user"), $config->get("database_password"));
        } catch (PDOException $e) {
            // Abort on error
            exit("Failed to connect to database: " . $e->getMessage());
        }

        // Let it throw exceptions
        $this->dbc->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Fill prefix replacement arrays
        $prefix = $config->get("database_table_prefix");
        $this->prefix = $prefix;
        $this->tableNamesToReplace = array("`categorie`", "`users`", "`links`", "`artikel`", "`comments`", "`menus`", "`widgets`", "`settings`");
        $this->replacingTableNames = array("`{$prefix}categorie`", "`{$prefix}users`", "`{$prefix}links`", "`{$prefix}artikel`", "`{$prefix}comments`", "`{$prefix}menus`", "`{$prefix}widgets`", "`{$prefix}settings`");
    }

    /**
     * Returns whether the database is up to date with the current database
     * schema. If the database is not installed yet, it is considered up to
     * date.
     * @return boolean True if the database is installed and up to date, false otherwise.
     */
    public function isUpToDate() {
        $version = $this->websiteObject->getConfig()->get("database_version");
        return $version == self::CURRENT_DATABASE_VERSION || $version == 0;
    }

    /**
     * Gets whether the database is installed. Outdated databases are still
     * considered as installed.
     * @return boolean True if the database is installed, false otherwise.
     */
    public function isInstalled() {
        $version = $this->websiteObject->getConfig()->get("database_version");
        return $version > 0;
    }

    /**
     * Creates any missing tables.
     */
    private function createTables() {
        // Categories
        $this->execQuery("CREATE TABLE `categorie` (`categorie_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `categorie_naam` VARCHAR(30) NOT NULL) ENGINE = MyISAM");
        $this->execQuery("INSERT INTO `categorie` (`categorie_naam`) VALUES ('No category'), ('Events'), ('News');");

        // Users
        $this->execQuery("CREATE TABLE IF NOT EXISTS `users` (`user_id` int(10) unsigned NOT NULL AUTO_INCREMENT, " .
                "`user_login` varchar(30) NOT NULL, `user_password` varchar(255) NOT NULL, " .
                "`user_display_name` varchar(30) NOT NULL, `user_email` varchar(100) NULL, " .
                "`user_joined` datetime NOT NULL, `user_last_login` datetime NOT NULL, " .
                "`user_rank` tinyint(3) unsigned NOT NULL, `user_status` tinyint(4) NOT NULL, " .
                "`user_status_text` varchar(255) NOT NULL, `user_extra_data` TEXT NULL, " .
                "PRIMARY KEY (`user_id`), UNIQUE KEY `user_login` (`user_login`)) ENGINE=InnoDB");

        $admin = User::createNewUser("admin", "Admin", "admin");
        $this->websiteObject->getAuth()->getUserRepository()->save($admin);

        // Links
        $this->execQuery("CREATE TABLE IF NOT EXISTS `links` (`link_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `menu_id` INT UNSIGNED NOT NULL, `link_url` VARCHAR(200) NOT NULL, `link_text` VARCHAR(50) NOT NULL) ENGINE = MyISAM");

        // Menus
        $this->execQuery("CREATE TABLE `menus` (`menu_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `menu_name` VARCHAR(50) NOT NULL) ENGINE = MyISAM");
        $this->execQuery("INSERT INTO `menus` (`menu_name`) VALUES ('Standard menu')");

        // Articles
        $this->execQuery("CREATE TABLE  `artikel` (`artikel_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `categorie_id` INT UNSIGNED NOT NULL, `gebruiker_id` INT UNSIGNED NOT NULL, `artikel_titel` VARCHAR(100) NOT NULL, `artikel_afbeelding` VARCHAR(150) NULL, `artikel_gepind` TINYINT(1) NOT NULL, `artikel_verborgen` TINYINT(1) NOT NULL, `artikel_reacties` TINYINT(1) NOT NULL, `artikel_intro` TEXT NOT NULL, `artikel_inhoud` TEXT NULL, `artikel_gemaakt` DATETIME NOT NULL, `artikel_verwijsdatum` DATETIME NULL,`artikel_bewerkt` DATETIME NULL) ENGINE = MyISAM");
        $this->execQuery("ALTER TABLE `artikel` ADD FULLTEXT `zoeken` (`artikel_titel`,`artikel_intro`,`artikel_inhoud`) ");

        // Comments
        $this->execQuery("CREATE TABLE IF NOT EXISTS `comments` (`comment_id` int(10) unsigned NOT NULL AUTO_INCREMENT, `article_id` int(10) unsigned NOT NULL, `user_id` int(10) unsigned NULL, `comment_parent_id` int(10) unsigned NULL, `comment_name` varchar(20) NULL, `comment_email` varchar(100) NULL, `comment_created` datetime NOT NULL, `comment_last_edited` datetime NULL, `comment_body` text NOT NULL, `comment_status` tinyint(3) unsigned NOT NULL, PRIMARY KEY (`comment_id`)) ENGINE=MyISAM");
        $this->execQuery("ALTER TABLE `comments` ADD FULLTEXT `comment_body` (`comment_body`), ADD INDEX `article_id` (`article_id`)");

        // Widgets
        $this->execQuery("CREATE TABLE `widgets` (`widget_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `widget_naam` VARCHAR(40) NOT NULL , `widget_data` TEXT NULL, `widget_priority` INT NOT NULL, `sidebar_id` INT UNSIGNED NOT NULL) ENGINE=MyISAM");
        $this->execQuery("INSERT INTO `widgets` (`widget_naam`, `widget_data`, `widget_priority`, `sidebar_id`) VALUES ('articles', '{\"title\":\"News\",\"categories\":[3],\"count\":4,\"display_type\":0,\"order\":0}', '0', '1'), ('calendar', '{\"title\":\"Events\"}', '0', '2')");

        // Settings
        $this->execQuery("CREATE TABLE `settings` (`setting_id` int(10) unsigned NOT NULL AUTO_INCREMENT, `setting_name` varchar(30) NOT NULL, `setting_value` varchar(" . Website::MAX_SITE_OPTION_LENGTH . ") NOT NULL, PRIMARY KEY (`setting_id`) ) ENGINE=MyIsam");
        $year = date("Y");
        $database_version = self::CURRENT_DATABASE_VERSION;
        $sql = <<<EOT
                INSERT INTO `settings` (`setting_name`, `setting_value`) VALUES 
                ('theme', 'rkok'),
                ('title', 'My website'),
                ('copyright', 'Copyright $year - built with rCMS'),
                ('password', ''),
                ('language', 'en'),
                ('user_account_creation', '1'),
                ('append_page_title', '0'),
                ('database_version', '$database_version')
EOT;
        $this->execQuery($sql);
    }

    /**
     * Updates/installs the database.
     * @return int 0 if nothing changed, returns 1
     * if the database structure was updated, returns 2 if the database was
     * installed.
     */
    public function updateTables() {
        $version = $this->websiteObject->getConfig()->get("database_version");
        if ($version == self::CURRENT_DATABASE_VERSION) {
            // Nothing to update
            return 0;
        }
        if ($version == 0) {
            // Not installed yet
            $this->createTables();
            return 2;
        }
        if ($version == 1) {
            // Update from version 1
            // Update users table (prefix needs to be included, since that isn't
            // automatically added by $this->query() )
            $updateSql = <<<SQL
                ALTER TABLE `{$this->prefix}gebruikers`
                CHANGE `gebruiker_id` `user_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE `gebruiker_admin` `user_rank` TINYINT(4) NOT NULL,
                CHANGE `gebruiker_login` `user_login` VARCHAR(30) NOT NULL,
                CHANGE `gebruiker_naam` `user_display_name` VARCHAR(30) NULL,
                CHANGE `gebruiker_wachtwoord` `user_password` VARCHAR(255) NOT NULL,
                CHANGE `gebruiker_email` `user_email` VARCHAR(100) NOT NULL,
                ADD `user_joined` DATETIME NOT NULL,
                ADD `user_last_login` DATETIME NOT NULL,
                ADD `user_status` TINYINT NOT NULL,
                ADD `user_status_text` VARCHAR( 255 ) NOT NULL,
                ADD `user_extra_data` TEXT NOT NULL
SQL;
            $this->execQuery($updateSql);

            $renameSql = "RENAME TABLE `{$this->prefix}gebruikers` TO `users`";
            $this->execQuery($renameSql);

            $version = 2; // Continue to next step
        }
        if ($version == 2) {
            // Update from version 2
            // Update comments table
            $updateSql = <<<SQL
                ALTER TABLE `{$this->prefix}reacties`
                CHANGE `reactie_id` `comment_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE `artikel_id` `article_id` INT(10) UNSIGNED NOT NULL,
                CHANGE `gebruiker_id` `user_id` INT(10) UNSIGNED NULL,
                CHANGE `reactie_email` `comment_email` VARCHAR(100) NULL,
                CHANGE `reactie_gemaakt` `comment_created` DATETIME NOT NULL,
                CHANGE `reactie_inhoud` `comment_body` TEXT NOT NULL,
                CHANGE `reactie_naam` `comment_name` VARCHAR(20) NULL,
                ADD `comment_last_edited` DATETIME NULL,
                ADD `comment_parent_id` INT(10) UNSIGNED NULL,
                ADD `comment_status` TINYINT(3) UNSIGNED NOT NULL
SQL;
            $this->execQuery($updateSql);

            $renameSql = "RENAME TABLE `{$this->prefix}reacties` TO `comments`";
            $this->execQuery($renameSql);
        }

        // Done updating
        $this->websiteObject->getConfig()->set($this, "database_version", self::CURRENT_DATABASE_VERSION);
        return 1;
    }

    /**
     * Sanitizes a string, so that it can be inserted in queries. Quotes will be
     * added automatically.
     * @param string $string The string to sanitize.
     * @return string Sanitized string with quotes around.
     */
    public function quote($string) {
        return $this->dbc->quote($string);
    }

    /**
     * Executes a SQL query. Use this for UPDATE, DELETE and INSERT queries.
     * Automatically adds prefixes to the table names, as long as they are
     * placed in backticks.
     * @param string $sql The SQL to execute
     * @throws PDOException If the query is invalid, or the connection is lost.
     * @return int The number of affected rows.
     */
    public function execQuery($sql) {
        $sql = str_replace($this->tableNamesToReplace, $this->replacingTableNames, $sql);
        return $this->dbc->exec($sql);
    }

    /**
     * Executes a SQL query and returns the result. Use this for SELECT
     * queries. Automatically adds prefixes to the table names, as long as they
     * are placed in backticks.
     * @param string $sql The SQL to execute.
     * @throws PDOException If the query is invalid, or the connection is lost.
     * @return PDOStatement The results.
     */
    public function resultQuery($sql) {
        $sql = str_replace($this->tableNamesToReplace, $this->replacingTableNames, $sql);
        return $this->dbc->query($sql);
    }

    /**
     * Prepares a query. Automatically adds prefixes to the table names, as long as they
     * are placed in backticks.
     * @param string $sql SQL of the query.
     * @throws PDOException If the query is invalid, or the connection is lost.
     * @return PDOStatement The query. Fill the parameters on this object and
     * then submit the query.
     */
    public function prepareQuery($sql) {
        $sql = str_replace($this->tableNamesToReplace, $this->replacingTableNames, $sql);
        return $this->dbc->prepare($sql);
    }

    /**
     * Gets the latest id that was inserted.
     * @return int The latest id.
     */
    public function getLastInsertedId() {
        return $this->dbc->lastInsertId();
    }

    /**
     * Parses the standard date format of this database.
     * @param string $string The string to parse.
     * @return DateTime|null The date/time, or null if the string is not a date.
     */
    public static function toDateTime($string) {
        if (empty($string) || $string === "0000-00-00 00:00:00") {
            return null;
        }
        return DateTime::createFromFormat("Y-m-d H:i:s", $string);
    }

    /**
     * Turns the given date/time back into a string, so that it can be inserted
     * into the database.
     * @param DateTime|null $dateTime The date/time to turn into a string.
     * @return string The date/time as a string.
     */
    public static function dateTimeToString(DateTime $dateTime = null) {
        if ($dateTime === null) {
            return "0000-00-00 00:00:00";
        }
        return $dateTime->format("Y-m-d H:i:s");
    }

}
