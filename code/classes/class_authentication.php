<?php

class Authentication {

    public static $ADMIN_RANK = 1;
    public static $MODERATOR_RANK = 0;
    public static $USER_RANK = 2;

    const NORMAL_STATUS = 0;
    const BANNED_STATUS = 1;
    const DELETED_STATUS = 2;
    const AUTHENTIATION_COOKIE = "remember_me";

    /* @var $website_object Website */

    protected $website_object;
    private $current_user;
    private $login_failed = false;

    public function __construct(Website $oWebsite) {
        $this->website_object = $oWebsite;

        // Check session and cookie
        if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']) && $_SESSION['user_id'] > 0) {
            // Try to log in with session
            $this->current_user = User::get_by_id($this->website_object, (int) $_SESSION['user_id']);
            if ($this->current_user == null) {
                // Invalid user id in session, probably because the user account was just deleted
                unset($_SESSION['user_id']);
            }
        } else {
            // Try to log in with cookie
            $user = $this->get_user_from_cookie();
            if ($user != null) {
                // Log in en refresh cookie
                $this->set_current_user($user);
                $this->set_login_cookie();
            }
        }
    }

    /**
     * Get the current user. Use the User.save function to save changes to the database.
     * @return User
     */
    public function get_current_user() {
        return $this->current_user;
    }

    /**
     * Save that user object in the session. Doesn't modify the login cookie.
     * Null values are not permitted. Use log_out to log the current user out.
     * @param User $user The user to login
     */
    public function set_current_user(User $user) {
        $_SESSION['user_id'] = $user->get_id();
        $this->current_user = $user;
    }

    /**
     * Logs the user in with the given username and password.
     * @param string $username The username.
     * @param string $password The unhashed password.
     * @return boolean Whether the login was succesfull
     */
    public function log_in($username, $password) {
        $user = User::get_by_name($this->website_object, $username);
        if ($user != null && $user->verify_password_for_login($password)) {
            // Matches!
            $this->set_current_user($user);
            if(!headers_sent()) {
                $this->set_login_cookie();
            }
            return true;
        }
        return false;
    }

    /**
     * Checks whether the user has access to the current page. If not, a login
     * screen is optionally displayed.
     * @param int $minimum_rank The minimum rank required.
     * @param boolean $showform Whether a login form should be shown on failure.
     * @return boolean Whether the login was succesfull.
     */
    public function check($minimum_rank, $showform = true) {
        $minimum_rank = (int) $minimum_rank;
        $current_user = $this->get_current_user();

        // Try to login if data was sent
        if (isset($_POST['user']) && isset($_POST['pass'])) {
            if ($this->log_in($_POST['user'], $_POST['pass'])) {
                $current_user = $this->get_current_user();
            } else {
                $this->login_failed = true;
            }
        }

        if ($current_user != null && $this->is_higher_or_equal_rank($current_user->get_rank(), $minimum_rank)) {
            // Logged in with enough rights
            return true;
        } else {
            // Not logged in with enough rights
            if ($showform) {
                echo $this->get_login_form($minimum_rank);
            }
            return false;
        }
    }

    function get_login_form($minimum_rank = 0) { //laat een inlogformulier zien
        //huidige pagina ophalen
        $oWebsite = $this->website_object;
        $logintext = $oWebsite->t("users.please_log_in");
        $return_value = "";
        if ($this->login_failed) {
            $return_value.= <<<EOT
                <div class="error">
                    <p>{$oWebsite->t("errors.invalid_username_or_password")}</p>
                </div>
EOT;
        }
        if ($minimum_rank != self::$USER_RANK)
            $logintext.=' <strong><em> ' . $oWebsite->t("users.as_administrator") . '</em></strong>';
        $return_value.= <<<EOT
            <form method="post" action="{$oWebsite->get_url_main()}">
                    <h3>$logintext</h3>
                    <p>
                            <label for="user">{$oWebsite->t('users.username')}:</label> <br />
                            <input type="text" name="user" id="user" autofocus="autofocus" /> <br />
                            <label for="pass">{$oWebsite->t('users.password')}:</label> <br />
                            <input type="password" name="pass" id="pass" /> <br />

                            <input type="submit" value="{$oWebsite->t('main.log_in')}" class="button" />

EOT;
        foreach ($_REQUEST as $key => $value) {
            // Repost all variables
            if ($key != "user" && $key != "pass") {
                $return_value.= '<input type="hidden" name="' . htmlspecialchars($key) . '" value="' . htmlspecialchars($value) . '" />';
            }
        }
        // End form and return it
        $return_value.= <<<EOT
                    </p>
            </form>
EOT;
        return $return_value;
    }

    function log_out() {
        unset($_SESSION['user_id']);
        unset($this->current_user);
        $this->delete_login_cookie();
    }

    /** Gets the number of registered users. Returns 0 on failure. */
    public function get_registered_users_count() {
        $oDB = $this->website_object->get_database();
        $sql = "SELECT COUNT(*) FROM `users`";
        $result = $oDB->query($sql);
        if ($result) {
            $first_row = $oDB->fetch($result);
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
        $oWebsite = $this->website_object;
        $oDB = $oWebsite->get_database();
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
        ) = $oDB->fetch($result)) {
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
        $oWebsite = $this->website_object;
        switch ($id) {
            case 0: return $oWebsite->t("users.rank.moderator");
            case 1: return $oWebsite->t("users.rank.admin");
            case 2: return $oWebsite->t("users.rank.user");
            default: return $oWebsite->t("users.rank.unknown");
        }
    }

    public function is_higher_or_equal_rank($rank_id, $compare_to) {
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
    public function get_user_from_cookie() {
        if (!isset($_COOKIE[self::AUTHENTIATION_COOKIE])) {
            return null;
        };

        // Get and split the cookie
        $auth_cookie = $_COOKIE[self::AUTHENTIATION_COOKIE];
        $cookie_split = explode('|', $auth_cookie);
        if (count($cookie_split) != 3) {
            // Invalid cookie, not consisting of three parts
            return null;
        }

        $user = User::get_by_id($this->website_object, (int) $cookie_split[0]);
        if ($user == null) {
            // Invalid user id
            return null;
        }

        $stored_hash = $cookie_split[1];
        $expires = $cookie_split[2];
        $verification_string = $expires . "|" . $user->get_password_hashed();

        if (StringHelper::verify_hash($verification_string, $stored_hash)) {
            return $user;
        } else {
            // Invalid hash
            return null;
        }
    }

    /**
     * Sets/refreshes the "remember me" for the currently connected user.
     * Requires that the headers have not been sent yet.
     * @throws BadMethodCallException If no user logged in.
     */
    public function set_login_cookie() {
        $user = $this->get_current_user();
        if ($user == null) {
            throw new BadMethodCallException("Tried to create 'Remember me' cookie while not logged in");
        }
        $expires = time() + 60 * 60 * 24 * 30; // Expires in 30 days
        $hash = StringHelper::hash($expires . "|" . $user->get_password_hashed());
        $cookie_value = $user->get_id() . "|" . $hash . "|" . $expires;
        setcookie(self::AUTHENTIATION_COOKIE, $cookie_value, $expires, '/');
    }

    /**
     * Deletes the "remember me" cookie. Requires that the headers have not
     * been sent yet.
     */
    public function delete_login_cookie() {
        setcookie(self::AUTHENTIATION_COOKIE, "", time() - 1, '/');
    }

}

?>