<?php

class Authentication {

    public static $ADMIN_RANK = 1;
    
    protected $website_object;
    
    private $current_user;

    function __construct(Website $oWebsite) {
        $this->website_object = $oWebsite;
    }

    /**
     * Get the current user. Use the User.save function to save changes to the database.
     * @return User
     */
    function get_current_user() {
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
     * @param User $user
     */
    function set_current_user($user) {
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
     * Logs the user in
     * @param string $username
     * @param string $password
     * @return boolean
     */
    function log_in($username, $password) {
        $user = User::get_by_name($this->website_object, $username);
        if ($user != null && $user->get_password_hashed() == md5(sha1($password))) {
            // Matches!
            $this->set_current_user($user);
            return true;
        }
        return false;
    }

    function check($admin, $showform) {
        $current_user = $this->get_current_user();
        if ($current_user != null && ($admin == false || $current_user->is_admin())) { //ingelogd met voldoende rechten
            return true;
        } else { //niet ingelogd met voldoende rechten, kijk of dat inmiddels veranderd is
            if (isset($_POST['user']) && isset($_POST['pass']) && $this->log_in($_POST['user'], $_POST['pass'])) {
                return true;
            } else { //of niet, laat dan het inlogformulier zien als dat nodig is en geef false door
                if ($showform) {
                    $this->echo_login_form($admin);
                }
                return false;
            }
        }
    }

    //GEVAARLIJKE FUNCTIE: log in met alleen een id. Mag alleen worden aangeroepen als de momenteel ingelogde gebruiker admin is
    function log_in_other($id) {
        $oWebsite = $this->website_object;

        $id = (int) $id;
        if ($id == 0) {
            $oWebsite->add_error("Cannot login as other account. Invalid ID! ID: " . $id);
            return false;
        }

        $this->set_current_user(new User($id));
    }

    function echo_login_form($admin = false) { //laat een inlogformulier zien
        //huidige pagina ophalen
        $oWebsite = $this->website_object;
        $p = urlencode($oWebsite->get_pagevar('file'));
        $logintext = $oWebsite->t("users.please_log_in");
        if ($admin)
            $logintext.=' <strong><em> ' . $oWebsite->t("users.as_administrator") . '</em></strong>';
        echo <<<EOT
            <form method="post" action="{$oWebsite->get_url_main()}">
                    <h3>$logintext</h3>
                    <p>
                            <label for="user">{$oWebsite->t('users.username')}:</label> <br />
                            <input type="text" name="user" id="user" autofocus="autofocus" /> <br />
                            <label for="pass">{$oWebsite->t('users.password')}:</label> <br />
                            <input type="password" name="pass" id="pass" /> <br />

                            <input type="submit" value="{$oWebsite->t('main.log_in')}" class="button" />

                            <input type="hidden" name="p" value="$p" />
                    </p>
            </form>
EOT;
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
                if (true || $id != $_SESSION['id']) { //eigen account niet aanpassen
                    //email als link weergeven
                    $emaillink = "<a href=\"mailto:$email\">$email</a>";
                    if (empty($email)) {
                        $emaillink = '<em>' . $oWebsite->t("main.not_set") . '</em>';
                    }//niet ingesteld

                    $return_value.="<tr>";
                    $return_value.="<td title=\"$login\">$login</td>";
                    $return_value.="<td title=\"$name\">$name</td>";
                    $return_value.="<td title=\"$email\">$emaillink</td>";
                    $return_value.="<td>" . $this->get_rank_name($rank) . "</td>";
                    if ($id == $this->current_user->get_id()) {
                        $return_value.="<td style=\"font-size:80%\">";
                        $return_value.='<a href="' . $oWebsite->get_url_page("change_password") . '">' . $oWebsite->t("users.password") . '</a>|'; //wachtwoord
                        $return_value.='<a href="' . $oWebsite->get_url_page("change_email") . '">' . $oWebsite->t("users.email") . "</a></td>\n"; //email
                    } elseif ($rank == self::$ADMIN_RANK) {
                        $return_value.="<td style=\"font-size:80%\"><em>" . $oWebsite->t('users.rank.admin') . "!</em></td>\n"; //beheerder!
                    } else {
                        $return_value.="<td style=\"font-size:80%\">";
                        $return_value.='<a href="' . $oWebsite->get_url_page("password_other", $id) . '">' . $oWebsite->t("users.password") . '</a>|'; //wachtwoord
                        $return_value.='<a href="' . $oWebsite->get_url_page("email_other", $id) . '">' . $oWebsite->t("users.email") . "</a></td>\n"; //email
                    }
                }
            }
        }
        $return_value.="</table>";
        return $return_value;
    }
    
    public function get_highest_rank_id() {
        return 2;
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

}

?>