<?php

namespace Rcms\Core;

use DateTime;
use Rcms\Core\Repository\Entity;
use Rcms\Core\Repository\Field;

class Comment extends Entity {

    const NORMAL_STATUS = 0;
    const DELETED_STATUS = 2;

    // Kept in sync with the constants in Authentication to avoid 
    // confusion when using the wrong constant

    protected $id;
    protected $articleId;
    protected $userId;
    protected $userDisplayName;
    protected $userName;
    protected $userEmail;
    protected $userRank;
    protected $commentName;
    protected $commentEmail;
    protected $created;
    protected $lastEdited;
    protected $body;
    protected $status;

    /** @var Comment[] Child comments */
    private $childComments;

    public function __construct($id = 0) {
        $this->id = (int) $id;
    }

    /**
     * Creates a commment with the given parameters. Make sure that the array
     * has the keys present as field names in the database.
     * @param int $commentId The comment id.
     * @param array $commentArray The comment array.
     * @return Comment The comment.
     */
    public static function getByArray($commentId, $commentArray) {
        $comment = new Comment($commentId);
        $comment->articleId = (int) $commentArray["article_id"];
        $comment->userId = (int) $commentArray["user_id"];
        $comment->userName = isSet($commentArray["user_login"]) ? $commentArray["user_login"] : $commentArray["comment_name"];
        $comment->userDisplayName = isSet($commentArray["user_display_name"]) ? $commentArray["user_display_name"] : $commentArray["comment_name"];
        $comment->userEmail = isSet($commentArray["user_email"]) ? $commentArray["user_email"] : $commentArray["comment_email"];
        $comment->userRank = isSet($commentArray["user_rank"]) ? (int) $commentArray["user_rank"] : Authentication::RANK_LOGGED_OUT;
        $comment->created = isSet($commentArray["comment_created"]) ? new DateTime($commentArray["comment_created"]) : new DateTime();
        $comment->lastEdited = isSet($commentArray["comment_last_edited"]) ? new DateTime($commentArray["comment_last_edited"]) : null;
        $comment->body = $commentArray["comment_body"];
        $comment->status = (int) $commentArray["comment_status"];
        return $comment;
    }

    public function getId() {
        return $this->id;
    }

    public function getArticleId() {
        return $this->articleId;
    }

    public function getUserId() {
        return isSet($this->userId) ? $this->userId : 0;
    }

    /** Returns username. Empty for comments without an user id */
    public function getUserName() {
        return isSet($this->userName) ? $this->userName : $this->commentName;
    }

    public function getUserDisplayName() {
        return isSet($this->userDisplayName) ? $this->userDisplayName : $this->commentName;
    }

    public function getUserEmail() {
        return isSet($this->userEmail) ? $this->userEmail : $this->commentEmail;
    }

    public function getUserRank() {
        return isSet($this->userRank) ? $this->userRank : Authentication::RANK_LOGGED_OUT;
    }

    /**
     * Gets the time the comment was created.
     * @return DateTime The time.
     */
    public function getDateCreated() {
        return $this->created;
    }

    /**
     * Gets the time the comment was last edited.
     * @return DateTime|null The time, or null if not edited.
     */
    public function getDateLastEdited() {
        return $this->lastEdited;
    }

    public function getBodyRaw() {
        return $this->body; // Without <br /> tags
    }

    public function getStatus() {
        return $this->status;
    }

    public function setUser(User $user) {
        $this->userId = $user->getId();
        $this->userName = $user->getUsername();
        $this->userDisplayName = $user->getDisplayName();
        $this->userEmail = $user->getEmail();
        $this->userRank = $user->getRank();

        // Update last edited date
        $this->lastEdited = new DateTime();
    }

    public function setBodyRaw($text) {
        $this->body = $text;

        // Update last edited date
        $this->lastEdited = new DateTime();
    }

    /**
     * Gets all child comments, which have been set previously by the
     * setChildComments method. Returns an empty array if there are no child
     * comments or if the child comments have not been set.
     * @return type
     */
    public function getChildComments() {
        if (!isSet($this->childComments)) {
            return array();
        }
        return $this->childComments;
    }

    /**
     * Sets all child comments for viewing purpose. The parent_id of the child
     * comments is ignored. Make sure that there are no cyclical relations.
     * @param Comment[] $childComments Array of child comments.
     */
    public function setChildComments($childComments) {
        $this->childComments = $childComments;
    }

}
