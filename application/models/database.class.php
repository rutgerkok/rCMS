<?php

class Database {

    const CURRENT_DATABASE_VERSION = 2;

    protected $dbc = false;
    protected $websiteObject;
    protected $prefix = "";
    // Replacing table names in queries
    private static $TABLE_NAMES_TO_REPLACE;
    private static $REPLACING_TABLE_NAMES;

    public function __construct(Website $oWebsite) {
        // Save website object in this object
        $this->websiteObject = $oWebsite;

        // Connect
        $this->dbc = @mysqli_connect($oWebsite->getSiteSetting('database_location'), $oWebsite->getSiteSetting('database_user'), $oWebsite->getSiteSetting('database_password'), $oWebsite->getSiteSetting('database_name'));

        // Fill prefix replacement arrays
        $prefix = $oWebsite->getSiteSetting('database_table_prefix');
        $this->prefix = $prefix;
        self::$TABLE_NAMES_TO_REPLACE = array('`categorie`', '`users`', '`links`', '`artikel`', '`reacties`', '`menus`', '`widgets`', '`settings`');
        self::$REPLACING_TABLE_NAMES = array("`{$prefix}categorie`", "`{$prefix}users`", "`{$prefix}links`", "`{$prefix}artikel`", "`{$prefix}reacties`", "`{$prefix}menus`", "`{$prefix}widgets`", "`{$prefix}settings`");

        // Abort on error
        if (!$this->dbc) {
            exit("Failed to connect to database: " . mysqli_connect_error());
        }
    }

    /**
     * Returns whether the database is installed and up to date.
     * @return boolean True if the database is installed and up to date, false otherwise.
     */
    public function isUpToDate() {
        return $this->websiteObject->getSiteSetting("database_version") == self::CURRENT_DATABASE_VERSION;
    }

    //Geeft aan hoeveel rows er bij de laatste query aangepast zijn
    public function affectedRows() {
        return(@mysqli_affected_rows($this->dbc));
    }

    /**
     * Creates any missing tables.
     */
    private function createTables() {
        // Categories
        if ($this->query("CREATE TABLE `categorie` (`categorie_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `categorie_naam` VARCHAR(30) NOT NULL) ENGINE = MyISAM", false)) {
            $this->query("INSERT INTO `categorie` (`categorie_naam`) VALUES ('No category'), ('Events'), ('News');");
        }

        if ($this->query("CREATE TABLE IF NOT EXISTS `users` (`user_id` int(10) unsigned NOT NULL AUTO_INCREMENT, " .
                        "`user_login` varchar(30) NOT NULL, `user_password` varchar(255) NOT NULL, " .
                        "`user_display_name` varchar(30) NOT NULL, `user_email` varchar(100) NOT NULL, " .
                        "`user_joined` datetime NOT NULL, `user_last_login` datetime NOT NULL, " .
                        "`user_rank` tinyint(3) unsigned NOT NULL, `user_status` tinyint(4) NOT NULL, " .
                        "`user_status_text` varchar(255) NOT NULL, `user_extra_data` TEXT NULL, " .
                        "PRIMARY KEY (`user_id`), UNIQUE KEY `user_login` (`user_login`)) ENGINE=InnoDB")) {
            $admin = new User($this->websiteObject, 0, "admin", "Admin", User::hashPassword("admin"), "", Authentication::$ADMIN_RANK, 0, 0, Authentication::NORMAL_STATUS, "");
            $admin->save();
        }
        // Links
        $this->query("CREATE TABLE IF NOT EXISTS `links` (`link_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `menu_id` INT UNSIGNED NOT NULL, `link_url` VARCHAR(200) NOT NULL, `link_text` VARCHAR(50) NOT NULL) ENGINE = MyISAM");

        // Menus
        if ($this->query("CREATE TABLE `menus` (`menu_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `menu_name` VARCHAR(50) NOT NULL) ENGINE = MyISAM", false)) {
            $this->query("INSERT INTO `menus` (`menu_name`) VALUES ('Standard menu')");
        }

        // Articles
        $result_articles = $this->query("CREATE TABLE  `artikel` (`artikel_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `categorie_id` INT UNSIGNED NOT NULL, `gebruiker_id` INT UNSIGNED NOT NULL, `artikel_titel` VARCHAR(100) NOT NULL, `artikel_afbeelding` VARCHAR(150) NULL, `artikel_gepind` TINYINT(1) NOT NULL, `artikel_verborgen` TINYINT(1) NOT NULL, `artikel_reacties` TINYINT(1) NOT NULL, `artikel_intro` TEXT NOT NULL, `artikel_inhoud` TEXT NULL, `artikel_gemaakt` DATETIME NOT NULL, `artikel_verwijsdatum` DATETIME NULL,`artikel_bewerkt` DATETIME NULL) ENGINE = MyISAM", false);
        if ($result_articles) {
            $this->query("ALTER TABLE `artikel` ADD FULLTEXT `zoeken` (`artikel_titel`,`artikel_intro`,`artikel_inhoud`) ");
        }

        // Comments
        $result_comments = $this->query("CREATE TABLE `reacties` (`reactie_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `artikel_id` INT UNSIGNED NOT NULL, `gebruiker_id` INT UNSIGNED NULL, `reactie_email` varchar(100) NOT NULL, `reactie_gemaakt` DATETIME NOT NULL, `reactie_naam` VARCHAR(20) NOT NULL, `reactie_inhoud` TEXT NOT NULL ) ENGINE=MyISAM", false);
        if ($result_comments) {
            $this->query("ALTER TABLE `reacties` ADD INDEX (`artikel_id`)", false);
        }

        // Widgets
        if ($this->query("CREATE TABLE `widgets` (`widget_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `widget_naam` VARCHAR(40) NOT NULL , `widget_data` TEXT NULL, `widget_priority` INT NOT NULL, `sidebar_id` INT UNSIGNED NOT NULL) ENGINE=MyISAM", false)) {
            $this->query("INSERT INTO `widgets` (`widget_naam`, `widget_data`, `widget_priority`, `sidebar_id`) VALUES ('articles', '{\"title\":\"News\",\"categories\":[3],\"count\":4,\"display_type\":0,\"order\":0}', '0', '1'), ('calendar', '{\"title\":\"Events\"}', '0', '2')");
        }

        // Settings
        if ($this->query("CREATE TABLE `settings` (`setting_id` int(10) unsigned NOT NULL AUTO_INCREMENT, `setting_name` varchar(30) NOT NULL, `setting_value` varchar(" . Website::MAX_SITE_OPTION_LENGTH . ") NOT NULL, PRIMARY KEY (`setting_id`) ) ENGINE=MyIsam", false)) {
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
            $this->query($sql);
        }
    }

    /**
     * Updates/installs the database. Returns 0 if nothing changed, returns 1
     * if the database structure was updated, returns 2 if the database was
     * installed.
     * @return int
     */
    public function updateTables() {
        $version = $this->websiteObject->getSiteSetting("database_version");
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
            $update_sql = <<<SQL
                ALTER TABLE `{$this->prefix}gebruikers` CHANGE `gebruiker_id` `user_id` INT( 10 ) UNSIGNED NOT NULL AUTO_INCREMENT ,
                CHANGE `gebruiker_admin` `user_rank` TINYINT( 4 ) NOT NULL ,
                CHANGE `gebruiker_login` `user_login` VARCHAR( 30 ) NOT NULL ,
                CHANGE `gebruiker_naam` `user_display_name` VARCHAR( 30 ) NULL DEFAULT NULL ,
                CHANGE `gebruiker_wachtwoord` `user_password` VARCHAR( 255 ) NOT NULL ,
                CHANGE `gebruiker_email` `user_email` VARCHAR( 100 ) NOT NULL,
                ADD `user_joined` DATETIME NOT NULL,
                ADD `user_last_login` DATETIME NOT NULL,
                ADD `user_status` TINYINT NOT NULL,
                ADD `user_status_text` VARCHAR( 255 ) NOT NULL,
                ADD `user_extra_data` TEXT NOT NULL
SQL;
            if ($this->query($update_sql)) {
                $rename_sql = "RENAME TABLE `{$this->prefix}gebruikers` TO `users`";
                $this->query($rename_sql);
            }
        }
        $this->websiteObject->setSiteSetting("database_version", self::CURRENT_DATABASE_VERSION);
        return 1;
    }

    /**
     * Sanitizes a String.
     * @param type $string
     * @return string Sanitized string
     */
    public function escapeData($string) {
        //Witruimte verwijderen en escapetekens toevoegen
        return @mysqli_real_escape_string($this->dbc, trim($string));
    }

    /**
     * Fetches a row. Keys will be numeric. Returns null if there are no more
     * rows left.
     * @param type $result
     * @return string[]
     */
    public function fetchNumeric($result) {
        return(@mysqli_fetch_array($result, MYSQLI_NUM));
    }

    //Geeft de primaire sleutel terug van de laatst ingevoegde rij
    public function getLastInsertedId() {
        return(@mysqli_insert_id($this->dbc));
    }

    /**
     * Executes a SQL query
     * @param type $sql The SQL to execute
     * @param type $errorreport Should it display (syntax, connection) errors? Defaults to true.
     * @return mysqli_result|boolean
     */
    public function query($sql, $errorreport = true) {
        $sql = str_replace(self::$TABLE_NAMES_TO_REPLACE, self::$REPLACING_TABLE_NAMES, $sql);

        $result = @mysqli_query($this->dbc, $sql);

        if (!$result && $errorreport) {
            //toon foutmelding
            $websiteObject = $this->websiteObject;
            if ($this->isUpToDate()) {
                $websiteObject->addError('Query failed: <br /><strong>Query:</strong><br />' . $sql . '<br /><strong>MySQL error:</strong><br />' . @mysqli_error($this->dbc) . @mysqli_connect_error(), 'A database error occured.');
                //een van beide functies(mysqli_error of mysqli_connect_error) geeft een duidelijke foutmelding
            } else {
                $websiteObject->addError('Database is outdated! Please upgrade using the link in the menu bar.');
            }
        }
        return $result;
    }

    /**
     * Returns the number of rows in a result
     * @param type $result
     * @return int
     */
    public function rows($result) {
        if ($result === false || $result == null) {
            return 0;
        }
        return(@mysqli_num_rows($result));
    }

}

?>