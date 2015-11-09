<?php

namespace Rcms\Core\Document;

use DateTime;
use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\Repository\Entity;
use Rcms\Core\Text;
use Rcms\Core\Website;

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

    public static function createNew($title, $intro, $userId) {
        $document = new Document();
        $document->title = (string) $title;
        $document->intro = (string) $intro;
        $document->userId = (int) $userId;
        $document->created = new DateTime();
        $document->userId = $userId;
        return $document;
    }

    public static function createForWidgetArea(Website $website, $widgetArea) {
        $widgetAreas = $website->getThemeManager()->getCurrentTheme()->getWidgetAreas($website);
        if (!isSet($widgetAreas[$widgetArea])) {
            throw new NotFoundException();
        }
        
            // This is a valid widget area, so create a document for it
            $title = $widgetAreas[$widgetArea];
            $intro = $website->tReplaced("documents.created_for_widgets", $title);
            $document = new Document();
            $document->id = $widgetArea;
        
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

}
