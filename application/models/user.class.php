<?php

class User {

    const GRAVATAR_URL_BASE = "http://www.gravatar.com/avatar/";

    protected $websiteObject;
    protected $username;
    protected $displayName;
    protected $passwordHashed;
    protected $id;
    protected $email;
    protected $rank;
    protected $joined;
    protected $lastLogin;
    protected $status;
    protected $statusText;
    protected $extraData;

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
    public function __construct(Website $oWebsite, $id, $username,
            $display_name, $password_hashed, $email, $rank, $joined,
            $last_login, $status, $status_text, $extra_data = null) {
        $this->websiteObject = $oWebsite;
        $this->id = (int) $id;
        $this->setUsername($username);
        $this->setDisplayName($display_name);
        $this->setPasswordHashed($password_hashed);
        $this->setEmail($email);
        $this->setRank($rank);
        $this->joined = (int) $joined;
        if ($this->joined == 0) {
            $this->joined = time();
        }
        $this->setLastLogin($last_login);
        $this->setStatus($status);
        $this->setStatusText($status_text);
        $this->setExtraData($extra_data);
    }

    /**
     * Returns whether this user can log in. Returns false if the account has
     * been banned or deleted.
     * @return boolean Whether the user can log in.
     */
    public function canLogIn() {
        if ($this->status == Authentication::DELETED_STATUS) {
            return false;
        }
        if ($this->status == Authentication::BANNED_STATUS) {
            return false;
        }
        return true;
    }

    // Vulnerable to SQL injection attacks, so it must be private. Safe, public
    // method are available below.
    private static function getByCondition(Website $oWebsite, $sqlCondition) {
        $oDB = $oWebsite->getDatabase();



        $sql = 'SELECT `user_id`, `user_login`, `user_display_name`, `user_password`, ';
        $sql.= '`user_email`, `user_rank`, `user_joined`, `user_last_login`, ';
        $sql.= '`user_status`, `user_status_text`, `user_extra_data` ';
        $sql.= 'FROM `users` WHERE ' . $sqlCondition;
        $result = $oDB->query($sql);

        // Create user object and return
        if ($oDB->rows($result) > 0) {
            list($id, $username, $displayName, $passwordHashed, $email, $rank, $joined, $lastLogin, $status, $statusText, $extraData) = $oDB->fetchNumeric($result);
            return new User($oWebsite, $id, $username, $displayName, $passwordHashed, $email, $rank, $joined, $lastLogin, $status, $statusText, $extraData);
        } else {
            return null;
        }
    }

    /**
     * Get the user by name. Returns null if the user isn't found.
     * @param Website $oWebsite The Website object.
     * @param string $username The username. Case insensitive.
     * @return User|null The User, or null if it isn't found.
     */
    public static function getByName(Website $oWebsite, $username) {
        $escapedUsername = $oWebsite->getDatabase()->escapeData(strToLower($username));
        $sqlCondition = '`user_login` = "' . $escapedUsername . '"';
        return self::getByCondition($oWebsite, $sqlCondition);
    }

    /**
     * Get the user by email. Returns null if the user isn't found.
     * @param Website $oWebsite The Website object.
     * @param string $email The email address. Case sensitive.
     * @return User|null The User, or null if it isn't found.
     */
    public static function getByEmail(Website $oWebsite, $email) {
        $escapedEmail = $oWebsite->getDatabase()->escapeData($email);
        $sqlCondition = '`user_email` = "' . $escapedEmail . '"';
        return self::getByCondition($oWebsite, $sqlCondition);
    }

    /**
     * Safe way of getting the user object when you know the id. Returns null if
     * the user doesn't exist.
     * @param Website $oWebsite The Website object.
     * @param int $id The user id.
     * @return User|null The User, or null if it isn't found.
     */
    public static function getById(Website $oWebsite, $userId) {
        $userId = (int) $userId;
        $sqlCondition = '`user_id` = "' . $userId . '"';
        return self::getByCondition($oWebsite, $sqlCondition);
    }

    /**
     * Gets the username, if needed from the database.
     * @return string
     */
    public function getUsername() {
        return $this->username;
    }

    public function getDisplayName() {
        return $this->displayName;
    }

    // Gravatar
    // Gets the gravatar url based on the hash and size.
    private static function getUserAvatarUrl($hash, $gravatarSize) {
        if ((int) $gravatarSize < 5) {
            throw new BadMethodCallException("Gravatar size $gravatarSize is too small");
        }
        $gravatar_url = self::GRAVATAR_URL_BASE . $hash;
        $gravatar_url.= "?size=$gravatarSize&d=mm";
        return $gravatar_url;
    }

    /**
     * Returns the url of the default gravatar.
     * @param int $gravatarSize Size (width and height) of the gravatar in pixels.
     * @return string The url.
     */
    public static function getStandardAvatarUrl($gravatarSize = 400) {
        return self::getUserAvatarUrl("00000000000000000000000000000000", $gravatarSize);
    }

    /**
     * Returns the url of the gravatar of an email.
     * @param string $email The email of the user, may be empty or null.
     * @param int $gravatarSize Size (width and height) of the gravatar in pixels.
     * @return string The url.
     */
    public static function getAvatarUrlFromEmail($email, $gravatarSize) {
        if ($email != null && strLen($email) > 0) {
            return self::getUserAvatarUrl(md5(strToLower($email)), $gravatarSize);
        } else {
            // No email given
            return self::getStandardAvatarUrl($gravatarSize);
        }
    }

    /**
     * Returns the url of the gravatar of the user.
     * @param int $gravatarSize Size (width and height) of the gravatar in pixels.
     * @return string The url.
     */
    public function getAvatarUrl($gravatarSize = 400) {
        return self::getAvatarUrlFromEmail($this->email, $gravatarSize);
    }

    /**
     * Get the hashed password for the user
     * @return password|string
     */
    public function getPasswordHashed() {
        return $this->passwordHashed;
    }

    /**
     * Verifies that the given unhashed password matches the stored hashed password.
     * @param string $passwordUnhashed The unhashed password.
     * @return boolean True if the given password is correct, false otherwise.
     */
    public function verifyPassword($passwordUnhashed) {
        $passwordHashed = $this->getPasswordHashed();
        if (strLen($passwordHashed) == 32 && $passwordHashed[0] != '$') {
            return (md5(sha1($passwordUnhashed)) == $passwordHashed);
        } else {
            return (crypt($passwordUnhashed, $passwordHashed) === $passwordHashed);
        }
    }

    /**
     * Call this when logging in an user. If password is correct, the last
     * login date is updated. If the password storage method was outdated, the
     * password is rehashed.
     * 
     * @param Website $oWebsite The website object.
     * @param string $password_unhashed The password entered by the user.
     */
    public function loginCheck(Website $oWebsite, $password_unhashed) {
        $password_hashed = $this->getPasswordHashed();
        $loggedIn = false;
        if (strLen($password_hashed) == 32 && $password_hashed[0] != '$') {
            // Still md5(sha1($pass)), update
            if (md5(sha1($password_unhashed)) == $password_hashed) {
                // Gets saved later on, when updating the last login
                $this->setPassword($password_unhashed);
                $loggedIn = true;
            }
        }

        // Try to use modern password verification
        if (!$loggedIn) {
            $loggedIn = (crypt($password_unhashed, $password_hashed) === $password_hashed);
        }

        if ($loggedIn) {
            // Check whether the account is deleted
            if ($this->status == Authentication::DELETED_STATUS) {
                // Act like the account doesn't exist
                return false;
            }

            // Check whether the account is banned
            if ($this->status == Authentication::BANNED_STATUS) {
                $oWebsite->addError($oWebsite->tReplaced("users.status.banned.your_account", $this->statusText));
                return false;
            }

            // Update last login date (and possibly password hash see above) if successfull
            $this->setLastLogin(0);
            $this->save();
        }
        return $loggedIn;
    }

    /**
     * Hashes the password using blowfish, or something weaker if blowfish is
     * not available. Using <code>crypt($pass,$hash)==$hash)</code> (or the
     * method verify_password) you can check if the given password matches the
     * hash.
     * @param string $password The password to hash.
     * @return string The hashed password.
     */
    public static function hashPassword($password) {
        return HashHelper::hash($password);
    }

    public function isAdmin() {
        return ($this->rank == Authentication::$ADMIN_RANK);
    }

    public function isStaff() {
        return $this->rank == Authentication::$MODERATOR_RANK || Authentication::$ADMIN_RANK;
    }

    public function getRank() {
        return $this->rank;
    }

    public function getEmail() {
        return $this->email;
    }

    public function getId() {
        return $this->id;
    }

    public function getJoined() {
        return $this->joined;
    }

    public function getLastLogin() {
        return $this->lastLogin;
    }

    /**
     * Gets the status of the user (normal/banned/deleted etc.)
     * @return int The status.
     */
    public function getStatus() {
        return $this->status;
    }

    /**
     * Gets the status text by the user, usually set by the user themselves.
     * May contain HTML tags, so use htmlSpecialChars($text) when displaying.
     * @return string The status text.
     */
    public function getStatusText() {
        return $this->statusText;
    }

    /**
     * Returns the extra data of this user in an associative array.
     * @return array The extra data as an array.
     */
    public function getExtraData() {
        if (!empty($this->extraData)) {
            return JSONHelper::stringToArray($this->extraData);
        } else {
            return array();
        }
    }

    /**
     * Returns the extra data of the user as a string. The string may be empty,
     * but it won't be null.
     * @return string The extra data of the user.
     */
    public function getExtraDataString() {
        return $this->extraData;
    }

    /**
     * Set the username of the user.
     * @param string $username The new username.
     */
    public function setUsername($username) {
        $this->username = strtolower(trim($username));
    }

    /**
     * Set the display name of the user
     * @param string $display_name
     */
    public function setDisplayName($display_name) {
        $this->displayName = htmlSpecialChars(trim($display_name));
    }

    /**
     * Set the password of the user. Password must be unhashed.
     * @param string $password
     */
    public function setPassword($password) {
        $this->passwordHashed = self::hashPassword($password);
    }

    /**
     * Set the hashed password of the user directly.
     * @param string $password_hashed The hashed password.
     */
    public function setPasswordHashed($password_hashed) {
        $this->passwordHashed = $password_hashed;
    }

    /**
     * Sets the email of the user. Case senstive.
     * @param string $email The email.
     */
    public function setEmail($email) {
        $this->email = $email;
    }

    /**
     * Change the status of the user
     * @param int $admin
     */
    public function setRank($rank) {
        $this->rank = (int) $rank;
    }

    /**
     * Sets the date of the last login of the user. When set to 0, the current
     * date will be used.
     * @param int $last_login The date.
     */
    public function setLastLogin($last_login) {
        $last_login = (int) $last_login;
        if ($last_login == 0) {
            $last_login = time();
        }
        $this->lastLogin = $last_login;
    }

    /**
     * Sets the status of the user (banned, deleted, etc.)
     * @param int $status The status.
     */
    public function setStatus($status) {
        $this->status = (int) $status;
    }

    /**
     * Sets the status text of the user. (Non-banned) users can set this
     * themselves, for banned users this is usually the ban reason.
     * @param string $status_text The status text.
     */
    public function setStatusText($status_text) {
        $this->statusText = trim($status_text);
    }

    /**
     * Sets the extra data of the user.
     * @param mixed $extra_data The extra data, either empty, as an array of as
     * a json string.
     */
    public function setExtraData($extra_data) {
        if (!$extra_data) {
            $this->extraData = "";
        } else if (is_array($extra_data)) {
            $this->extraData = JSONHelper::arrayToString($extra_data);
        } else {
            $this->extraData = $extra_data;
        }
    }

    /**
     * Saves everything to the database
     */
    public function save() {
        $oDB = $this->websiteObject->getDatabase();

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
            $sql.= "'" . $oDB->escapeData($this->username) . "',";
            $sql.= "'" . $oDB->escapeData($this->displayName) . "',";
            $sql.= "'" . $oDB->escapeData($this->passwordHashed) . "',";
            $sql.= "'" . $oDB->escapeData($this->email) . "',";
            $sql.= "NOW(),";
            $sql.= "NOW(),";
            $sql.= "'" . $this->status . "',";
            $sql.= "'" . $oDB->escapeData($this->statusText) . "',";
            $sql.= "'" . $oDB->escapeData($this->extraData) . "'";
            $sql.= ")";
            // Call query and update ID
            if ($oDB->query($sql)) {
                $this->id = $oDB->getLastInsertedId();
                return true;
            } else {
                return false;
            }
        } else {
            // Update existing user
            $sql = "UPDATE `users` ";
            $sql.= 'SET `user_rank` = "' . $this->rank . '", ';
            $sql.= '`user_login` = "' . $oDB->escapeData($this->username) . '", ';
            $sql.= '`user_display_name` = "' . $oDB->escapeData($this->displayName) . '", ';
            $sql.= '`user_email` = "' . $oDB->escapeData($this->email) . '", ';
            $sql.= '`user_password` = "' . $oDB->escapeData($this->passwordHashed) . '", ';
            $sql.= '`user_last_login` = "' . date("Y-m-d H:i:s", $this->lastLogin) . '", ';
            $sql.= '`user_status` = "' . $this->status . '", ';
            $sql.= '`user_status_text` = "' . $oDB->escapeData($this->statusText) . '", ';
            $sql.= '`user_extra_data` = "' . $oDB->escapeData($this->extraData) . '" ';
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