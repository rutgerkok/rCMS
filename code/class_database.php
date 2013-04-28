<?php

class Database {

    protected $database_table_prefix; // slaan we hier op zodat die niet iedere keer geladen moet worden
    // Variabele met verbinding
    public $dbc = false;
    // Variabele met website-object
    protected $website_object;

    public function __construct(Website $oWebsite) {
        //Variabele website_object goed zetten
        $this->website_object = $oWebsite;

        //Verbinding maken of laden
        $this->dbc = @mysqli_connect("p:" . $oWebsite->get_sitevar('database_location'), $oWebsite->get_sitevar('database_user'), $oWebsite->get_sitevar('database_password'), $oWebsite->get_sitevar('database_name'));

        //Tabelprefix opslaan (scheelt weer een hoop functieaanroepen)
        $this->database_table_prefix = $oWebsite->get_sitevar('database_table_prefix');

        //Eventueel foutmelding
        if (!$this->dbc) {
            exit("Failed to connect to database.");
        }
    }

    //Geeft aan hoeveel rows er bij de laatste query aangepast zijn
    public function affected_rows() {
        return(@mysqli_affected_rows($this->dbc));
    }

    //Maakt de standaard tabellen
    public function create_tables() {
        //oude tabellen verwijderen
        $this->query("DROP TABLE `categorie`", false);
        $this->query("DROP TABLE `gebruikers`", false);
        $this->query("DROP TABLE `menuitem`", false);
        $this->query("DROP TABLE `artikel`", false);
        $this->query("DROP TABLE `reacties`", false);
        $this->query("DROP TABLE `widgets`", false);

        //categorietabel
        if ($this->query("CREATE TABLE `categorie` (`categorie_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `categorie_naam` VARCHAR(30) NOT NULL) ENGINE = MyISAM", false)) {
            $this->query("INSERT INTO `categorie` (`categorie_naam`) VALUES ('No category'), ('Events'), ('News');");
        }

        //gebruikerstabel
        if ($this->query("CREATE TABLE `gebruikers` ( `gebruiker_id` int(10) unsigned NOT NULL AUTO_INCREMENT, `gebruiker_admin` tinyint(4) NOT NULL, `gebruiker_login` varchar(30) NOT NULL, `gebruiker_naam` varchar(20) NULL, `gebruiker_wachtwoord` char(32) NOT NULL, `gebruiker_email` varchar(100) NOT NULL, PRIMARY KEY (`gebruiker_id`) ) ENGINE=MyIsam", false)) {
            $this->query("INSERT INTO `gebruikers` ( `gebruiker_admin`, `gebruiker_login`, `gebruiker_naam`, `gebruiker_wachtwoord`, `gebruiker_email`) VALUES ( '1', 'admin', 'De beheerder', '" . md5(sha1('admin')) . "', '')");
        }

        //menutabel
        $this->query("CREATE TABLE `menuitem` (`menuitem_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `menuitem_url` VARCHAR(200) NOT NULL, `menuitem_naam` VARCHAR(50) NOT NULL, `menuitem_type` INT UNSIGNED NOT NULL) ENGINE = MyISAM", false);

        //artikeltabel
        $this->query("CREATE TABLE  `artikel` (`artikel_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `categorie_id` INT UNSIGNED NOT NULL, `gebruiker_id` INT UNSIGNED NOT NULL, `artikel_titel` VARCHAR(100) NOT NULL, `artikel_afbeelding` VARCHAR(150) NULL, `artikel_gepind` TINYINT(1) NOT NULL, `artikel_verborgen` TINYINT(1) NOT NULL, `artikel_reacties` TINYINT(1) NOT NULL, `artikel_intro` TEXT NOT NULL, `artikel_inhoud` TEXT NULL, `artikel_gemaakt` DATETIME NOT NULL, `artikel_verwijsdatum` DATETIME NULL,`artikel_bewerkt` DATETIME NULL) ENGINE = MyISAM", false);
        $this->query("ALTER TABLE `artikel` ADD FULLTEXT `zoeken` (`artikel_titel`,`artikel_intro`,`artikel_inhoud`) ");

        //reactiestabel
        $this->query("CREATE TABLE `reacties` ( `reactie_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `artikel_id` INT UNSIGNED NOT NULL, `gebruiker_id` INT UNSIGNED NULL, `reactie_email` varchar(100) NOT NULL, `reactie_gemaakt` DATETIME NOT NULL, `reactie_naam` VARCHAR(20) NOT NULL, `reactie_inhoud` TEXT NOT NULL ) ENGINE=MyISAM;", false);
        $this->query("ALTER TABLE `reacties` ADD INDEX (`artikel_id`)");

        //widgettabel
        $this->query("CREATE TABLE `widgets` ( `widget_id` INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY, `widget_naam` VARCHAR(40) NOT NULL , `widget_data` TEXT NULL, `widget_order` INT NOT NULL, `sidebar_id` INT UNSIGNED NOT NULL)");
    }

    /**
     * Sanitizes a String.
     * @param type $string
     * @return string Sanitized string
     */
    public function escape_data($string) {
        //Magic quotes oplossen
        if (ini_get('magic_quotes_gpc')) {
            $string = stripslashes($string);
        }

        //Witruimte verwijderen en escapetekens toevoegen
        return @mysqli_real_escape_string($this->dbc, trim($string));
    }

    /**
     * Fetches a row. Keys will be numeric. Returns null if there are no more
     * rows left.
     * @param type $result
     * @return string[]
     */
    public function fetch($result) {
        return(@mysqli_fetch_array($result, MYSQLI_NUM));
    }

    //Geeft de actieve verbinding terug
    public function get_connection() {
        return $this->dbc;
    }

    //Geeft de primaire sleutel terug van de laatst ingevoegde rij
    public function inserted_id() {
        return(@mysqli_insert_id($this->dbc));
    }

    /**
     * Executes a SQL query
     * @param type $sql The SQL to execute
     * @param type $errorreport Should it display (syntax, connection) errors? Defaults to true.
     * @return mysqli_result|boolean
     */
    public function query($sql, $errorreport = true) {
        $prefix = $this->database_table_prefix;

        $sql = str_replace(
                array('`categorie`', '`gebruikers`', '`menuitem`', '`artikel`', '`reacties`'), array("`{$prefix}categorie`", "`{$prefix}gebruikers`", "`{$prefix}menuitem`", "`{$prefix}artikel`", "`{$prefix}reacties`"), $sql);

        $result = @mysqli_query($this->dbc, $sql);

        if (!$result && $errorreport) {
            //toon foutmelding
            $website_object = $this->website_object;
            $website_object->add_error('Query failed: <br /><strong>Query:</strong><br />' . $sql . '<br /><strong>MySQL error:</strong><br />' . @mysqli_error($this->dbc) . @mysqli_connect_error(), 'A database error occured.');
            //een van beide functies(mysqli_error of mysqli_connect_error) geeft een duidelijke foutmelding
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