<?php

namespace Rcms\Core\Document;

use DateTime;

use Rcms\Core\Repository\Entity;
use Rcms\Core\Text;

/**
 * Documents are more-or-less static pages on a site. They can be customized
 * using widgets.
 */
class Document extends Entity {
    /**
     * @var int The id of the document.
     */
    protected $id;
    /**
     * @var string The title of the document.
     */
    protected $title;
    /**
     * @var string The intro of the document.
     */
    protected $intro;
    /**
     * @var boolean Whether the document is hidden for normal users.
     */
    protected $hidden;
    /**
     *
     * @var DateTime The moment the document was created.
     */
    protected $created;
    /**
     * @var DateTime|null The moment the document was last edited.
     */
    protected $edited;
    /**
     * @var int Id of the user that created this document.
     */
    protected $userId;
    /**
     * @var int Id of the parent document.
     */
    protected $parentId;

    /**
     * Gets the title of the document.
     * @return string The title.
     */
    public function getTitle() {
        return $this->title;
    }

    /**
     * Gets the intro of this document.
     * @return string The intro.
     */
    public function getIntro() {
        return $this->intro;
    }

    /**
     * Gets the (html escaped) URL for this document.
     * @param Text $text The text object, for URL structure.
     * @return string The URL.
     */
    public function getUrl(Text $text) {
        return $text->getUrlPage("document", $this->id);
    }
}
