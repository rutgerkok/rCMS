<?php

namespace Rcms\Core;

use DateTime;

use Rcms\Core\Repository\Entity;

/**
 * Represents a single article. All data is raw HTML, handle with extreme
 * caution (read: htmlSpecialChars)
 */
class Article extends Entity {
    
    const MAX_TITLE_LENGTH = 100;
    const MIN_TITLE_LENGTH = 2;
    const MAX_INTRO_LENGTH = 325;
    const MIN_INTRO_LENGTH = 2;
    const MAX_BODY_LENGTH = 65535;
    const MIN_BODY_LENGTH = 9;
    const MAX_FEATURED_IMAGE_URL_LENGTH = 150;

    protected $id = 0;
    protected $title = "";

    /** @var DateTime Article create date/time */
    public $created;

    /** @var DateTime|null Article edit date/time */
    protected $lastEdited;
    protected $intro = "";
    public $featuredImage = "";
    public $category = "";
    public $categoryId = 0;
    public $author = "";
    public $authorId = 0;
    public $pinned = false;
    protected $hidden = false;
    protected $body = "";
    public $showComments = false;

    /** @var DateTime|null Date for calendar. */
    public $onCalendar;

    /**
     * Creates a new article.
     * @param User $author Author of the article.
     * @return Article The article.
     */
    public static function createArticle(User $author) {
        $article = new Article();
        $article->setAuthor($author);
        return $article;
    }

    /**
     * Creates a new, empty article.
     */
    public function __construct() {
        $this->created = new DateTime();
    }

    /**
     * Gets the article id.
     * @return int The article id.
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Gets the title of this article.
     * @return string The title.
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Sets the title of this article.
     * @param string $title The title.
     */
    public function setTitle($title) {
        $this->title = (string) $title;

        $this->updateLastEdited();
    }

    /**
     * Gets the intro of the article, displayed in article listings.
     * @return string The intro.
     */
    public function getIntro() {
        return $this->intro;
    }

    /**
     * Sets the intro of the article.
     * @param string $intro The new intro.
     */
    public function setIntro($intro) {
        $this->intro = (string) $intro;

        $this->updateLastEdited();
    }

    /**
     * Gets the text of the article.
     * @return string The text.
     */
    public function getBody() {
        return $this->body;
    }

    /**
     * Sets the text of the article.
     * @param string $body The new text.
     */
    public function setBody($body) {
        $this->body = (string) $body;

        $this->updateLastEdited();
    }

    /**
     * Gets the date the article was created.
     * @return DateTime The date.
     */
    public function getDateCreated() {
        return $this->created;
    }

    /**
     * Gets the date of the latest article edit.
     * @return DateTime|null The date, or null if not edited yet.
     */
    public function getDateLastEdited() {
        return $this->lastEdited;
    }

    /**
     * Sets the author of this article to the given user.
     * @param User $user The user.
     */
    public function setAuthor(User $user) {
        $this->author = $user->getDisplayName();
        $this->authorId = $user->getId();

        $this->updateLastEdited();
    }

    /**
     * Gets whether this article is hidden for normal users.
     * @return boolean True if this article is hidden, false otherwise.
     */
    public function isHidden() {
        return $this->hidden;
    }

    /**
     * Sets whether the article is hidden.
     * @param boolean $hidden True if the article is hidden, false otherwise.
     */
    public function setHidden($hidden) {
        $this->hidden = (boolean) $hidden;
    }

    /**
     * Updates the "last edited" field for existing articles.
     */
    private function updateLastEdited() {
        if ($this->id > 0) {
            $this->lastEdited = new DateTime();
        }
    }

    /**
     * Gets whether this is a complete article: all parts have been filled out
     * and are of the appropriate length.
     * @return boolean True if this article is complete, false otherwise.
     */
    public function isComplete() {
        return strLen($this->title) >= Article::MIN_TITLE_LENGTH
                && strLen($this->title) <= Article::MAX_TITLE_LENGTH
                && strLen($this->body) >= Article::MIN_BODY_LENGTH
                && strLen($this->body) <= Article::MAX_BODY_LENGTH
                && strLen($this->intro) >= Article::MIN_INTRO_LENGTH
                && strLen($this->intro) <= Article::MAX_INTRO_LENGTH
                && strLen($this->featuredImage) <= Article::MAX_FEATURED_IMAGE_URL_LENGTH
                && $this->authorId > 0;
    }

}
