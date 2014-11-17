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

    public $id = 0;
    public $title = "";

    /** @var DateTime Article create date/time */
    public $created;

    /** @var DateTime|null Article edit date/time */
    public $lastEdited;
    public $intro = "";
    public $featuredImage = "";
    public $category = "";
    public $categoryId = 0;
    public $author = "";
    public $authorId = 0;
    public $pinned = false;
    public $hidden = false;
    public $body = "";
    public $showComments = false;

    /** @var DateTime|null Date for calendar. */
    public $onCalendar;

    /**
     * Creates a new, empty article.
     */
    public function __construct() {
        $this->created = new DateTime();
    }

    /**
     * Sets the author of this article to the given user.
     * @param User $user The user.
     */
    public function setAuthor(User $user) {
        $this->author = $user->getDisplayName();
        $this->authorId = $user->getId();
    }

}
