<?php

namespace Rcms\Core;

use DateTime;
use Rcms\Core\Repository\Entity;

class Comment extends Entity {

    // Kept in sync with the constants in Authentication to avoid 
    // confusion when using the wrong constant
    const NORMAL_STATUS = 0;
    const DELETED_STATUS = 2;
    
    const BODY_MIN_LENGTH = 10;
    const BODY_MAX_LENGTH = 65565;

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
        $this->status = self::NORMAL_STATUS;
    }

    /**
     * Creates a new comment for the given user. This method will succeed even
     * if the given article doesn't allow comments.
     * @param User $user The author of the comment.
     * @param Article $article The article that is commented on.
     * @param string $text The comment of the user.
     * @return Comment The comment.
     */
    public static function createForUser(User $user, Article $article, $text) {
        $comment = new Comment(0);
        $comment->articleId = $article->getId();
        $comment->userId = $user->getId();
        $comment->userName = $user->getUsername();
        $comment->userDisplayName = $user->getDisplayName();
        $comment->userEmail = $user->getEmail();
        $comment->userRank = $user->getRank();
        $comment->created = new DateTime();
        $comment->body = (string) $text;
        return $comment;
    }

    /**
     * Creates a new comment for a visitor.
     * @param string $displayName Name of the visitor.
     * @param string $email E-mail of the visitor, may be empty.
     * @param Article $article The article that is commented on.
     * @param string $text The text of the comment.
     * @return Comment The comment.
     */
    public static function createForVisitor($displayName, $email, Article $article, $text) {
        $comment = new Comment(0);
        $comment->articleId = $article->getId();
        $comment->commentName = (string) $displayName;
        $comment->commentEmail = (string) $email;
        $comment->body = (string) $text;
        $comment->created = new DateTime();
        return $comment;
    }

    public function getId() {
        return $this->id;
    }

    public function getArticleId() {
        return $this->articleId;
    }

    /**
     * Gets whether this comment was made by a visitor. If yes, there is no
     * account associated to this comment.
     * @return boolean Whether this comment was made by a visitor.
     */
    public function isByVisitor() {
        return $this->getUserId() === 0;
    }

    /**
     * Gets the account id of the commenter, or 0 if the comment was made by a
     * visitor.
     * @return int The account id.
     */
    public function getUserId() {
        return isSet($this->userId) ? (int) $this->userId : 0;
    }

    /**
     * Gets the username of commenter. For comments created by a visitor, this is
     * always equal to the display name.
     * @return string The username.
     */
    public function getUsername() {
        return isSet($this->userName) ? $this->userName : $this->commentName;
    }

    /**
     * Gets the display name of the commenter.
     * @return string The display name.
     */
    public function getUserDisplayName() {
        return isSet($this->userDisplayName) ? $this->userDisplayName : $this->commentName;
    }

    /**
     * Gets the e-mail of the commenter, may be empty.
     * @return string The email of the commenter.
     */
    public function getUserEmail() {
        return isSet($this->userEmail) ? $this->userEmail : $this->commentEmail;
    }

    /**
     * Gets the rank of the commenter, or the logged out rank for visitors.
     * @return int The rank.
     */
    public function getUserRank() {
        return isSet($this->userRank) ? $this->userRank : Ranks::LOGGED_OUT;
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

    public function getUrl(Text $text) {
        return $text->getUrlPage("article", $this->getArticleId())
                ->withFragment("comment_" . $this->getId());
    }

    /**
     * Sets the comment contents to the given string.
     * @param string $text The contents of the comment.
     */
    public function setBodyRaw($text) {
        $this->body = $text;

        $this->markEdited();
    }

    /**
     * Sets the comment as created by the given visitor.
     * @param string $name Name of the visitor.
     * @param string $email E-mail address of the visitor.
     */
    public function setByVisitor($name, $email) {
        $this->userDisplayName = null;
        $this->userId = 0;
        $this->userRank = null;
        $this->userName = null;
        
        $this->commentName = (string) $name;
        $this->commentEmail = (string) $email;
        
        $this->markEdited();
    }

    /**
     * Gets all child comments, which have been set previously by the
     * setChildComments method. Returns an empty array if there are no child
     * comments or if the child comments have not been set.
     * @return type
     */
    public function getChildComments() {
        if (!isSet($this->childComments)) {
            return [];
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

    /**
     * Updates the date and time of when this comment was last edited.
     */
    private function markEdited() {
        if ($this->id !== 0) {
            $this->lastEdited = new DateTime();
        }
    }

}
