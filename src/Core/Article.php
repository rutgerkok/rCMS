<?php

namespace Rcms\Core;

use DateTime;
use Rcms\Core\Repository\Entity;

/**
 * Represents a single article. All data is raw HTML, handle with extreme
 * caution (read: htmlSpecialChars)
 */
class Article extends Entity {

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

}
