<?php

class Authentication {

    public static $ADMIN_RANK = 1;
    public static $MODERATOR_RANK = 0;
    public static $USER_RANK = 2;

    const NORMAL_STATUS = 0;
    const BANNED_STATUS = 1;
    const DELETED_STATUS = 2;
    const AUTHENTIATION_COOKIE = "remember_me";

    /* @var $websiteObject Website */

    protected $websiteObject;
    private $currentUser;
    private $loginFailed = false;

    public function __construct(Website $oWebsite) {
        $this->websiteObject = $oWebsite;

        // Check session and cookie
        if (isSet($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
            // Try to log in with session
            $user = User::getById($this->websiteObject, (int) $_SESSION['user_id']);
            if ($user == null || !$this->setCurrentUser($user)) {
                // Invalid user id in session, probably because the user account was just deleted
                unset($_SESSION['user_id']);
            }
        } else {
            // Try to log in with cookie
            $user = $this->getUserFromCookie();
            if ($user != null) {
                // Log in and refresh cookie
                if ($this->setCurrentUser($user)) {
                    $this->setLoginCookie();
                } else {
                    // User account is banned or deleted
                    $this->deleteLoginCookie();
                }
            } else {
                // Cookie is corrupted
                $this->deleteLoginCookie();
            }
        }
    }

    /**
     * Get the current user. Use the User.save function to save changes to the database.
     * @return User
     */
    public function getCurrentUser() {
        return $this->currentUser;
    }

    /**
     * Save that user object in the session. Doesn't modify the login cookie.
     * Null values are not permitted. Use log_out to log the current user out.
     * @param User $user The user to login
     * @return boolean Whether the user object was set. Returns false when the
     * account is banned or deleted.
     */
    public function setCurrentUser(User $user) {
        if (!$user->canLogIn()) {
            return false;
        }

        $_SESSION['user_id'] = $user->getId();
        if ($this->isHigherOrEqualRank($user->getRank(), self::$MODERATOR_RANK)) {
            $_SESSION['moderator'] = true;
        } else {
            $_SESSION['moderator'] = false;
        }
        $this->currentUser = $user;
        return true;
    }

    /**
     * Logs the user in with the given username and password.
     * @param string $username The username.
     * @param string $password The unhashed password.
     * @return boolean Whether the login was succesfull
     */
    public function logIn($username, $password) {
        $user = User::getByName($this->websiteObject, $username);
        if ($user != null && $user->loginCheck($this->websiteObject, $password)) {
            // Matches!
            $this->setCurrentUser($user);
            $this->setLoginCookie();
            return true;
        }
        return false;
    }

    /**
     * Checks whether the user has access to the current page. If not, a login
     * screen is optionally displayed.
     * @param int $minimumRank The minimum rank required.
     * @param boolean $showform Whether a login form should be shown on failure.
     * @return boolean Whether the login was succesfull.
     */
    public function check($minimumRank, $showform = true) {
        $minimumRank = (int) $minimumRank;
        $current_user = $this->getCurrentUser();

        // Try to login if data was sent
        if (isSet($_POST['user']) && isSet($_POST['pass'])) {
            if ($this->logIn($_POST['user'], $_POST['pass'])) {
                $current_user = $this->getCurrentUser();
            } else {
                $this->loginFailed = true;
            }
        }

        if ($current_user != null && $this->isHigherOrEqualRank($current_user->getRank(), $minimumRank)) {
            // Logged in with enough rights
            return true;
        } else {
            // Not logged in with enough rights
            if ($showform) {
                echo $this->getLoginForm($minimumRank);
            }
            return false;
        }
    }

    function getLoginForm($minimumRank = 0) { //laat een inlogformulier zien
        //huidige pagina ophalen
        $oWebsite = $this->websiteObject;
        $loginText = $oWebsite->t("users.please_log_in");
        $returnValue = "";
        if ($this->loginFailed && $oWebsite->getErrorCount() == 0) {
            // Only display the standard error if there was no other error
            $returnValue.= <<<EOT
                <div class="error">
                    <p>{$oWebsite->t("errors.invalid_username_or_password")}</p>
                </div>
EOT;
        }
        if ($minimumRank != self::$USER_RANK)
            $loginText.=' <strong><em> ' . $oWebsite->t("users.as_administrator") . '</em></strong>';
        $returnValue.= <<<EOT
            <form method="post" action="{$oWebsite->getUrlMain()}">
                    <h3>$loginText</h3>
                    <p>
                            <label for="user">{$oWebsite->t('users.username')}:</label> <br />
                            <input type="text" name="user" id="user" autofocus="autofocus" /> <br />
                            <label for="pass">{$oWebsite->t('users.password')}:</label> <br />
                            <input type="password" name="pass" id="pass" /> <br />

                            <input type="submit" value="{$oWebsite->t('main.log_in')}" class="button primary_button" />

EOT;
        foreach ($_REQUEST as $key => $value) {
            // Repost all variables
            if ($key != "user" && $key != "pass") {
                $returnValue.= '<input type="hidden" name="' . htmlSpecialChars($key) . '" value="' . htmlSpecialChars($value) . '" />';
            }
        }
        // End form and return it
        $returnValue.= <<<EOT
                    </p>
            </form>
EOT;
        return $returnValue;
    }

    function log_out() {
        unset($_SESSION['user_id']);
        unset($_SESSION['moderator']);
        $this->currentUser = null;
        $this->deleteLoginCookie();
    }

    /** Gets the number of registered users. Returns 0 on failure. */
    public function get_registered_usersCount() {
        $oDB = $this->websiteObject->getDatabase();
        $sql = "SELECT COUNT(*) FROM `users`";
        $result = $oDB->query($sql);
        if ($result) {
            $first_row = $oDB->fetchNumeric($result);
            return $first_row[0];
        }
        return 0;
    }

    /**
     * Gets all registered users.
     * @param int $start The index to start searching.
     * @param int $limit The maximum number of users to find.
     * @return \User List of users.
     */
    public function get_registered_users($start, $limit) {
        // Variables and casting
        $users = array();
        $oWebsite = $this->websiteObject;
        $oDB = $oWebsite->getDatabase();
        $start = max(0, (int) $start);
        $limit = max(1, (int) $limit);

        // Execute query
        $sql = "SELECT `user_id`, `user_login`, `user_display_name`, ";
        $sql.= "`user_password`, `user_email`, `user_rank`, `user_joined`, ";
        $sql.= "`user_last_login`, `user_status`, `user_status_text`, ";
        $sql.= "`user_extra_data` FROM `users` LIMIT $start, $limit";
        $result = $oDB->query($sql);

        // Parse and return results
        while (list(
        $id, $username, $display_name, $password_hashed, $email,
        $rank, $joined, $last_login, $status, $status_text, $extra_data
        ) = $oDB->fetchNumeric($result)) {
            $users[] = new User(
                            $oWebsite, $id, $username, $display_name,
                            $password_hashed, $email, $rank, $joined, $last_login,
                            $status, $status_text, $extra_data
            );
        }
        return $users;
    }

    /**
     * Returns the highest id in use for a rank.
     * @return int The highest id in use for a rank.
     */
    public function get_highest_rank_id() {
        return 2;
    }

    public function get_standard_rank_id() {
        return self::$USER_RANK;
    }

    /** Returns true if the given number is a valid rank id */
    public function is_valid_rank($id) {
        if ($id == self::$USER_RANK || $id == self::$ADMIN_RANK || $id == self::$MODERATOR_RANK) {
            return true;
        } else {
            return false;
        }
    }

    public function get_rank_name($id) {
        $oWebsite = $this->websiteObject;
        switch ($id) {
            case 0: return $oWebsite->t("users.rank.moderator");
            case 1: return $oWebsite->t("users.rank.admin");
            case 2: return $oWebsite->t("users.rank.user");
            default: return $oWebsite->t("users.rank.unknown");
        }
    }
    
    public function is_valid_status($id) {
        if ($id == self::NORMAL_STATUS || $id == self::DELETED_STATUS || $id == self::BANNED_STATUS) {
            return true;
        } else {
            return false;
        }
    }

    public function get_status_name($id) {
        $oWebsite = $this->websiteObject;
        switch ($id) {
            case self::BANNED_STATUS: return $oWebsite->t("users.status.banned");
            case self::DELETED_STATUS: return $oWebsite->t("users.status.deleted");
            case self::NORMAL_STATUS: return $oWebsite->t("users.status.allowed");
            default: return $oWebsite->t("users.status.unknown");
        }
    }

    public function isHigherOrEqualRank($rank_id, $compare_to) {
        if ($compare_to == self::$USER_RANK) {
            // Comparing to normal user
            return true;
        }
        if ($compare_to == self::$MODERATOR_RANK) {
            // Comparing to moderator
            if ($rank_id == self::$USER_RANK) {
                // Normal users aren't higher or equal
                return false;
            }
            return true;
        }
        if ($compare_to == self::$ADMIN_RANK) {
            // Only other admins have the same rank
            return $rank_id == self::$ADMIN_RANK;
        }
    }

    /**
     * Gets the account that is stored in the "remember me"-cookie. Returns null
     * if the cookie is invalid.
     * @return null|User The user, or null if the cookie is invalid.
     */
    public function getUserFromCookie() {
        if (!isSet($_COOKIE[self::AUTHENTIATION_COOKIE])) {
            return null;
        };

        // Get and split the cookie
        $auth_cookie = $_COOKIE[self::AUTHENTIATION_COOKIE];
        $cookie_split = explode('|', $auth_cookie);
        if (count($cookie_split) != 3) {
            // Invalid cookie, not consisting of three parts
            return null;
        }

        $user = User::getById($this->websiteObject, (int) $cookie_split[0]);
        if ($user == null) {
            // Invalid user id
            return null;
        }

        $stored_hash = $cookie_split[1];
        $expires = $cookie_split[2];
        $verification_string = $expires . "|" . $user->getPasswordHashed();

        if (HashHelper::verifyHash($verification_string, $stored_hash)) {
            return $user;
        } else {
            // Invalid hash
            return null;
        }
    }

    /**
     * Sets/refreshes the "remember me" for the currently connected user.
     * If the headers have already been sent, this method returns false.
     * @throws BadMethodCallException If no user logged in.
     * @return boolean Whether the cookie was set.
     */
    public function setLoginCookie() {
        $user = $this->getCurrentUser();
        if ($user == null) {
            throw new BadMethodCallException("Tried to create 'Remember me' cookie while not logged in");
        }
        if (headers_sent()) {
            return false;
        }
        $expires = time() + 60 * 60 * 24 * 30; // Expires in 30 days
        $hash = HashHelper::hash($expires . "|" . $user->getPasswordHashed());
        $cookie_value = $user->getId() . "|" . $hash . "|" . $expires;
        setcookie(self::AUTHENTIATION_COOKIE, $cookie_value, $expires, '/');
        return true;
    }

    /**
     * Deletes the "remember me" cookie. If the headers have already been sent,
     * this method does nothing and returns false.
     * @return boolean Whether the cookie was removed.
     */
    public function deleteLoginCookie() {
        if (headers_sent()) {
            return false;
        }
        setcookie(self::AUTHENTIATION_COOKIE, "", time() - 1, '/');
        return true;
    }

}

?>