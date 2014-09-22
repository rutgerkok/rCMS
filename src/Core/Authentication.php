<?php

namespace Rcms\Core;

use DateTime;
use Rcms\Core\Exception\NotFoundException;

class Authentication {

    public static $LOGGED_OUT_RANK = -1;
    public static $ADMIN_RANK = 1;
    public static $MODERATOR_RANK = 0;
    public static $USER_RANK = 2;

    const NORMAL_STATUS = 0;
    const BANNED_STATUS = 1;
    const DELETED_STATUS = 2;
    const AUTHENTIATION_COOKIE = "remember_me";

    /**
     * @var Website The website object.
     */
    protected $websiteObject;

    /**
     * @var UserRepository The user repository.
     */
    protected $userRepo;

    /**
     * @var User|null The current user.
     */
    private $currentUser;

    /**
     * @var boolean True if a failed login attempt was made during this request.
     */
    private $loginFailed = false;

    /**
     * Creates a new authentication checker.
     * @param Website $oWebsite The website object.
     * @param UserRepository $userRepo The user repository. Omitting
     * this parameter is deprecated. When this parameter is omitted, the default
     * database is used for creating one.
     */
    public function __construct(Website $oWebsite,
            UserRepository $userRepo = null) {
        $this->websiteObject = $oWebsite;
        $this->userRepo = $userRepo? : new UserRepository($oWebsite->getDatabase());

        // Check session and cookie
        if (isSet($_SESSION["user_id"])) {
            if (!$this->setCurrentUserFromId($_SESSION["user_id"])) {
                // Invalid session variable
                $this->logOut();
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
     * Gets the user repository being used for this authentication object.
     * @return UserRepository The user repository.
     */
    public function getUserRepository() {
        return $this->userRepo;
    }

    /**
     * Sets the current user using the given user id.
     * @param int $userId The user id.
     * @return boolean True if the user was set, false otherwise (happens when
     * an invalid id is given, or the user is banned).
     */
    private function setCurrentUserFromId($userId) {
        $userId = (int) $userId;
        if ($userId <= 0) {
            return false;
        }
        try {
            $user = $this->userRepo->getById($userId);
            return $this->setCurrentUser($user);
        } catch (NotFoundException $e) {
            return false;
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
            // User is banned or something
            return false;
        }

        $_SESSION['user_id'] = $user->getId();
        if ($this->isHigherOrEqualRank($user->getRank(), self::$MODERATOR_RANK)) {
            // This session vars are purely used for CKEditor.
            // In rCMS there are much better, easier and safer ways to check this.
            $_SESSION['moderator'] = true;
        } else {
            $_SESSION['moderator'] = false;
        }
        $this->currentUser = $user;
        return true;
    }

    /**
     * Logs the user in with the given username and password.
     * @param string $usernameOrEmail The username.
     * @param string $password The unhashed password.
     * @return boolean Whether the login was succesfull
     */
    public function logIn($usernameOrEmail, $password) {
        try {
            $user = $this->userRepo->getByNameOrEmail($usernameOrEmail);

            if ($this->loginCheck($user, $password)) {
                // Matches!
                $this->setCurrentUser($user);
                $this->setLoginCookie();
                return true;
            }
            return false;
        } catch (NotFoundException $e) {
            return false;
        }
    }

    /**
     * Call this when logging in an user. If password is correct, the last
     * login date is updated. If the password storage method was outdated, the
     * password is rehashed.
     * 
     * @param User $user The user.
     * @param string $password_unhashed The password entered by the user.
     */
    protected function loginCheck(User $user, $password_unhashed) {
        $password_hashed = $user->getPasswordHashed();
        $loggedIn = false;
        if (strLen($password_hashed) == 32 && $password_hashed[0] != '$') {
            // Still md5(sha1($pass)), update
            if (md5(sha1($password_unhashed)) == $password_hashed) {
                // Gets saved later on, when updating the last login
                $user->setPassword($password_unhashed);
                $loggedIn = true;
            }
        }

        // Try to use modern password verification
        if (!$loggedIn) {
            $loggedIn = (crypt($password_unhashed, $password_hashed) === $password_hashed);
        }

        if ($loggedIn) {
            $status = $user->getStatus();
            // Check whether the account is deleted
            if ($status == Authentication::DELETED_STATUS) {
                // Act like the account doesn't exist
                return false;
            }

            // Check whether the account is banned
            if ($status == Authentication::BANNED_STATUS) {
                $oWebsite->addError($oWebsite->tReplaced("users.status.banned.your_account", $this->statusText));
                return false;
            }

            // Update last login date (and possibly password hash, see above) if successfull
            $user->setLastLogin(new DateTime());
            $this->userRepo->save($user);
        }
        return $loggedIn;
    }

    /**
     * Checks whether the user has access to the current page, taking POST
     * parameters into account. If not, a login screen is optionally displayed.
     * @param int $minimumRank The minimum rank required.
     * @param boolean $showform Whether a login form should be shown on failure.
     * @return boolean Whether the login was succesfull.
     */
    public function check($minimumRank, $showform = true) {
        $oWebsite = $this->websiteObject;
        $minimumRank = (int) $minimumRank;
        $currentUser = $this->getCurrentUser();

        if ($minimumRank == self::$LOGGED_OUT_RANK) {
            throw new InvalidArgumentException("Rank for logging in cannot be LOGGED_OUT_RANK");
        }

        // Try to login if data was sent
        $usernameOrEmail = $oWebsite->getRequestString("user");
        $password = $oWebsite->getRequestString("pass");
        if ($usernameOrEmail && $password) {
            if ($this->logIn($usernameOrEmail, $password)) {
                $currentUser = $this->getCurrentUser();
            } else {
                $this->loginFailed = true;
            }
        }

        if ($currentUser !== null && $this->isHigherOrEqualRank($currentUser->getRank(), $minimumRank)) {
            // Logged in with enough rights
            return true;
        } else {
            // Not logged in with enough rights
            if ($showform) {
                $loginView = new LoginView($this->websiteObject, $this->getLoginError($minimumRank));
                echo $loginView->getText();
            }
            return false;
        }
    }

    /**
     * Gets the error message to display on the login form, like "Wrong
     * password" or "You need to be logged in to view this page". The message
     * "Wrong password" can only be returned when {@link check(int, boolean)}
     * has been called before.
     * @return string The error message, or empty if there is no message.
     */
    public function getLoginError($minimumRank) {
        $oWebsite = $this->websiteObject;
        if ($this->hasLoginFailed()) {
            return $oWebsite->t("errors.invalid_login_credentials");
        }
        if ($this->isHigherOrEqualRank($minimumRank, Authentication::$MODERATOR_RANK)) {
            return $oWebsite->t("users.must_be_logged_in_as_administrator");
        }
        return $oWebsite->t("users.must_be_logged_in");
    }

    /**
     * Returns true if the login of the user has failed because the username,
     * password or email was wrong.
     */
    public function hasLoginFailed() {
        return $this->loginFailed;
    }

    /**
     * Logs the current user out. Does nothing if the user is already logged
     * out.
     */
    public function logOut() {
        unset($_SESSION['user_id']);
        unset($_SESSION['moderator']);
        $this->currentUser = null;
        $this->deleteLoginCookie();
    }

    /**
     * Returns all ranks as id=>name pairs.
     * @return array The highest id in use for a rank.
     */
    public function getRanks() {
        $rankIds = array(self::$USER_RANK, self::$MODERATOR_RANK, self::$ADMIN_RANK);
        $ranks = array();
        foreach ($rankIds as $rankId) {
            $ranks[$rankId] = $this->getRankName($rankId);
        }
        return $ranks;
    }

    /**
     * Gets the rank id assigned to new accounts.
     * @return int The rank id.
     */
    public function getDefaultRankForAccounts() {
        return self::$USER_RANK;
    }

    /**
     * Returns true if the given number is a valid rank id for accounts.
     * The LOGGED_OUT rank isn't a valid rank for accounts.
     * @return boolean Whether the rank is valid. 
     */
    public function isValidRankForAccounts($id) {
        if ($id == self::$USER_RANK || $id == self::$ADMIN_RANK || $id == self::$MODERATOR_RANK) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets the translated name of the rank with the given id. When the rank is
     * not found, the translation of users.rank.unknown is returned.
     * @param int $id The rank id.
     * @return string The translated rank name.
     */
    public function getRankName($id) {
        $oWebsite = $this->websiteObject;
        switch ($id) {
            case -1: return $oWebsite->t("users.rank.visitor");
            case 0: return $oWebsite->t("users.rank.moderator");
            case 1: return $oWebsite->t("users.rank.admin");
            case 2: return $oWebsite->t("users.rank.user");
            default: return $oWebsite->t("users.rank.unknown");
        }
    }

    public function isValidStatus($id) {
        if ($id == self::NORMAL_STATUS || $id == self::DELETED_STATUS || $id == self::BANNED_STATUS) {
            return true;
        } else {
            return false;
        }
    }

    public function getStatusName($id) {
        $oWebsite = $this->websiteObject;
        switch ($id) {
            case self::BANNED_STATUS: return $oWebsite->t("users.status.banned");
            case self::DELETED_STATUS: return $oWebsite->t("users.status.deleted");
            case self::NORMAL_STATUS: return $oWebsite->t("users.status.allowed");
            default: return $oWebsite->t("users.status.unknown");
        }
    }

    public function isHigherOrEqualRank($rankId, $compareTo) {
        if ($compareTo == self::$LOGGED_OUT_RANK) {
            // Comparing to logged out user
            return true;
        }
        if ($compareTo == self::$USER_RANK) {
            // Comparing to normal user
            return $rankId != self::$LOGGED_OUT_RANK;
        }
        if ($compareTo == self::$MODERATOR_RANK) {
            // Comparing to moderator
            if ($rankId == self::$USER_RANK || $rankId == self::$LOGGED_OUT_RANK) {
                // Normal and logged out users aren't higher or equal
                return false;
            }
            return true;
        }
        if ($compareTo == self::$ADMIN_RANK) {
            // Only other admins have the same rank
            return $rankId == self::$ADMIN_RANK;
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
        }

        // Get and split the cookie
        $auth_cookie = $_COOKIE[self::AUTHENTIATION_COOKIE];
        $cookie_split = explode('|', $auth_cookie);
        if (count($cookie_split) != 3) {
            // Invalid cookie, not consisting of three parts
            return null;
        }

        try {
            $user = $this->userRepo->getById($cookie_split[0]);
        } catch (NotFoundException $e) {
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
