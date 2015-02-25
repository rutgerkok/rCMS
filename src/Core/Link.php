<?php

namespace Rcms\Core;

use BadMethodCallException;
use Rcms\Core\Repository\Entity;
use Rcms\Core\Repository\Field;

/**
 * Represents a link. A link has an id, url, description and is
 * placed in a menu. 
 */
class Link extends Entity {

    protected $id;
    protected $url;
    protected $text;
    protected $menuId;

    /**
     * Creates a new link with the given url and text.
     *
     * This link will not be part of a menu and therefore cannot be saved to
     * the link repository.
     *
     * @param string $url Url of the link.
     * @param string $text Text of the link.
     * @return Link The link.
     */
    public static function of($url, $text) {
        $link = new Link();
        $link->url = (string) $url;
        $link->text = (string) $text;
        return $link;
    }

    /**
     * Creates a link that can be saved to the database.
     * @param int $linkId Id of the link, 0 for new links.
     * @param int $menuId Id of the menu.
     * @param string $url Url of the link.
     * @param string $text Text of the link.
     * @return Link The link.
     */
    public static function createSaveable($linkId, $menuId, $url, $text) {
        $link = static::of($url, $text);
        $link->id = (int) $linkId;
        $link->menuId = (int) $menuId;
        return $link;
    }

    /**
     * Gets the url of the link.
     * @return string The url.
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

    public function getField(Field $field) {
        // Make sure links created with createTemporary cannot be saved
        if ($field->getName() === "menuId" && $this->menuId === 0) {
            throw new BadMethodCallException("Cannot save links without a menu");
        }

        return parent::getField($field);
    }

}
