<?php

namespace Rcms\Core\Document;

use DateTime;
use InvalidArgumentException;
use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\Repository\Entity;
use Rcms\Core\Text;
use Rcms\Core\User;
use Rcms\Core\Website;

/**
 * Documents are more-or-less static pages on a site. They can be customized
 * using widgets.
 */
final class Document extends Entity {

    const TITLE_MIN_LENGTH = 1;
    const TITLE_MAX_LENGTH = 255;
    const INTRO_MIN_LENGTH = 0; // empty intro is allowed
    const INTRO_MAX_LENGTH = 65535;

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
    protected $hidden = false;

    /**
     * @var DateTime The moment the document was created.
     */
    protected $created;

    /**
     * @var DateTime|null The moment the document was last edited.
     */
    protected $edited = null;

    /**
     * @var int Id of the user that created this document.
     */
    protected $userId;

    /**
     * @var int Id of the parent document.
     */
    protected $parentId = 0;
    
    /**
     * @var boolean Whether this document is a widget area. See the method
     * isForWidgetArea() for more information.
     */
    private $isWidgetArea = false;

    /**
     * Gets whether the given string would be a valid document title.
     * @param string $title The document title.
     * @return boolean True if the given title would be a valid document title,
     * false otherwise.
     */
    public static function isValidTitle($title) {
        return is_string($title)
                && strLen($title) >= self::TITLE_MIN_LENGTH
                && strLen($title) <= self::TITLE_MAX_LENGTH;
    }

    /**
     * Gets whether the given string would be a valid document intro.
     * @param string $intro The document intro.
     * @return boolean True if the given intro would be a valid document intro,
     * false otherwise.
     */
    public static function isValidIntro($intro) {
        return is_string($intro)
                && strLen($intro) >= self::INTRO_MIN_LENGTH
                && strLen($intro) <= self::INTRO_MAX_LENGTH;
    }

    /**
     * Creates a new document with the given title, intro and author. The
     * document is not saved automatically.
     * @param string $title Title of the document.
     * @param string $intro Intro of the document.
     * @param User $user
     * @return Document The document.
     */
    public static function createNew($title, $intro, User $user) {
        $document = new Document();
        $document->title = (string) $title;
        $document->intro = (string) $intro;
        $document->userId = (int) $user->getId();
        $document->created = new DateTime();
        return $document;
    }

    /**
     * Creates a document with the id, title and intro customized for the widget
     * area. (The id of the widget area is equal to the id of the document.)
     * @param Website $website The website object.
     * @param User $user User that becomes the author of the document.
     * @param int $widgetArea Id of the widget area and document.
     * @return Document The document.
     * @throws NotFoundException If the theme supports no widget area with the
     * given number.
     */
    public static function createForWidgetArea(Website $website, User $user,
            $widgetArea) {
        $widgetAreas = $website->getWidgets()->getWidgetAreas();
        if (!isSet($widgetAreas[$widgetArea])) {
            throw new NotFoundException();
        }

        // This is a valid widget area, so create a document for it
        $title = $widgetAreas[$widgetArea];
        if (strLen($title) > self::TITLE_MAX_LENGTH) {
            $title = substr($title, 0, self::TITLE_MAX_LENGTH);
        }
        $intro = $website->tReplaced("documents.created_for_widgets", $title);

        $document = self::createNew($title, $intro, $user);
        $document->isWidgetArea = true;
        return $document;
    }

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
     * Sets the title. Doesn't validate the title.
     * @param string $title The new title.
     */
    public function setTitle($title) {
        $this->title = (string) $title;
    }

    /**
     * Sets the intro. Doesn't validate the intro.
     * @param string $intro The new intro.
     */
    public function setIntro($intro) {
        $this->intro = (string) $intro;
    }

    /**
     * Gets the (html escaped) URL for this document.
     * @param Text $text The text object, for URL structure.
     * @return string The URL.
     */
    public function getUrl(Text $text) {
        return $text->getUrlPage("document", $this->id);
    }

    /**
     * Gets the id of this document.
     * @return int The id.
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * Returns true if this document is just a widget area; for such
     * documents the title and description cannot be changed, and the document
     * doesn't appear in the database.
     * @return boolean True if this is a document for a widget area, false
     * otherwise.
     */
    public function isForWidgetArea() {
        return $this->isWidgetArea;
    }

    public function canBeSaved() {
        return parent::canBeSaved()
                && !$this->isWidgetArea
                && self::isValidIntro($this->intro)
                && self::isValidTitle($this->title);
    }
}
