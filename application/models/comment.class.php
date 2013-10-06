<?php

class Comment {

    const NORMAL_STATUS = 0;
    const DELETED_STATUS = 2;

    // Kept in sync with the constants in Authentication to avoid 
    // confusion when using the wrong constant

    private $id;
    private $articleId;
    private $userId;
    private $userName;
    private $userDisplayName;
    private $userEmail;
    private $userRank;
    private $created;
    private $lastEdited;
    private $body;
    private $status;

    /** @var Comment[] Child comments */
    private $childComments;

    public function __construct($commentId, $articleId, $userId, $userName, $userDisplayName, $userEmail, $userRank, $created, $lastEdited, $body, $status) {
        $this->id = (int) $commentId;
        $this->articleId = (int) $articleId;
        $this->userId = (int) $userId;
        $this->userName = $userName;
        $this->userDisplayName = $userDisplayName;
        $this->userEmail = $userEmail;
        $this->userRank = (int) $userRank;
        $this->created = $created;
        $this->lastEdited = $lastEdited;
        $this->body = $body;
        $this->status = (int) $status;

        // When no date/time is provided, take the current date/time
        if ($created == 0) {
            $this->created = time();
        }
        if ($lastEdited == 0) {
            $this->lastEdited = time();
        }
    }

    /**
     * Gets the comment with the given id.
     * @param Database $oDatabase Database to fetch from.
     * @param int $commentId Id of the comment.
     * @return Comment|null The comment, or null if not found.
     */
    public static function getById(Database $oDatabase, $commentId) {
        $commentId = (int) $commentId;
        $sql = <<<SQL
SELECT `article_id`, `user_id`, `user_display_name`, 
`user_login`, `user_email`, `user_rank`, `comment_name`, `comment_email`, 
`comment_created`, `comment_last_edited`, `comment_body`, `comment_status` 
FROM `comments` LEFT JOIN `users` USING(`user_id`) WHERE `comment_id` = '{$commentId}'
SQL;
        $result = $oDatabase->singleRowQuery($sql);

        if (!$result) {
            return null;
        }

        // Create user object
        return self::getByArray($commentId, $result);
    }

    /**
     * Creates a commment with the given parameters. Make sure that the array
     * has the keys present as field names in the database.
     * @param int $commentId The comment id.
     * @param array $commentArray The comment array.
     * @return Comment The comment.
     */
    public static function getByArray($commentId, $commentArray) {
        $commentId = (int) $commentId;
        $articleId = (int) $commentArray["article_id"];
        $userId = (int) $commentArray["user_id"];
        $userName = $commentArray["user_login"];
        $userDisplayName = $commentArray["user_display_name"] ? $commentArray["user_display_name"] : $commentArray["comment_name"];
        $userEmail = $commentArray["user_email"] ? $commentArray["user_email"] : $commentArray["comment_email"];
        $userRank = ($commentArray["user_rank"] == "") ? Authentication::$LOGGED_OUT_RANK : (int) $commentArray["user_rank"];
        $created = (int) strToTime($commentArray["comment_created"]);
        $lastEdited = (int) ($commentArray["comment_last_edited"] ? strToTime($commentArray["comment_last_edited"]) : 0);
        $body = $commentArray["comment_body"];
        $status = (int) $commentArray["comment_status"];
        return new Comment($commentId, $articleId, $userId, $userName, $userDisplayName, $userEmail, $userRank, $created, $lastEdited, $body, $status);
    }

    public function getId() {
        return $this->id;
    }

    public function getArticleId() {
        return $this->articleId;
    }

    /** Fetches the article from the database. Returns null for deleted articles. */
    public function getArticle(Database $oDatabase) {
        try {
            return new Article($this->articleId, $oDatabase);
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }

    public function getUserId() {
        return $this->userId;
    }

    /** Returns username. Empty for comments without an user id */
    public function getUserName() {
        return $this->userName;
    }

    public function getUserDisplayName() {
        return $this->userDisplayName;
    }

    public function getUserEmail() {
        return $this->userEmail;
    }

    public function getUserRank() {
        return $this->userRank;
    }

    public function getDateCreated() {
        return $this->created;
    }

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
        $this->lastEdited = time();
    }

    public function setBodyRaw($text) {
        $this->body = $text;

        // Update last edited date
        $this->lastEdited = time();
    }

    /**
     * Saves the comment to the database. When updating a comment, only the
     * body, last edited date and author info are updated.
     * @param Database $oDatabase The database to save to.
     * @return boolean Whether the update succeeded.
     */
    public function save(Database $oDatabase) {
        $accountLinked = ($this->userId != 0);
        $commentId = (int) $this->id;
        $articleId = (int) $this->articleId;
        $body = $oDatabase->escapeData($this->body);
        $userId = $accountLinked ? (int) $this->userId : "NULL";
        // $q is to indicate that the quotes are already included for the query
        $qUserName = $accountLinked ? "NULL" : '"' . $oDatabase->escapeData($this->userDisplayName) . '"';
        $qUserEmail = $accountLinked ? "NULL" : '"' . $oDatabase->escapeData($this->userEmail) . '"';
        $status = (int) $this->status;

        if ($this->id == 0) {
            // Build INSERT query
            $commentCreated = date("Y-m-d H:i:s", $this->created);
            $sql = <<<SQL
INSERT INTO `comments` (`article_id`, `user_id`, `comment_name`, `comment_email`, `comment_created`, `comment_body`, `comment_status`)
VALUES ($articleId, $userId, $qUserName, $qUserEmail, "$commentCreated", "$body", $status)      
SQL;

            // Save to database
            if ($oDatabase->query($sql)) {
                $this->id = $oDatabase->getLastInsertedId();
                return true;
            }
            return false;
        } else {
            // Build UPDATE query
            $commentUpdated = date("Y-m-d H:i:s", $this->lastEdited);
            $sql = <<<SQL
UPDATE `comments` SET 
    `user_id` = $userId,
    `comment_name` = $qUserName,
    `comment_email` = $qUserEmail,
    `comment_body` = "$body",
    `comment_last_edited` = "$commentUpdated"
WHERE `comment_id` = $commentId
SQL;
            // Save to databasse
            return $oDatabase->query($sql) != false;
        }
    }

    /**
     * Gets all child comments, which have been set previously by the
     * setChildComments method. Returns an empty array if there are no child
     * comments or if the child comments have not been set.
     * @return type
     */
    public function getChildComments() {
        if (!$this->childComments) {
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

