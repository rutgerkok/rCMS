<?php

class User {

    protected $website_object;
    protected $username;
    protected $display_name;
    protected $password_hashed;
    protected $id;
    protected $email;
    // -1 for unknown
    protected $rank;

    /**
     * Creates a new User object
     * @param Website $oWebsite The Website object
     * @param int $id The id of the user. Use 0 for new users.
     * @param string $username Will be fetched from the database if omitted.
     * @param string $display_name Will be fetched from the database if omitted.
     * @param string $password_hashed Will be fetched from the database if omitted.
     * @param string $email Will be fetched from the database if omitted.
     * @param boolean $admin Will be fetched from the database if omitted.
     * @throws InvalidArgumentException If the id is 0, but one of the other arguments is omitted.
     */
    public function __construct(Website $oWebsite, $id, $username = "", $display_name = "", $password_hashed = "", $email = "NOT_FETCHED", $status = -1) {
        if ($id == 0) {
            // New user, check for arguments
            if (empty($username) || empty($display_name) || $email == "NOT_FETCHED" || ($status < 0)) {
                throw new InvalidArgumentException("For new accounts, you need to supply all arguments!");
            }
        }
        $this->website_object = $oWebsite;
        $this->id = $id;
        $this->set_username($username);
        $this->set_display_name($display_name);
        $this->set_password_hashed($password_hashed);
        $this->set_email($email);
        $this->set_rank($status);
    }

    /**
     * Get the user by name. Returns null if the user isn't found.
     * @param Website $oWebsite The Website object.
     * @param string $username The username. Case insensitive.
     * @return User The User, or null if it isn't found.
     */
    public static function get_by_name(Website $oWebsite, $username) {
        $oDB = $oWebsite->get_database();
        // Escape the username
        $username = strtolower($username);
        $escaped_username = $oDB->escape_data($username);
        // Query
        $sql = 'SELECT `gebruiker_id`, `gebruiker_admin`, `gebruiker_naam`, `gebruiker_wachtwoord`, `gebruiker_email` FROM `gebruikers` WHERE `gebruiker_login` = \'' . $escaped_username . '\' ';
        $result = $oDB->query($sql);

        // Parse the result
        if ($oDB->rows($result) === 1) {
            list($id, $rank, $display_name, $password_hashed, $email) = $oDB->fetch($result);
            return new User($oWebsite, (int) $id, $username, $display_name, $password_hashed, $email, $rank);
        } else {
            return null;
        }
    }
    
    /**
     * Safe way of getting the user object when you know the id. Returns null if
     * the user doesn't exist.
     * @param Website $oWebsite The Website object.
     * @param int $id The user id.
     * @return User The User, or null if it isn't found.
     */
    public static function get_by_id(Website $oWebsite, $user_id) {
        $oDB = $oWebsite->get_database();
        // Escape the user id
        $user_id = (int) $user_id;
        // Query
        $sql = 'SELECT `gebruiker_login`, `gebruiker_admin`, `gebruiker_naam`, `gebruiker_wachtwoord`, `gebruiker_email` FROM `gebruikers` WHERE `gebruiker_id` = \'' . $user_id . '\' ';
        $result = $oDB->query($sql);

        // Parse the result
        if ($oDB->rows($result) === 1) {
            list($username, $rank, $display_name, $password_hashed, $email) = $oDB->fetch($result);
            return new User($oWebsite, $user_id, $username, $display_name, $password_hashed, $email, $rank);
        } else {
            return null;
        }
    }

    /**
     * Gets the username, if needed from the database.
     * @return string
     */
    public function get_username() {
        if (!empty($this->username)) {
            // No need to do a database call
            return $this->username;
        }
        
        // Get from database
        return $this->database_call('gebruiker_login');
    }

    public function get_display_name() {
        if (!empty($this->display_name)) {
            // No need to do a database call
            return $this->display_name;
        }
        
        // Get from database
        return $this->database_call('gebruiker_naam');
    }

    /**
     * Get the hashed password for the user
     * @return password|string
     */
    public function get_password_hashed() {
        if (!empty($this->password_hashed)) {
            // No need to do a database call
            return $this->password_hashed;
        }

        // Fetch from database
        return $this->database_call('gebruiker_wachtwoord');
    }

    public function is_admin() {
        if($this->rank < 0) {
            // Do a database call
            $this->rank = (int) $this->database_call('gebruiker_admin');
        }
        
        return ($this->rank == 1);
    }
    
    public function get_rank() {
        if($this->rank >= 0) {
            return $this->rank;
        }
            
        // Fetch from database
        return (int) $this->database_call('gebruiker_admin');
    }

    public function get_email() {
        if(!empty($this->email)) {
            return $this->email;
        }

        // Do a database call
        return $this->database_call('gebruiker_email');
    }

    public function get_id() {
        return $this->id;
    }

    /**
     * Set the username of the user.
     * @param string $username
     */
    public function set_username($username) {
        $this->username = strtolower(trim($username));
    }

    /**
     * Set the display name of the user
     * @param string $display_name
     */
    public function set_display_name($display_name) {
        $this->display_name = htmlspecialchars(trim($display_name));
    }

    /**
     * Set the password of the user. Password must be unhashed.
     * @param string $password
     */
    public function set_password($password) {
        $this->password_hashed = md5(sha1($password));
    }

    /**
     * Set the hashed password of the user directly
     * @param string $password_hashed
     */
    public function set_password_hashed($password_hashed) {
        $this->password_hashed = $password_hashed;
    }

    /**
     * Sets the email of the user
     * @param string $email
     */
    public function set_email($email) {
        $this->email = $email;
    }

    /**
     * Change the status of the user
     * @param int $admin
     */
    public function set_rank($rank) {
        $this->rank = (int) $rank;
    }

    // Fieldname needs to be sanitized :)
    private function database_call($fieldname) {
        // Get database object
        $oDB = $this->website_object->get_database();

        // Make and execute query
        $sql = 'SELECT `' . $fieldname . '` FROM `gebruikers` WHERE `gebruiker_id` = \'' . $this->id . '\' ';
        $result = $oDB->query($sql);

        // Parse the result
        if ($oDB->rows($result) == 1) {
            $first_row = $oDB->fetch($result);
            return $first_row[0];
        } else {
            $this->website_object->add_error('User not found in User->database_call(' . $fieldname . ') function.', 'Failed to look up the username');
            return '';
        }
    }
    
    /**
     * Saves everything to the database
     */
    public function save() {
        $oDB = $this->website_object->get_database();

        if ($this->id === 0) {
            // New user
            $sql = "INSERT INTO `gebruikers` ( ";
            $sql.= "`gebruiker_admin`, ";
            $sql.= "`gebruiker_login`, ";
            $sql.= "`gebruiker_naam`, ";
            $sql.= "`gebruiker_wachtwoord`, ";
            $sql.= "`gebruiker_email` ";
            $sql.= ")";
            $sql.= "VALUES (";
            $sql.= $this->rank . ",";
            $sql.= "'" . $oDB->escape_data($this->username) . "',";
            $sql.= "'" . $oDB->escape_data($this->display_name) . "',";
            $sql.= "'" . $this->password_hashed . "',";
            $sql.= "'" . $oDB->escape_data($this->email) . "'";
            $sql.= ");";
            // Call query and update ID
            if($oDB->query($sql)) {
                $this->id = $oDB->inserted_id();
                return true;
            } else {
                return false;
            }
        } else {
            // Update existing user
            $changed = false;

            $sql = "UPDATE `gebruikers` ";

            if ($this->rank >= 0) {
                $sql.= 'SET `gebruiker_admin` = "' . $this->rank . "\" ";
                $changed = true;
            }

            if (!empty($this->username)) {
                $sql.= ", `gebruiker_login` = \"" . $oDB->escape_data($this->username) . "\" ";
                $changed = true;
            }

            if (!empty($this->display_name)) {
                $sql.= ", `gebruiker_naam` = \"" . $oDB->escape_data($this->display_name) . "\" ";
                $changed = true;
            }

            if ($this->email != "NOT_FETCHED") {
                $sql.= ", `gebruiker_email` = \"" . $oDB->escape_data($this->email) . "\" ";
                $changed = true;
            }

            if (!empty($this->password_hashed)) {
                $sql.= ", `gebruiker_wachtwoord` = \"" . $this->password_hashed . "\" ";
                $changed = true;
            }

            $sql.= "WHERE `gebruiker_id` = \"$this->id\";";

            if ($changed) {
                // Only execute the query if it has actually changed
                if ($oDB->query($sql)) {
                    return true;
                } else {
                    return false;
                }
            } else {
                // Nothing could be saved, but there was also nothing that went wrong
                return true;
            }
        }
    }

}

?>