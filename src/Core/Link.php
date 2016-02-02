<?php

namespace Rcms\Core;

use Psr\Http\Message\UriInterface;
use Rcms\Core\Repository\Entity;

/**
 * Represents a link. A link has an id, url, description and is
 * placed in a menu. 
 */
class Link extends Entity {

    protected $id;

    /**
     * @var UriInterface
     */
    protected $url;
    protected $text;
    protected $menuId;

    /**
     * Creates a new link with the given url and text.
     *
     * This link will not be part of a menu and therefore cannot be saved to
     * the link repository.
     *
     * @param UriInterface $url Url of the link.
     * @param string $text Text of the link.
     * @return Link The link.
     */
    public static function of(UriInterface $url, $text) {
        $link = new Link();
        $link->url = $url;
        $link->text = (string) $text;
        return $link;
    }

    /**
     * Creates a link that can be saved to the database.
     * @param int $linkId Id of the link, 0 for new links.
     * @param int $menuId Id of the menu.
     * @param UriInterface $url Url of the link.
     * @param string $text Text of the link.
     * @return Link The link.
     */
    public static function createSaveable($linkId, $menuId, UriInterface $url,
            $text) {
        $link = static::of($url, $text);
        $link->id = (int) $linkId;
        $link->menuId = (int) $menuId;
        return $link;
    }

    /**
     * Gets the url of the link.
     * @return UriInterface The url.
     */
    public function getUrl() {
        return $this->url;
    }

    /**
     * Gets the text of the link.
     * @return string The text.
     */
    public function getText() {
        return $this->text;
    }

    /**
     * Gets the id of the menu this link is placed in.
     * @return int The menu id.
     */
    public function getMenuId() {
        return $this->menuId;
    }

    /**
     * Gets the id of this link.
     * @return int The id.
     */
    public function getId() {
        return $this->id;
    }

    public function canBeSaved() {
        return parent::canBeSaved() && ($this->menuId > 0 || $this->id > 0);
    }

}
