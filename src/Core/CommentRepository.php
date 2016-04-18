<?php

namespace Rcms\Core;

use PDOException;
use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\Repository\Entity;
use Rcms\Core\Repository\Field;
use Rcms\Core\Repository\Repository;
use Rcms\Core\Text;

class CommentRepository extends Repository {

    const TABLE_NAME = "comments";

    /* @var $website Website */

    protected $website;
    /* @var $databaseObject \PDO */
    protected $databaseObject;
    /* @var $authenticationObject Authentication */
    protected $authenticationObject;
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
     * Constructs a new comment object.
     * @param Website $website The website.
     * @param Authentication $oAuth Unneeded, provided for backwards compability.
     */
    public function __construct(Website $website, Authentication $oAuth = null) {
        parent::__construct($website->getDatabase());
        $this->databaseObject = $website->getDatabase();
        $this->website = $website;
        $this->authenticationObject = $oAuth;
        if ($this->authenticationObject == null) {
            $this->authenticationObject = $website->getAuth();
        }

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
     * @deprecated Use $this->validateComment and $comment->setBody
     */
    function setBody(Comment $comment, $text) {
        $this->checkCommentBody($text);
        $comment->setBodyRaw($text);
        return $comment;
    }

    /**
     * @deprecated Use $this->validateComment
     */
    function checkCommentBody($comment_body) {
        $website = $this->website;
        $valid = true;

        if (!isSet($comment_body) || strLen(trim($comment_body)) === 0) {
            $website->addError($website->t("comments.comment") . ' ' . $website->t("errors.not_entered"));
            $valid = false;
        } else {
            if ($comment_body != strip_tags($comment_body)) {
                $website->addError($website->t("comments.comment") . ' ' . $website->t("errors.contains_html"));
                $valid = false;
            }
            if (strLen($comment_body) < 10) {
                // WAY too long
                $website->addError($website->t("comments.comment") . ' ' . $website->tReplaced("errors.is_too_short_num", 10));
                $valid = false;
            }

            $comment_body = htmlSpecialChars($comment_body);
            if (strLen($comment_body) > 65565) {
                // WAY too long
                $website->addError($website->t("comments.comment") . ' ' . $website->t("errors.is_too_long"));
                $valid = false;
            }
        }
        return $valid;
    }

    /**
     * @deprecated Use saveComment
     */
    function save(Comment $comment) {
        try {
            $this->saveComment($comment);
            return true;
        } catch (PDOException $ex) {
            return false;
        }
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

    function deleteComment($id) {
        try {
            $this->where($this->primaryField, '=', $id)->deleteOneOrFail();
            return true;
        } catch (PDOException $e) {
            $website->addError($website->t("comments.comment") . ' ' . $website->t("errors.not_found"));
            return false;
        }
    }

    /**
     * @deprecated Use views
     */
    function echoEditor($comment) {
        // $comment can be null
        $website = $this->website;
        if (!isSet($_REQUEST['id']) || ((int) $_REQUEST['id']) == 0) {
            $website->addError($website->t("main.article") . ' ' . $website->t("errors.not_found")); //Artikel niet gevonden
            return false;
        }

        if ($website->isLoggedIn()) {
            $this->echoEditorLoggedIn($comment);
        } else {
            $this->echoEditorNormal($comment);
        }
        return true;
    }

    /**
     * @deprecated Use views
     */
    function echoEditorLoggedIn($comment) {
        $website = $this->website;
        $comment_body = ($comment == null) ? "" : htmlSpecialChars($comment->getBodyRaw());
        echo <<<EOT
            <p>
                <em>{$website->t("main.fields_required")}</em> <!-- velden met een * zijn verplicht -->
            </p>
            <p>	
                <!-- reactie -->
                {$website->t("comments.comment")}<span class="required">*</span>:<br />
                <textarea name="comment" id="comment" rows="10" cols="60" style="width:98%">$comment_body</textarea>
            </p>	
EOT;
    }

    /**
     * @deprecated Use views
     */
    function echoEditorNormal($comment) {
        $website = $this->website;

        if ($comment == null) {
            $name = "";
            $email = "";
            $comment_body = "";
        } else {
            $name = htmlSpecialChars($comment->getUserDisplayName());
            $email = htmlSpecialChars($comment->getUserEmail());
            $comment_body = htmlSpecialChars($comment->getBodyRaw());
        }

        echo <<<EOT
            <p>
                <em>{$website->t("main.fields_required")}</em> <!-- velden met een * zijn verplicht -->
            </p>
            <p>
                <!-- naam -->
                {$website->t("users.name")}<span class="required">*</span>:<br />
                <input type="text" name="name" id="name" maxlength="20" style="width:98%" value="$name" /><br />
            </p>
            <p>
                <!-- email -->
                {$website->t("users.email")}:<br />
                <input type="email" name="email" id="email" style="width:98%" value="$email" /><br />
                <em>{$website->t("comments.email_explained")}</em><br />
            </p>
            <p>	
                <!-- reactie -->
                {$website->t("comments.comment")}<span class="required">*</span>:<br />
                <textarea name="comment" id="comment" rows="10" cols="60" style="width:98%">$comment_body</textarea>
            </p>
EOT;
    }

    /**
     * Gets the comment with the given id.
     * @param int $commentId Id of the comment.
     * @return Comment|null The comment, or null if not found.
     * @deprecated Use getCommentOrFail
     */
    public function getComment($commentId) {
        try {
            return $this->getCommentOrFail($commentId);
        } catch (NotFoundException $e) {
            return null;
        }
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

    /**
     * @deprecated Use $comment->getUserId()
     */
    function getUserId(Comment $comment) {
        return $comment->getUserId();
    }

    /**
     * @deprecated Use $comment->getArticleId()
     */
    function getArticleId(Comment $comment) {
        return $comment->getArticleId();
    }

    /**
     * @deprecated Use $comment->getId()
     */
    function getCommentId(Comment $comment) {
        return $comment->getId();
    }

}
