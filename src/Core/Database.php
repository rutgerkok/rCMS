<?php

namespace Rcms\Core;

use DateTime;
use PDO;
use PDOException;
use PDOStatement;

class Database extends PDO {

    const CURRENT_DATABASE_VERSION = 3;

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
            parent::__construct($dataSource, $config->get("database_user"), $config->get("database_password"));
        } catch (PDOException $e) {
            // Abort on error
            exit("Failed to connect to database: " . $e->getMessage());
        }

        // Let it throw exceptions
        $this->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

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
        $this->exec("CREATE TABLE `categorie` (`categorie_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `categorie_naam` VARCHAR(30) NOT NULL) ENGINE = MyISAM");
        $this->exec("INSERT INTO `categorie` (`categorie_naam`) VALUES ('No category'), ('Events'), ('News');");

        // Users
        $this->exec("CREATE TABLE IF NOT EXISTS `users` (`user_id` int(10) unsigned NOT NULL AUTO_INCREMENT, " .
                "`user_login` varchar(30) NOT NULL, `user_password` varchar(255) NOT NULL, " .
                "`user_display_name` varchar(30) NOT NULL, `user_email` varchar(100) NULL, " .
                "`user_joined` datetime NOT NULL, `user_last_login` datetime NOT NULL, " .
                "`user_rank` tinyint(3) unsigned NOT NULL, `user_status` tinyint(4) NOT NULL, " .
                "`user_status_text` varchar(255) NOT NULL, `user_extra_data` TEXT NULL, " .
                "PRIMARY KEY (`user_id`), UNIQUE KEY `user_login` (`user_login`)) ENGINE=InnoDB");

        $admin = User::createNewUser("admin", "Admin", "admin");
        $admin->setRank(Authentication::$ADMIN_RANK);
        $this->websiteObject->getAuth()->getUserRepository()->save($admin);

        // Links
        $this->exec("CREATE TABLE IF NOT EXISTS `links` (`link_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `menu_id` INT UNSIGNED NOT NULL, `link_url` VARCHAR(200) NOT NULL, `link_text` VARCHAR(50) NOT NULL) ENGINE = MyISAM");

        // Menus
        $this->exec("CREATE TABLE `menus` (`menu_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `menu_name` VARCHAR(50) NOT NULL) ENGINE = MyISAM");
        $this->exec("INSERT INTO `menus` (`menu_name`) VALUES ('Standard menu')");

        // Articles
        $this->exec("CREATE TABLE  `artikel` (`artikel_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `categorie_id` INT UNSIGNED NOT NULL, `gebruiker_id` INT UNSIGNED NOT NULL, `artikel_titel` VARCHAR(100) NOT NULL, `artikel_afbeelding` VARCHAR(150) NULL, `artikel_gepind` TINYINT(1) NOT NULL, `artikel_verborgen` TINYINT(1) NOT NULL, `artikel_reacties` TINYINT(1) NOT NULL, `artikel_intro` TEXT NOT NULL, `artikel_inhoud` TEXT NULL, `artikel_gemaakt` DATETIME NOT NULL, `artikel_verwijsdatum` DATETIME NULL,`artikel_bewerkt` DATETIME NULL) ENGINE = MyISAM");
        $this->exec("ALTER TABLE `artikel` ADD FULLTEXT `zoeken` (`artikel_titel`,`artikel_intro`,`artikel_inhoud`) ");

        // Comments
        $this->exec("CREATE TABLE IF NOT EXISTS `comments` (`comment_id` int(10) unsigned NOT NULL AUTO_INCREMENT, `article_id` int(10) unsigned NOT NULL, `user_id` int(10) unsigned NULL, `comment_parent_id` int(10) unsigned NULL, `comment_name` varchar(20) NULL, `comment_email` varchar(100) NULL, `comment_created` datetime NOT NULL, `comment_last_edited` datetime NULL, `comment_body` text NOT NULL, `comment_status` tinyint(3) unsigned NOT NULL, PRIMARY KEY (`comment_id`)) ENGINE=MyISAM");
        $this->exec("ALTER TABLE `comments` ADD FULLTEXT `comment_body` (`comment_body`), ADD INDEX `article_id` (`article_id`)");

        // Widgets
        $this->exec("CREATE TABLE `widgets` (`widget_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `widget_naam` VARCHAR(40) NOT NULL , `widget_data` TEXT NULL, `widget_priority` INT NOT NULL, `sidebar_id` INT UNSIGNED NOT NULL) ENGINE=MyISAM");
        $this->exec("INSERT INTO `widgets` (`widget_naam`, `widget_data`, `widget_priority`, `sidebar_id`) VALUES ('articles', '{\"title\":\"News\",\"categories\":[3],\"count\":4,\"display_type\":0,\"order\":0}', '0', '1'), ('calendar', '{\"title\":\"Events\"}', '0', '2')");

        // Settings
        $this->exec("CREATE TABLE `settings` (`setting_id` int(10) unsigned NOT NULL AUTO_INCREMENT, `setting_name` varchar(30) NOT NULL, `setting_value` varchar(" . Website::MAX_SITE_OPTION_LENGTH . ") NOT NULL, PRIMARY KEY (`setting_id`) ) ENGINE=MyIsam");
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
        $this->exec($sql);
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
            $this->exec($updateSql);

            $renameSql = "RENAME TABLE `{$this->prefix}gebruikers` TO `users`";
            $this->exec($renameSql);

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
            $this->exec($updateSql);

            $renameSql = "RENAME TABLE `{$this->prefix}reacties` TO `comments`";
            $this->exec($renameSql);
        }

        // Done updating
        $this->websiteObject->getConfig()->set($this, "database_version", self::CURRENT_DATABASE_VERSION);
        return 1;
    }

    /**
     * Executes a SQL query. Use this for UPDATE, DELETE and INSERT queries.
     * Automatically adds prefixes to the table names, as long as they are
     * placed in backticks.
     * @param string $sql The SQL to execute
     * @throws PDOException If the query is invalid, or the connection is lost.
     * @return int The number of affected rows.
     */
    public function exec($sql) {
        $sql = str_replace($this->tableNamesToReplace, $this->replacingTableNames, $sql);
        return parent::exec($sql);
    }

    /**
     * Executes a SQL query and returns the result. Use this for SELECT
     * queries. Automatically adds prefixes to the table names, as long as they
     * are placed in backticks.
     * @param string $sql The SQL to execute.
     * @throws PDOException If the query is invalid, or the connection is lost.
     * @return PDOStatement The results.
     */
    public function query($sql) {
        $sql = str_replace($this->tableNamesToReplace, $this->replacingTableNames, $sql);
        return parent::query($sql);
    }

    /**
     * Prepares a statement for execution and returns a statement object
     * @link http://php.net/manual/en/pdo.prepare.php
     * @param string $sql This must be a valid SQL statement for the target
     * database server.
     * @param array $driverOptions [optional] This array holds one or more 
     * key=&gt;value pairs to set attribute values for the PDOStatement object
     * that this method returns. You would most commonly use this to set the
     * PDO::ATTR_CURSOR value to PDO::CURSOR_SCROLL to request a scrollable
     * cursor. Some drivers have driver specific options that may be set at
     * prepare-time.
     * @return PDOStatement If the database server successfully prepares the statement,
     * <b>PDO::prepare</b> returns a
     * <b>PDOStatement</b> object.
     * If the database server cannot successfully prepare the statement,
     * <b>PDO::prepare</b> returns <b>FALSE</b> or emits
     * <b>PDOException</b> (depending on error handling).
     * </p>
     * <p>
     * Emulated prepared statements does not communicate with the database server
     * so <b>PDO::prepare</b> does not check the statement.
     */
    public function prepare($sql, $driverOptions = array()) {
        $sql = str_replace($this->tableNamesToReplace, $this->replacingTableNames, $sql);
        return parent::prepare($sql, $driverOptions);
    }

}
