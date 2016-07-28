<?php

namespace Rcms\Core;

use PDO;
use PDOException;
use Rcms\Core\NotFoundException;
use Rcms\Core\Repository\Field;
use Rcms\Core\Repository\Repository;

/**
 * Description of UserRepository
 */
class UserRepository extends Repository {

    const TABLE_NAME = "users";

    protected $usernameField;
    protected $displayNameField;
    protected $passwordHashedField;
    protected $idField;
    protected $emailField;
    protected $rankField;
    protected $joinedField;
    protected $lastLoginField;
    protected $statusField;
    protected $statusTextField;
    protected $extraDataField;

    public function __construct(PDO $database = null) {
        parent::__construct($database);

        $this->usernameField = new Field(Field::TYPE_STRING_LOWERCASE, "username", "user_login");
        $this->displayNameField = new Field(Field::TYPE_STRING, "displayName", "user_display_name");
        $this->passwordHashedField = new Field(Field::TYPE_STRING, "passwordHashed", "user_password");
        $this->idField = new Field(Field::TYPE_PRIMARY_KEY, "id", "user_id");
        $this->emailField = new Field(Field::TYPE_STRING, "email", "user_email");
        $this->rankField = new Field(Field::TYPE_INT, "rank", "user_rank");
        $this->joinedField = new Field(Field::TYPE_DATE, "joined", "user_joined");
        $this->lastLoginField = new Field(Field::TYPE_DATE, "lastLogin", "user_last_login");
        $this->statusField = new Field(Field::TYPE_INT, "status", "user_status");
        $this->statusTextField = new Field(Field::TYPE_STRING, "statusText", "user_status_text");
        $this->extraDataField = new Field(Field::TYPE_JSON, "extraData", "user_extra_data");
    }

    public function getTableName() {
        return self::TABLE_NAME;
    }

    public function getStandardFields() {
        return [$this->usernameField, $this->displayNameField,
            $this->idField, $this->emailField,
            $this->rankField, $this->statusField];
    }

    public function getAllFields() {
        $fields = $this->getStandardFields();
        $fields[] = $this->passwordHashedField;
        $fields[] = $this->joinedField;
        $fields[] = $this->lastLoginField;
        $fields[] = $this->statusTextField;
        $fields[] = $this->extraDataField;
        return $fields;
    }

    public function createEmptyObject() {
        return new User();
    }

    public function getPrimaryKey() {
        return $this->idField;
    }

    /**
     * Gets the user with the given id.
     * @param int $id Id of the user.
     * @return User The user.
     * @throws NotFoundException If no user exists with that id.
     */
    public function getById($id) {
        $id = (int) $id;
        if ($id <= 0) {
            throw new NotFoundException;
        }
        return $this->where($this->getPrimaryKey(), '=', $id)
                        ->withExtraFields($this->passwordHashedField, $this->joinedField, $this->lastLoginField, $this->statusTextField, $this->extraDataField)
                        ->selectOneOrFail();
    }

    /**
     * Gets the user with the given username or email
     * @param string $usernameOrEmail Either a username or an email.
     * @return User The user.
     * @throws NotFoundException If no user exists with that username or email.
     */
    public function getByNameOrEmail($usernameOrEmail) {
        if (strPos($usernameOrEmail, '@') === false) {
            return $this->getByName($usernameOrEmail);
        } else {
            return $this->getByEmail($usernameOrEmail);
        }
    }

    /**
     * Gets the user with the given username.
     * @param string $username The username.
     * @return User The user.
     * @throws NotFoundException If no user exists with that username.
     */
    public function getByName($username) {
        return $this->where($this->usernameField, '=', $username)
                        ->withExtraFields($this->passwordHashedField, $this->joinedField, $this->lastLoginField, $this->statusTextField, $this->extraDataField)
                        ->selectOneOrFail();
    }

    /**
     * Gets whether the given username is in use.
     * @param string $username Username to check.
     * @return True if the username is in use, false otherwise.
     */
    public function isUsernameInUse($username) {
        $count = $this->where($this->usernameField, '=', $username)->count();
        return $count > 0;
    }

    /**
     * Gets whether the given email is in use.
     * @param string $email Email to check.
     * @return True if the username is in use, false otherwise.
     */
    public function isEmailInUse($email) {
        $count = $this->where($this->emailField, '=', $email)->count();
        return $count > 0;
    }

    /**
     * Gets the user with the given email.
     * @param string $email The email.
     * @return User The user.
     * @throws NotFoundException If no user exists with that email.
     */
    public function getByEmail($email) {
        return $this->where($this->emailField, '=', $email)
                        ->withExtraFields($this->passwordHashedField, $this->joinedField, $this->lastLoginField, $this->statusTextField, $this->extraDataField)
                        ->selectOneOrFail();
    }

    /**
     * Gets the total amount of users in the database.
     * @return int The total amount of users.
     */
    public function getTotalUserCount() {
        return $this->all()->count();
    }

    /**
     * Gets all registered users.
     * @param int $start The index to start searching.
     * @param int $limit The maximum number of users to find.
     * @return User[] List of users.
     */
    public function getRegisteredUsers($start, $limit) {
        return $this->all()->limit($limit)->offset($start)->select();
    }

    /**
     * Gets the total amount of registered users on the site.
     * @return int The total amount of users.
     */
    public function getRegisteredUsersCount() {
        return $this->all()->count();
    }

    /**
     * Saves the user. If the user has the id 0 it is added to the database. If
     * the id is larger than 0 the existing user is updated in the database.
     * @param User $user The user to update.
     * @throws NotFoundException If the user has an id > 0 and doesn't exist in the database.
     * @throws PDOException If a database error occured.
     */
    public function save(User $user) {
        $this->saveEntity($user);
    }

}
