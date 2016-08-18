<?php

namespace Rcms\Core;

use DateTime;
use InvalidArgumentException;
use Rcms\Core\Repository\Entity;

class User extends Entity {

    const GRAVATAR_URL_BASE = "http://www.gravatar.com/avatar/";

    protected $username;
    protected $displayName;
    protected $passwordHashed;
    protected $id;
    protected $email;
    protected $rank;
    protected $joined;
    protected $lastLogin;
    protected $status = Authentication::STATUS_NORMAL;
    protected $statusText = "";
    protected $extraData = [];

    /**
     * Creates a new user with the given username, display name and password.
     * @param string $username The username.
     * @param string $displayName The display name.
     * @param string $password The password (plaintext).
     * @return User The newly created user. Needs to be saved to a
     * {@link UserRepository}.
     */
    public static function createNewUser($username, $displayName, $password) {
        $user = new User();
        $user->setUsername($username);
        $user->setDisplayName($displayName);
        $user->setPassword($password);
        $user->rank = Authentication::RANK_USER;

        $now = new DateTime();
        $user->setLastLogin($now);
        $user->joined = $now;

        return $user;
    }

    /**
     * Returns whether this user can log in. Returns false if the account has
     * been banned or deleted.
     * @return boolean Whether the user can log in.
     */
    public function canLogIn() {
        if ($this->status == Authentication::STATUS_DELETED) {
            return false;
        }
        if ($this->status == Authentication::STATUS_BANNED) {
            return false;
        }
        return true;
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
     * Checks if the given password would be too weak for the user. Password
     * requirements are a little more strict for admins.
     * @param User $user The user.
     * @param string $password The (plain-text) password.
     * @return boolean True if the password would be too weak.
     */
    public function isWeakPassword($password) {
        if ($this->getRank() === Authentication::RANK_ADMIN) {
            // Admins shouldn't use the default password
            if ($password === "admin") {
                return true;
            }
        }
        if (!Validate::password($password, $password)) {
            // Password wouldn't pass current validation
            return true;
        }
        return false;
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

    /**
     * Gets whether the rank of the user is at least the given rank.
     * @param int $rank The minimum rank.
     * @return boolean True if the user has at least the given rank, false
     * otherwise.
     * @throws InvalidArgumentException When an invalid rank is provided.
     */
    public function hasRank($rank) {
        switch ($rank) {
            case Authentication::RANK_ADMIN:
                return $this->rank == Authentication::RANK_ADMIN;
            case Authentication::RANK_MODERATOR:
                return $this->rank == Authentication::RANK_MODERATOR
                    || $this->rank == Authentication::RANK_ADMIN;
            case Authentication::RANK_USER:
            case Authentication::RANK_LOGGED_OUT:
                return true;
            default:
                throw new InvalidArgumentException("Invalid rank: " . $rank);
        }
    }

    /**
     * Gets the rank of the user.
     * @return int The rank.
     */
    public function getRank() {
        return $this->rank;
    }

    /**
     * Gets the email address of the user.
     * @return string The email address, or an empty string if no email was set.
     */
    public function getEmail() {
        if ($this->email === null) {
            return "";
        }
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
     * Returns the extra data of this user in an associative array. The array
     * is passed by value, so changing it has no effect.
     * @return array The extra data as an array.
     */
    public function getExtraData() {
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
        $this->displayName = trim($display_name);
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
     * @param string $passwordHashed The hashed password.
     */
    public function setPasswordHashed($passwordHashed) {
        $this->passwordHashed = $passwordHashed;
    }

    /**
     * Sets the email of the user. Case senstive. Empty strings are stored as
     * NULL in the database.
     * @param string $email The email.
     */
    public function setEmail($email) {
        if (empty($email)) {
            $this->email = null;
        }
        $this->email = (string) $email;
    }

    /**
     * Change the rank of the user.
     * @param int $rank The new rank.
     */
    public function setRank($rank) {
        $this->rank = (int) $rank;
    }

    /**
     * Sets the date of the last login of the user. When set to 0, the current
     * date will be used.
     * @param DateTime|null $lastLogin The date.
     */
    public function setLastLogin(DateTime $lastLogin = null) {
        $this->lastLogin = $lastLogin;
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
     * @throws InvalidArgumentException If the extra data is not a string, array or null.
     */
    public function setExtraData($extra_data) {
        if ($extra_data === null) {
            $this->extraData = [];
            return;
        }
        if (is_string($extra_data)) {
            if (empty($extra_data)) {
                $this->extraData = [];
                return;
            }
            $this->extraData = JsonHelper::stringToArray($extra_data);
            return;
        }
        if (is_array($extra_data)) {
            $this->extraData = $extra_data;
            return;
        }
        throw new InvalidArgumentException("Expected string, array or null, got " . $extra_data);
    }

}
