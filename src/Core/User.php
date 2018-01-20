<?php

namespace Rcms\Core;

use BadMethodCallException;
use DateTimeImmutable;
use InvalidArgumentException;
use Rcms\Core\Repository\Entity;

class User extends Entity {

    const GRAVATAR_URL_BASE = "//www.gravatar.com/avatar/";
    const STATUS_NORMAL = 0;
    const STATUS_DELETED = 2;
    const STATUS_BANNED = 1;
    /**
     * Password used for the admin account when the site is created. The site
     * will complain until the admin no longer uses this password.
     */
    const DEFAULT_ADMIN_PASSWORD = "admin";

    // Various keys used by setExtraData
    const DATA_PASSWORD_RESET_TOKEN = "pwr_token";
    const DATA_PASSWORD_RESET_EXPIRATION = "pwr_expiration";

    protected $username;
    protected $displayName;
    protected $passwordHashed;
    protected $id;
    protected $email;
    protected $rank;
    protected $joined;
    protected $lastLogin;
    protected $status = self::STATUS_NORMAL;
    protected $statusText = "";
    protected $extraData = false;

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
        $user->rank = Ranks::USER;
        $user->extraData = null;

        $now = new DateTimeImmutable();
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
        if ($this->status == self::STATUS_DELETED) {
            return false;
        }
        if ($this->status == self::STATUS_BANNED) {
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
        if (strLen($passwordHashed) === 32 && $passwordHashed[0] !== '$') {
            return md5(sha1($passwordUnhashed)) === $passwordHashed;
        } else {
            return crypt($passwordUnhashed, $passwordHashed) === $passwordHashed;
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
        if ($this->getRank() === Ranks::ADMIN) {
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

    public function passwordNeedsRehash() {
        $passwordHashed = $this->getPasswordHashed();
        if (strLen($passwordHashed) === 32 && $passwordHashed[0] !== '$') {
            return true; // Still md5(sha1($pass))
        }
        return password_needs_rehash($passwordHashed, PASSWORD_DEFAULT);
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
            case Ranks::ADMIN:
                return $this->rank == Ranks::ADMIN;
            case Ranks::MODERATOR:
                return $this->rank == Ranks::MODERATOR
                    || $this->rank == Ranks::ADMIN;
            case Ranks::USER:
            case Ranks::LOGGED_OUT:
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
     * Returns the extra data stored with the given key..
     * @param string $key The extra data.
     * @param string|bool|int|float|array|null Default
     * @return string|bool|int|float|array|null The extra data.
     * @throws BadMethodCallException If the extra data is not loaded.
     */
    public function getExtraData($key, $default) {
        if ($this->extraData === false) {
            throw new BadMethodCallException("Extra data not available");
        }
        if (!isSet($this->extraData[$key])) {
            return $default;
        }
        return $this->extraData[$key];
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
        $this->setPasswordHashed(password_hash($password, PASSWORD_DEFAULT));
    }

    /**
     * Set the hashed password of the user directly.
     * @param string $passwordHashed The hashed password.
     */
    public function setPasswordHashed($passwordHashed) {
        $this->passwordHashed = $passwordHashed;

        // Disallow pending password resets
        $this->setExtraData(self::DATA_PASSWORD_RESET_EXPIRATION, null);
        $this->setExtraData(self::DATA_PASSWORD_RESET_TOKEN, null);
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
     * @param DateTimeImmutable $lastLogin The date.
     */
    public function setLastLogin(DateTimeImmutable $lastLogin = null) {
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
     * Sets extra data on the object. Overwrites existing data.
     * @param string $key Data key. See the DATA_ constants in this class.
     * @param string|int|float|bool|array|null $data The data.
     * @throws BadMethodCallException If the extra data is not loaded.
     * @throws InvalidArgumentException If an argument is of the wrong type.
     */
    public function setExtraData($key, $data) {
        if ($this->extraData === false) {
            throw new BadMethodCallException("Extra data not available");
        }
        if (!is_string($key)) {
            throw new InvalidArgumentException("Key should be a string, was " . getType($key));
        }
        if (is_string($data) || is_array($data) || is_bool($data) || is_array($data) || is_numeric($data)) {
            $this->extraData[$key] = $data;
        } else if ($data === null) {
            unSet($this->extraData[$key]);
            if (empty($this->extraData)) {
                $this->extraData = null;
            }
        } else {
            throw new InvalidArgumentException("Invalid data type: " . getType($data));
        }
    }

}
