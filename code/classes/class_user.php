<?php

class User {

    protected $website_object;
    protected $username;
    protected $display_name;
    protected $password_hashed;
    protected $id;
    protected $email;
    protected $rank;
    protected $joined;
    protected $last_login;
    protected $status;
    protected $status_text;
    protected $extra_data;

    /**
     * Creates a new User object
     * @param Website $oWebsite The Website object
     * @param int $id The id of the user. Use 0 for new users.
     * @param string $username The name the user logs in with.
     * @param string $display_name The name that is displayed.
     * @param string $password_hashed Hashed password.
     * @param string $email The email, or empty if no email.
     * @param int $joined When the user joined the site. Use 0 for the current time.
     * @param int $last_login Date of the lastest visit to the site. Use 0 for the current time.
     * @param int $status Whether the user is banned, deleted, etc.
     * @param string $status_text The status text of the user. Can be set by the user.
     * @param string $extra_data Stringified extra data in JSON format.
     * @param int $rank The rank of the account.
     * @throws InvalidArgumentException If the id is 0, but one of the other arguments is omitted.
     */
    public function __construct(Website $oWebsite, $id, $username, $display_name, $password_hashed, $email, $rank, $joined, $last_login, $status, $status_text, $extra_data = null) {
        $this->website_object = $oWebsite;
        $this->id = (int) $id;
        $this->set_username($username);
        $this->set_display_name($display_name);
        $this->set_password_hashed($password_hashed);
        $this->set_email($email);
        $this->set_rank($rank);
        $this->joined = (int) $joined;
        if ($this->joined == 0) {
            $this->joined = time();
        }
        $this->set_last_login($last_login);
        $this->set_status($status);
        $this->set_status_text($status_text);
        $this->set_extra_data($extra_data);
    }

    /**
     * Get the user by name. Returns null if the user isn't found.
     * @param Website $oWebsite The Website object.
     * @param string $username The username. Case insensitive.
     * @return User The User, or null if it isn't found.
     */
    public static function get_by_name(Website $oWebsite, $username) {
        $oDB = $oWebsite->get_database();

        $username = strtolower($username);
        $escaped_username = $oDB->escape_data(strtolower($username));

        $sql = 'SELECT `user_id`, `user_display_name`, `user_password`, ';
        $sql.= '`user_email`, `user_rank`, `user_joined`, `user_last_login`, ';
        $sql.= '`user_status`, `user_status_text`, `user_extra_data` ';
        $sql.= 'FROM `users` WHERE `user_login` = "' . $escaped_username . '" ';
        $result = $oDB->query($sql);

        // Create user object and return
        if ($oDB->rows($result) === 1) {
            list($id, $display_name, $password_hashed, $email, $rank, $joined, $last_login, $status, $status_text, $extra_data) = $oDB->fetch($result);
            return new User($oWebsite, $id, $username, $display_name, $password_hashed, $email, $rank, $joined, $last_login, $status, $status_text, $extra_data);
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
        $user_id = (int) $user_id;

        $sql = 'SELECT `user_login`, `user_display_name`, `user_password`, ';
        $sql.= '`user_email`, `user_rank`, `user_joined`, `user_last_login`, ';
        $sql.= '`user_status`, `user_status_text`, `user_extra_data` ';
        $sql.= 'FROM `users` WHERE `user_id` = "' . $user_id . '" ';
        $result = $oDB->query($sql);

        // Create user object and return
        if ($oDB->rows($result) === 1) {
            list($username, $display_name, $password_hashed, $email, $rank, $joined, $last_login, $status, $status_text, $extra_data) = $oDB->fetch($result);
            return new User($oWebsite, $user_id, $username, $display_name, $password_hashed, $email, $rank, $joined, $last_login, $status, $status_text, $extra_data);
        } else {
            return null;
        }
    }

    /**
     * Gets the username, if needed from the database.
     * @return string
     */
    public function get_username() {
        return $this->username;
    }

    public function get_display_name() {
        return $this->display_name;
    }

    /**
     * Get the hashed password for the user
     * @return password|string
     */
    public function get_password_hashed() {
        return $this->password_hashed;
    }

    /**
     * Call this when logging in an user. If password is correct, the last
     * login date is updated. If the password storage method was outdated, the
     * password is rehashed.
     * 
     * @param string $password_unhashed The password entered by the user.
     */
    public function verify_password_for_login($password_unhashed) {
        $password_hashed = $this->get_password_hashed();
        $logged_in = false;
        if (strlen($password_hashed) == 32 && $password_hashed[0] != '$') {
            // Still md5(sha1($pass)), update
            if (md5(sha1($password_unhashed)) == $password_hashed) {
                // Gets saved later on, when updating the last login
                $this->set_password($password_unhashed);
                $logged_in = true;
            }
        }

        // Try to use modern password verification
        if (!$logged_in) {
            $logged_in = (crypt($password_unhashed, $password_hashed) === $password_hashed);
        }

        // Update last login date (and possibly password has, see above) if successfull
        if ($logged_in) {
            $this->set_last_login(0);
            $this->save();
        }
        return $logged_in;
    }

    /**
     * Hashes the password using blowfish, or something weaker if blowfish is
     * not available. Using <code>crypt($pass,$hash)==$hash)</code> (or the
     * method verify_password) you can check if the given password matches the
     * hash.
     * @param string $password The password to hash.
     * @return string The hashed password.
     */
    public static function hash_password($password) {
        return StringHelper::hash($password);
    }

    public function is_admin() {
        return ($this->rank == 1);
    }

    public function get_rank() {
        return $this->rank;
    }

    public function get_email() {
        return $this->email;
    }

    public function get_id() {
        return $this->id;
    }

    public function get_joined() {
        return $this->joined;
    }

    public function get_last_login() {
        return $this->last_login;
    }

    /**
     * Gets the status of the user (normal/banned/deleted etc.)
     * @return int The status.
     */
    public function get_status() {
        return $this->status;
    }

    /**
     * Gets the status text by the user, usually set by the user themselves.
     * May contain HTML tags, so use htmlspecialchars($text) when displaying.
     * @return string The status text.
     */
    public function get_status_text() {
        return $this->status_text;
    }

    /**
     * Returns the extra data of this user in an associative array.
     * @return array The extra data as an array.
     */
    public function get_extra_data() {
        if (!empty($this->extra_data)) {
            return JSONHelper::string_to_array($this->extra_data);
        } else {
            return array();
        }
    }

    /**
     * Returns the extra data of the user as a string. The string may be empty,
     * but it won't be null.
     * @return string The extra data of the user.
     */
    public function get_extra_data_string() {
        return $this->extra_data;
    }

    /**
     * Set the username of the user.
     * @param string $username The new username.
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
        $this->password_hashed = self::hash_password($password);
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

    /**
     * Sets the date of the last login of the user. When set to 0, the current
     * date will be used.
     * @param int $last_login The date.
     */
    public function set_last_login($last_login) {
        $last_login = (int) $last_login;
        if ($last_login == 0) {
            $last_login = time();
        }
        $this->last_login = $last_login;
    }

    /**
     * Sets the status of the user (banned, deleted, etc.)
     * @param int $status The status.
     */
    public function set_status($status) {
        $this->status = (int) $status;
    }

    /**
     * Sets the status text of the user. (Non-banned) users can set this
     * themselves, for banned users this is usually the ban reason.
     * @param string $status_text The status text.
     */
    public function set_status_text($status_text) {
        $this->status_text = trim($status_text);
    }

    /**
     * Sets the extra data of the user.
     * @param mixed $extra_data The extra data, either empty, as an array of as
     * a json string.
     */
    public function set_extra_data($extra_data) {
        if (!$extra_data) {
            $this->extra_data = "";
        } else if (is_array($extra_data)) {
            $this->extra_data = JSONHelper::array_to_string($extra_data);
        } else {
            $this->extra_data = $extra_data;
        }
    }

    /**
     * Saves everything to the database
     */
    public function save() {
        $oDB = $this->website_object->get_database();

        if ($this->id === 0) {
            // New user
            $sql = "INSERT INTO `users` ( ";
            $sql.= "`user_rank`, ";
            $sql.= "`user_login`, ";
            $sql.= "`user_display_name`, ";
            $sql.= "`user_password`, ";
            $sql.= "`user_email`, ";
            $sql.= "`user_joined`, ";
            $sql.= "`user_last_login`, ";
            $sql.= "`user_status`, ";
            $sql.= "`user_status_text`, ";
            $sql.= "`user_extra_data`";
            $sql.= ")";
            $sql.= "VALUES (";
            $sql.= "'" . $this->rank . "',";
            $sql.= "'" . $oDB->escape_data($this->username) . "',";
            $sql.= "'" . $oDB->escape_data($this->display_name) . "',";
            $sql.= "'" . $oDB->escape_data($this->password_hashed) . "',";
            $sql.= "'" . $oDB->escape_data($this->email) . "',";
            $sql.= "NOW(),";
            $sql.= "NOW(),";
            $sql.= "'" . $this->status . "',";
            $sql.= "'" . $oDB->escape_data($this->status_text) . "',";
            $sql.= "'" . $oDB->escape_data($this->extra_data) . "'";
            $sql.= ")";
            echo "Inserting new user " . $sql;
            // Call query and update ID
            if ($oDB->query($sql)) {
                $this->id = $oDB->inserted_id();
                return true;
            } else {
                return false;
            }
        } else {
            // Update existing user
            $sql = "UPDATE `users` ";
            $sql.= 'SET `user_rank` = "' . $this->rank . '", ';
            $sql.= '`user_login` = "' . $oDB->escape_data($this->username) . '", ';
            $sql.= '`user_display_name` = "' . $oDB->escape_data($this->display_name) . '", ';
            $sql.= '`user_email` = "' . $oDB->escape_data($this->email) . '", ';
            $sql.= '`user_password` = "' . $oDB->escape_data($this->password_hashed) . '", ';
            $sql.= '`user_last_login` = "' . date("Y-m-d H:i:s", $this->last_login) . '", ';
            $sql.= '`user_status` = "' . $this->status . '", ';
            $sql.= '`user_status_text` = "' . $oDB->escape_data($this->status_text) . '", ';
            $sql.= '`user_extra_data` = "' . $oDB->escape_data($this->extra_data) . '" ';
            $sql.= 'WHERE `user_id` = "' . $this->id . '"';

            // Execute the query
            if ($oDB->query($sql)) {
                return true;
            } else {
                return false;
            }
        }
    }

}

?>