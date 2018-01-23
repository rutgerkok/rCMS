<?php

namespace Rcms\Core;

use Rcms\Core\NotFoundException;
use PDO;

/**
 * Contains the code to install the database.
 */
class DatabaseInstaller {

    /**
     * Database state constant. Used when no connection could be made to the
     * database.
     */
    const STATE_NOT_CONNECTED = 0;

    /**
     * Database state constant. Used when database scheme doesn't have a version
     * at all. In other words, the database tables are not installed.
     */
    const STATE_NOT_INSTALLED = 1;

    /**
     * Database state constant. Used when database scheme has a version lower
     * than what the website expects. In other words, the database scheme is
     * outdated.
     */
    const STATE_OUTDATED = 2;

    /**
     * Database state constant. Used when the version of the database scheme
     * matches the expected version.
     */
    const STATE_NORMAL = 3;
    /**
     * Database state constant. Used when database scheme has a version higher
     * than what the website expects - the database scheme must be from the
     * future!
     */
    const STATE_FROM_FUTURE = 4;

    public function __construct() {

    }

    /**
     * Gets the state of the database: can we connect, is the scheme up to date?
     * @param Website $website The website.
     * @return int One of the `STATE_` constants of this class.
     */
    public function getDatabaseState(Website $website) {
        if ($website->getConfig()->isDatabaseUpToDate()) {
            return self::STATE_NORMAL;
        }

        try {
            $website->getDatabase();
        } catch (NotFoundException $ex) {
            return self::STATE_NOT_CONNECTED;
        }

        $databaseVersion = $website->getConfig()->get(Config::OPTION_DATABASE_VERSION);
        if ($databaseVersion == 0) {
            return self::STATE_NOT_INSTALLED;
        } else if ($databaseVersion < Config::CURRENT_DATABASE_VERSION) {
            return self::STATE_OUTDATED;
        } else { // so ($databaseVersion > Config::CURRENT_DATABASE_VERSION)
            return self::STATE_FROM_FUTURE;
        }
    }

    private function createTables(PDO $database) {
        // Categories
        $database->exec("CREATE TABLE `categories` (`category_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, " .
                 "`category_name` VARCHAR(30) NOT NULL, " .
                 "`category_description` TEXT NULL " .
                 ") ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $database->exec("INSERT INTO `categories` (`category_name`) VALUES ('No category'), ('Events'), ('News');");

        // Users
        $database->exec("CREATE TABLE IF NOT EXISTS `users` (`user_id` int(10) unsigned NOT NULL AUTO_INCREMENT, " .
                "`user_login` varchar(30) NOT NULL, `user_password` varchar(255) NOT NULL, " .
                "`user_display_name` varchar(30) NOT NULL, `user_email` varchar(100) NULL, " .
                "`user_joined` datetime NOT NULL, `user_last_login` datetime NOT NULL, " .
                "`user_rank` tinyint(3) unsigned NOT NULL, `user_status` tinyint(4) NOT NULL, " .
                "`user_status_text` varchar(255) NOT NULL, `user_extra_data` TEXT NULL, " .
                "PRIMARY KEY (`user_id`), UNIQUE KEY `user_login` (`user_login`)) ENGINE=InnoDB");

        $admin = User::createNewUser("admin", "Admin", "admin");
        $admin->setRank(Ranks::ADMIN);
        $userRepo = new UserRepository($database);
        $userRepo->save($admin);

        // Links
        $database->exec("CREATE TABLE IF NOT EXISTS `links` (`link_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `menu_id` INT UNSIGNED NOT NULL, `link_url` VARCHAR(200) NOT NULL, `link_text` VARCHAR(50) NOT NULL) ENGINE = MyISAM");

        // Menus
        $database->exec("CREATE TABLE `menus` (`menu_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `menu_name` VARCHAR(50) NOT NULL) ENGINE = MyISAM");
        $database->exec("INSERT INTO `menus` (`menu_name`) VALUES ('Standard menu')");

        // Articles
        $database->exec("CREATE TABLE  `artikel` (`artikel_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `categorie_id` INT UNSIGNED NOT NULL, `gebruiker_id` INT UNSIGNED NOT NULL, `artikel_titel` VARCHAR(100) NOT NULL, `artikel_afbeelding` VARCHAR(150) NULL, `artikel_gepind` TINYINT(1) NOT NULL, `artikel_verborgen` TINYINT(1) NOT NULL, `artikel_reacties` TINYINT(1) NOT NULL, `artikel_intro` TEXT NOT NULL, `artikel_inhoud` TEXT NULL, `artikel_gemaakt` DATETIME NOT NULL, `artikel_verwijsdatum` DATETIME NULL,`artikel_bewerkt` DATETIME NULL) ENGINE = MyISAM");
        $database->exec("ALTER TABLE `artikel` ADD FULLTEXT `zoeken` (`artikel_titel`,`artikel_intro`,`artikel_inhoud`) ");

        // Comments
        $database->exec("CREATE TABLE IF NOT EXISTS `comments` (`comment_id` int(10) unsigned NOT NULL AUTO_INCREMENT, `article_id` int(10) unsigned NOT NULL, `user_id` int(10) unsigned NULL, `comment_parent_id` int(10) unsigned NULL, `comment_name` varchar(20) NULL, `comment_email` varchar(100) NULL, `comment_created` datetime NOT NULL, `comment_last_edited` datetime NULL, `comment_body` text NOT NULL, `comment_status` tinyint(3) unsigned NOT NULL, PRIMARY KEY (`comment_id`)) ENGINE=MyISAM");
        $database->exec("ALTER TABLE `comments` ADD FULLTEXT `comment_body` (`comment_body`), ADD INDEX `article_id` (`article_id`)");

        // Widgets
        $database->exec("CREATE TABLE `widgets` (`widget_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `widget_naam` VARCHAR(40) NOT NULL , `widget_data` TEXT NULL, `widget_priority` INT NOT NULL, `sidebar_id` INT UNSIGNED NOT NULL) ENGINE=MyISAM");
        $database->exec("INSERT INTO `widgets` (`widget_naam`, `widget_data`, `widget_priority`, `sidebar_id`) VALUES ('articles', '{\"title\":\"News\",\"categories\":[3],\"count\":4,\"display_type\":0,\"order\":0}', '0', '1'), ('calendar', '{\"title\":\"Events\"}', '0', '2')");

        // Documents
        $this->createDocumentsTable($database);

        // Settings
        $database->exec("CREATE TABLE `settings` (`setting_id` int(10) unsigned NOT NULL AUTO_INCREMENT, `setting_name` varchar(30) NOT NULL, `setting_value` varchar(" . Website::MAX_SITE_OPTION_LENGTH . ") NOT NULL, PRIMARY KEY (`setting_id`) ) ENGINE=MyIsam");
        $year = date("Y");
        $database_version = Config::CURRENT_DATABASE_VERSION;
        $sql = <<<EOT
                INSERT INTO `settings` (`setting_name`, `setting_value`) VALUES
                ('theme', 'temp'),
                ('title', 'My website'),
                ('copyright', 'Copyright $year - built with rCMS'),
                ('password', ''),
                ('language', 'en'),
                ('user_account_creation', '1'),
                ('append_page_title', '0'),
                ('database_version', '$database_version')
EOT;
        $database->exec($sql);
    }

    private function createDocumentsTable(PDO $database) {
        $documentsTable = <<<SQL
        CREATE TABLE IF NOT EXISTS `documents` (
            `document_id` int(10) unsigned NOT NULL AUTO_INCREMENT,
            `document_title` varchar(255) NOT NULL,
            `document_intro` text NOT NULL,
            `document_hidden` tinyint(1) NOT NULL,
            `document_created` datetime NOT NULL,
            `document_edited` datetime DEFAULT NULL,
            `user_id` int(10) unsigned NOT NULL,
            `document_parent_id` int(10) unsigned NOT NULL,
            PRIMARY KEY (`document_id`),
            KEY `document_hidden` (`document_hidden`, `document_created`, `user_id`, `document_parent_id`),
            FULLTEXT KEY `document_title` (`document_title`, `document_intro`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 AUTO_INCREMENT=10 ;
SQL;
        $database->exec($documentsTable);
    }

    private function updateUsersTable(PDO $database) {
        // Update users table (prefix needs to be included, since that isn't
        // automatically added for old table names )
        $updateSql = <<<SQL
                ALTER TABLE `gebruikers`
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
                ADD `user_extra_data` TEXT NULL
SQL;
        $database->exec($updateSql);

        $renameSql = "RENAME TABLE `gebruikers` TO `users`";
        $database->exec($renameSql);
    }

    private function updateCommentsTable(PDO $database) {
        $updateSql = <<<SQL
                ALTER TABLE `reacties`
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
        $database->exec($updateSql);

        $renameSql = "RENAME TABLE `reacties` TO `comments`";
        $database->exec($renameSql);
    }

    private function updateCategoriesTable(PDO $database) {
        $updateSql = <<<SQL
                ALTER TABLE `categorie`
                CHANGE `categorie_id` `category_id` INT(10) UNSIGNED NOT NULL AUTO_INCREMENT,
                CHANGE `categorie_naam` `category_name` VARCHAR(30) NOT NULL,
                ADD `category_description` TEXT NULL
SQL;
        $database->exec($updateSql);

        $renameSql = "RENAME TABLE `categorie` TO `categories`";
        $database->exec($renameSql);
    }
    
    private function makeUsersExtraDataFieldOptional(PDO $database) {
        $database->exec(<<<SQL
            ALTER TABLE `users`
            CHANGE `user_extra_data` `user_extra_data` TEXT NULL; 
SQL
       );
    }

    /**
     * Updates/installs the database.
     * @return int 0 if nothing changed, returns 1
     * if the database structure was updated, returns 2 if the database was
     * installed.
     */
    public function createOrUpdateTables(Website $website) {
        $version = $website->getConfig()->get(Config::OPTION_DATABASE_VERSION);

        // Nothing to update
        if ($version == Config::CURRENT_DATABASE_VERSION) {
            return 0;
        }

        // Create tables
        if ($version == 0) {
            $this->createTables($website->getDatabase());
            return 2;
        }

        // Upgrade existing
        if ($version === 1) {
            $this->updateUsersTable($website->getDatabase());
        }
        if ($version <= 2) {
            $this->updateCommentsTable($website->getDatabase());
        }
        if ($version <= 3) {
            $this->createDocumentsTable($website->getDatabase());
        }
        if ($version <= 4) {
            $this->updateCategoriesTable($website->getDatabase());
        }
        if ($version !== 1 && $version <= 5) {
            $this->makeUsersExtraDataFieldOptional($website->getDatabase());
        }

        // Update version number to signify database update
        $website->getConfig()->set($website->getDatabase(), Config::OPTION_DATABASE_VERSION, Config::CURRENT_DATABASE_VERSION);
        return 1;
    }

}
