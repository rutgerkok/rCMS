<?php

class Authentication {

    public static $ADMIN_RANK = 1;
    public static $MODERATOR_RANK = 0;
    public static $USER_RANK = 2;

    /* @var $website_object Website */
    protected $website_object;
    private $current_user;
    private $login_failed = false;

    public function __construct(Website $oWebsite) {
        $this->website_object = $oWebsite;
    }

    /**
     * Get the current user. Use the User.save function to save changes to the database.
     * @return User
     */
    public function get_current_user() {
        if (isset($this->current_user)) {
            // Object cached
            return $this->current_user;
        } else {
            if (isset($_SESSION['id']) && isset($_SESSION['user']) && isset($_SESSION['display_name']) && isset($_SESSION['pass']) && isset($_SESSION['email']) && isset($_SESSION['admin'])) {
                $this->current_user = new User(
                                $this->website_object,
                                $_SESSION['id'],
                                $_SESSION['user'],
                                $_SESSION['display_name'],
                                $_SESSION['pass'],
                                $_SESSION['email'],
                                $_SESSION['admin']
                );
                return $this->current_user;
            } else {
                // Not logged in
                return null;
            }
        }
    }

    /**
     * Save that user object in the session
     * @param User $user The user to login
     */
    public function set_current_user($user) {
        if ($user == null || !($user instanceof User)) {
            // Log out
            unset($_SESSION['id']);
            unset($_SESSION['user']);
            unset($_SESSION['display_name']);
            unset($_SESSION['pass']);
            unset($_SESSION['email']);
            unset($_SESSION['admin']);
            unset($this->current_user);
        } else {
            // Log in
            $_SESSION['id'] = $user->get_id();
            $_SESSION['user'] = $user->get_username();
            $_SESSION['display_name'] = $user->get_display_name();
            $_SESSION['pass'] = $user->get_password_hashed();
            $_SESSION['email'] = $user->get_email();
            $_SESSION['admin'] = $user->get_rank();
            $this->current_user = $user;
        }
    }

    /**
     * Logs the user in with the given username and password.
     * @param string $username
     * @param string $password
     * @return boolean Whether the login was succesfull
     */
    public function log_in($username, $password) {
        $user = User::get_by_name($this->website_object, $username);
        if ($user != null && $user->get_password_hashed() == md5(sha1($password))) {
            // Matches!
            $this->set_current_user($user);
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
        if($this->login_failed) {
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
        $this->set_current_user(null);
    }

    function get_users_table() { //geeft de gebruikers als tabel
        $oWebsite = $this->website_object;
        $oDB = $oWebsite->get_database();

        $sql = "SELECT gebruiker_id,gebruiker_admin,gebruiker_login,gebruiker_naam,gebruiker_email FROM `gebruikers` ";
        $result = $oDB->query($sql);

        $return_value = "<table style=\"width:98%\">\n";
        $return_value.="<tr><th>" . $oWebsite->t("users.username") . "</th><th>" . $oWebsite->t("users.display_name") . "</th><th>" . $oWebsite->t("users.email") . "</th><th>" . $oWebsite->t("users.rank") . "</th><th>" . $oWebsite->t("main.edit") . "</th></tr>\n"; //login-naam-email-admin-bewerk
        $return_value.='<tr><td colspan="5"><a class="arrow" href="' . $oWebsite->get_url_page("create_account") . '">' . $oWebsite->t("users.create") . "...</a></td></tr>\n"; //maak nieuwe account
        if ($oDB->rows($result) > 0) {
            while (list($id, $rank, $login, $name, $email) = $oDB->fetch($result)) {

                //email als link weergeven
                $emaillink = "<a href=\"mailto:$email\">$email</a>";
                if (empty($email)) {
                    //niet ingesteld
                    $emaillink = '<em>' . $oWebsite->t("main.not_set") . '</em>';
                }

                $return_value.="<tr>";
                $return_value.="<td title=\"$login\">$login</td>";
                $return_value.="<td title=\"$name\">$name</td>";
                $return_value.="<td title=\"$email\">$emaillink</td>";
                $return_value.="<td>" . $this->get_rank_name($rank) . "</td>";
                if ($id == $this->current_user->get_id()) {
                    $return_value.="<td style=\"font-size:80%\">";
                    $return_value.='<a href="' . $oWebsite->get_url_page("edit_password") . '">' . $oWebsite->t("users.password") . "</a> |\n"; //wachtwoord
                    $return_value.='<a href="' . $oWebsite->get_url_page("edit_email") . '">' . $oWebsite->t("users.email") . "</a></td>\n"; //email
                } else {
                    $return_value.="<td style=\"font-size:80%\">";
                    $return_value.='<a href="' . $oWebsite->get_url_page("edit_password", $id) . '">' . $oWebsite->t("users.password") . "</a> |\n";
                    $return_value.='<a href="' . $oWebsite->get_url_page("edit_email", $id) . '">' . $oWebsite->t("users.email") . "</a> |\n";
                    $return_value.='<a href="' . $oWebsite->get_url_page("log_in_other", $id) . '">' . $oWebsite->t("main.log_in") . "</a></td>\n";
                }
            }
        }
        $return_value.="</table>";
        return $return_value;
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
        if($id == self::$USER_RANK || $id == self::$ADMIN_RANK || $id == self::$MODERATOR_RANK) {
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

}

?>