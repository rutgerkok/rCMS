<?php

namespace Rcms\Core;

use DateTime;

class Authentication {

    const RANK_LOGGED_OUT = -1;
    const RANK_ADMIN = 1;
    const RANK_MODERATOR = 0;
    const RANK_USER = 2;

    const STATUS_NORMAL = 0;
    const STATUS_BANNED = 1;
    const STATUS_DELETED = 2;

    const AUTHENTIATION_COOKIE = "remember_me";

    /**
     * Password used for the admin account when the site is created. The site
     * will complain until the admin no longer uses this password.
     */
    const DEFAULT_ADMIN_PASSWORD = "admin";

    /**
     * @var Request The request object.
     */
    protected $request;

    /**
     * @var UserRepository|null The user repository, or null when the website is
     * not connected to a database.
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
     * @param Website $website The website object.
     * @param UserRepository $userRepo The user repository, or null if the
     * website is not connected to a database (happens when the website is not
     * installed yet).
     */
    public function __construct(Request $request,
            UserRepository $userRepo = null) {
        $this->request = $request;
        $this->userRepo = $userRepo;

        // Check session and cookie
        if (isSet($_SESSION["user_id"])) {
            if (!$this->setCurrentUserFromId($_SESSION["user_id"])) {
                // Invalid session variable
                $this->logOut();
            }
        } else {
            // Try to log in with cookie
            $user = $this->getUserFromCookie();
            if ($user != null && $this->setCurrentUser($user)) {
                // Log in and refresh cookie
                $this->setLoginCookie();
            } else {
                // Cookie is corrupted/account is deleted
                $this->deleteLoginCookie();
            }
        }
    }

    /**
     * Gets the user repository being used for this authentication object.
     * @return UserRepository The user repository.
     * @throws NotFoundException If the website is not connected to a database
     * (for example when the site is being installed).
     */
    public function getUserRepository() {
        if ($this->userRepo == null) {
            throw new NotFoundException();
        }
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
            $user = $this->getUserRepository()->getById($userId);
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
        if ($user->hasRank(self::RANK_MODERATOR)) {
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
     * @param Text $text For printing errors.
     * @param string $usernameOrEmail The username.
     * @param string $password The unhashed password.
     * @return boolean Whether the login was succesfull
     */
    public function logIn(Text $text, $usernameOrEmail, $password) {
        try {
            $user = $this->getUserRepository()->getByNameOrEmail($usernameOrEmail);

            if ($this->loginCheck($text, $user, $password)) {
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
     * @param Text $text For error messages.
     * @param User $user The user.
     * @param string $password_unhashed The password entered by the user.
     */
    protected function loginCheck(Text $text, User $user, $password_unhashed) {
        if ($this->userRepo == null) {
            // Unable to log in when userRepo is not present
            return false;
        }

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
            if ($status == Authentication::STATUS_DELETED) {
                // Act like the account doesn't exist
                return false;
            }

            // Check whether the account is banned
            if ($status == Authentication::STATUS_BANNED) {
                $text->addError($text->tReplaced("users.status.banned.your_account", $user->getStatusText()));
                return false;
            }

            // Check password strength
            if ($user->isWeakPassword($password_unhashed)) {
                $text->addError($text->t("users.your_password_is_insecure"), Link::of(
                                $text->getUrlPage("edit_password"), $text->t("users.password.edit")));
            }

            // Update last login date (and possibly password hash, see above) if successfull
            $user->setLastLogin(new DateTime());
            $this->userRepo->save($user);
        }
        return $loggedIn;
    }

    /**
     * Checks whether the user has access to the current page. If not, the
     * request object is scanned for username/password, and if found, the user
     * is logged in and the check is repeated.
     * @param Text $text The text object.
     * @param int $minimumRank The minimum rank required.
     * @return boolean Whether the login was succesfull.
     */
    public function check(Text $text, $minimumRank) {
        $minimumRank = (int) $minimumRank;
        $currentUser = $this->getCurrentUser();

        if ($minimumRank == self::RANK_LOGGED_OUT) {
            throw new InvalidArgumentException("Rank for logging in cannot be LOGGED_OUT_RANK");
        }

        // Try to login if data was sent
        $usernameOrEmail = $this->request->getRequestString("user", "");
        $password = $this->request->getRequestString("pass", "");
        if ($usernameOrEmail && $password) {
            if ($this->logIn($text, $usernameOrEmail, $password)) {
                $currentUser = $this->getCurrentUser();
            } else {
                $this->loginFailed = true;
            }
        }

        if ($currentUser !== null && $currentUser->hasRank($minimumRank)) {
            // Logged in with enough rights
            return true;
        } else {
            // Not logged in with enough rights
            return false;
        }
    }

    /**
     * Gets the error message to display on the login form, like "Wrong
     * password" or "You need to be logged in to view this page". The message
     * "Wrong password" can only be returned when {@link check(int, boolean)}
     * has been called before.
     * @param Text $text The text object, for translations.
     * @param int $minimumRank The minimum rank. Used to customize the message.
     * @return string The error message, or empty if there is no message.
     */
    public function getLoginError(Text $text, $minimumRank) {
        if ($this->hasLoginFailed()) {
            return $text->t("errors.invalid_login_credentials");
        }
        if ($minimumRank == self::RANK_MODERATOR || $minimumRank == self::RANK_ADMIN) {
            return $text->t("users.must_be_logged_in_as_administrator");
        }
        return $text->t("users.must_be_logged_in");
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
        $rankIds = [self::RANK_USER, self::RANK_MODERATOR, self::RANK_ADMIN];
        $ranks = [];
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
        return self::RANK_USER;
    }

    /**
     * Returns true if the given number is a valid rank id for accounts.
     * The LOGGED_OUT rank isn't a valid rank for accounts.
     * @return boolean Whether the rank is valid.
     */
    public function isValidRankForAccounts($id) {
        if ($id == self::RANK_USER || $id == self::RANK_ADMIN || $id == self::RANK_MODERATOR) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Gets the translation string for the rank with the given id. When the rank
     * is not found, the translation of users.rank.unknown is returned.
     * @param int $id The rank id.
     * @return string The translation id for the rank name.
     */
    public function getRankName($id) {
        switch ($id) {
            case -1: return "users.rank.visitor";
            case 0: return "users.rank.moderator";
            case 1: return "users.rank.admin";
            case 2: return "users.rank.user";
            default: return "users.rank.unknown";
        }
    }

    public function isValidStatus($id) {
        if ($id == self::STATUS_NORMAL || $id == self::STATUS_DELETED || $id == self::STATUS_BANNED) {
            return true;
        } else {
            return false;
        }
    }

    public function getStatusName(Text $text, $id) {
        switch ($id) {
            case self::STATUS_BANNED: return $text->t("users.status.banned");
            case self::STATUS_DELETED: return $text->t("users.status.deleted");
            case self::STATUS_NORMAL: return $text->t("users.status.allowed");
            default: return $text->t("users.status.unknown");
        }
    }

    /**
     * Gets the account that is stored in the "remember me"-cookie. Returns null
     * if the cookie is invalid.
     * @return null|User The user, or null if the cookie is invalid.
     */
    public function getUserFromCookie() {
        $cookies = $this->request->toPsr()->getCookieParams();
        if (!isSet($cookies[self::AUTHENTIATION_COOKIE])) {
            return null;
        }

        // Get and split the cookie
        $auth_cookie = $cookies[self::AUTHENTIATION_COOKIE];
        $cookie_split = explode('|', $auth_cookie);
        if (count($cookie_split) != 3) {
            // Invalid cookie, not consisting of three parts
            return null;
        }

        try {
            $user = $this->getUserRepository()->getById($cookie_split[0]);
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
        $this->request->toPsr()->getCookieParams();
        if (headers_sent()) {
            return false;
        }
        setcookie(self::AUTHENTIATION_COOKIE, "", time() - 1, '/');
        return true;
    }

}
