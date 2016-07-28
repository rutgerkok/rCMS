<?php

namespace Rcms\Core;

use PDO;
use PDOException;
use Rcms\Core\Repository\Entity;
use Rcms\Core\Repository\Field;
use Rcms\Core\Repository\Repository;

class CommentRepository extends Repository {

    const TABLE_NAME = "comments";

    protected $primaryField;
    protected $articleIdField;
    protected $userIdField;
    protected $userDisplayNameField;
    protected $userNameField;
    protected $userEmailField;
    protected $userRankField;
    protected $commentUserNameField;
    protected $commentEmailField;
    protected $createdField;
    protected $lastEditedField;
    protected $bodyField;
    protected $statusField;

    /**
     * Constructs a new comment repository.
     * @param PDO The database to retrieve comments from, and save comments to.
     */
    public function __construct(PDO $database) {
        parent::__construct($database);

        $this->primaryField = new Field(Field::TYPE_PRIMARY_KEY, "id", "comment_id");
        $this->articleIdField = new Field(Field::TYPE_INT, "articleId", "article_id");
        $this->userIdField = new Field(Field::TYPE_INT, "userId", "user_id");
        $this->userDisplayNameField = new Field(Field::TYPE_STRING, "userDisplayName", "user_display_name");
        $this->userDisplayNameField->createLink(UserRepository::TABLE_NAME, $this->userIdField);
        $this->userNameField = new Field(Field::TYPE_STRING, "userName", "user_login");
        $this->userNameField->createLink(UserRepository::TABLE_NAME, $this->userIdField);
        $this->userEmailField = new Field(Field::TYPE_STRING, "userEmail", "user_email");
        $this->userEmailField->createLink(UserRepository::TABLE_NAME, $this->userIdField);
        $this->userRankField = new Field(Field::TYPE_STRING, "userName", "user_rank");
        $this->userRankField->createLink(UserRepository::TABLE_NAME, $this->userIdField);
        $this->commentUserNameField = new Field(Field::TYPE_STRING, "commentName", "comment_name");
        $this->commentEmailField = new Field(Field::TYPE_STRING, "commentEmail", "comment_email");
        $this->createdField = new Field(Field::TYPE_DATE, "created", "comment_created");
        $this->lastEditedField = new Field(Field::TYPE_DATE, "lastEdited", "comment_last_edited");
        $this->bodyField = new Field(Field::TYPE_STRING, "body", "comment_body");
        $this->statusField = new Field(Field::TYPE_INT, "status", "comment_status");
    }

    public function getTableName() {
        return self::TABLE_NAME;
    }

    public function getPrimaryKey() {
        return $this->primaryField;
    }

    public function getAllFields() {
        return [$this->primaryField, $this->articleIdField,
            $this->userIdField, $this->userDisplayNameField,
            $this->userNameField, $this->userEmailField, $this->userRankField,
            $this->commentUserNameField, $this->commentEmailField,
            $this->createdField, $this->lastEditedField, $this->bodyField,
            $this->statusField];
    }

    public function createEmptyObject() {
        return new Comment();
    }

    /**
     * Validates a comment for saving to the database.
     * @param Comment $comment The comment.
     * @param Text $text Errors go here.
     * @return boolean True if the comment is valid, false otherwise.
     */
    public function validateComment(Comment $comment, Text $text) {
        $valid = true;
        if (!Validate::stringLength($comment->getBodyRaw(), Comment::BODY_MIN_LENGTH, Comment::BODY_MAX_LENGTH)) {
            $text->addError($text->t("comments.comment") . " " . Validate::getLastError($text));
            $valid = false;
        }

        if ($comment->isByVisitor()) {
            if (!Validate::email($comment->getUserEmail())) {
                $text->addError($text->t("users.email") . " " . Validate::getLastError($text));
                $valid = false;
            }

            if (!Validate::displayName($comment->getUserDisplayName())) {
                $text->addError($text->t("users.name") . " " . Validate::getLastError($text));
                $valid = false;
            }
        }

        return $valid;
    }

    /**
     * Saves a comment to the databse.
     * @param Comment $comment The comment to save.
     * @throws PDOException If saving fails.
     */
    public function saveComment(Comment $comment) {
        $this->saveEntity($comment);
    }

    protected function canBeSaved(Entity $comment) {
        if (!($comment instanceof Comment)) {
            return false;
        }
        return parent::canBeSaved($comment)
                && strLen($comment->getBodyRaw()) >= Comment::BODY_MIN_LENGTH
                && strLen($comment->getBodyRaw()) <= Comment::BODY_MAX_LENGTH;
    }

    /**
     * Deletes the comment with the given id.
     * @param int $id Id of the comment.
     * @throws PDOException If deleting the comment failed.
     */
    public function deleteComment($id) {
        $this->where($this->primaryField, '=', $id)->deleteOneOrFail();
    }

    /**
     * Gets the comment with the given id.
     * @param int $commentId Id of the comment.
     * @return Comment The comment.
     * @throws NotFoundException If no comment with that id exists.
     */
    public function getCommentOrFail($commentId) {
        return $this->where($this->primaryField, '=', $commentId)->selectOneOrFail(); 
    }

    /**
     * Gets all comments for an article.
     * @param int $articleId The article of the comments.
     * @return Comment[] The comments.
     */
    public function getCommentsArticle($articleId) {
        return $this->where($this->articleIdField, '=', $articleId)->orderDescending($this->primaryField)->select();
    }

    /**
     * Gets the latest comments on the site.
     * @param int $amount The amount of comments to fetch.
     * @return Comment[] The comments.
     */
    public function getCommentsLatest($amount = 20) {
        return $this->all()->orderDescending($this->primaryField)->limit($amount)->select();
    }

    /**
     * Gets the latest 10 comments of the given user.
     * @param int $userId The id of the user.
     * @return array The comments.
     */
    public function getCommentsUser($userId) {
        return $this->where($this->userIdField, '=', $userId)->limit(10)->select();
    }

}
