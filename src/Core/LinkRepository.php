<?php

namespace Rcms\Core;

use PDOException;
use Psr\Http\Message\UriInterface;
use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\Repository\Entity;
use Rcms\Core\Repository\Field;
use Rcms\Core\Repository\Repository;

class LinkRepository extends Repository {

    const TABLE_NAME = "links";
    const MAX_URL_LENGTH = 200;
    const MAX_LINK_TEXT_LENGTH = 50;

    protected $website;
    protected $linkIdField;
    protected $linkTextField;
    protected $linkUrlField;
    protected $menuIdField;

    public function __construct(Website $website) {
        parent::__construct($website->getDatabase());
        $this->website = $website;

        $this->linkIdField = new Field(Field::TYPE_PRIMARY_KEY, "id", "link_id");
        $this->linkTextField = new Field(Field::TYPE_STRING, "text", "link_text");
        $this->linkUrlField = new Field(Field::TYPE_URI, "url", "link_url");
        $this->menuIdField = new Field(Field::TYPE_INT, "menuId", "menu_id");
    }

    public function getTableName() {
        return self::TABLE_NAME;
    }

    public function getPrimaryKey() {
        return $this->linkIdField;
    }

    public function getStandardFields() {
        return [$this->linkIdField, $this->linkTextField, $this->linkUrlField];
    }

    public function getAllFields() {
        $fields = $this->getStandardFields();
        $fields[] = $this->menuIdField;
        return $fields;
    }

    public function createEmptyObject() {
        return new Link();
    }

    /**
     * Gets all links from the given menu.
     * @param int $id The menu id.
     * @return Link[] The links.
     */
    public function getLinksByMenu($id) {
        return $this->where($this->menuIdField, '=', $id)->orderAscending($this->linkTextField)->select();
    }

    /**
     * Gets the amount of links in the specified menu. Returns 0 if no menu with
     * that id exists.
     * @param int $id Id of the menu.
     * @return int The amount of links.
     */
    public function getLinkCountByMenu($id) {
        return $this->where($this->menuIdField, '=', $id)->count();
    }

    /**
     * Gets all links for every menu that are stored in the database.
     * @return Link[] All links.
     */
    public function getAllLinks() {
        return $this->all()->withAllFields()->select();
    }
    
    /**
     * Gets an array of int=>Link[], where int is the menu id and Link[] the
     * links in that menu.
     * @return Link[][] The link lists by menu id.
     */
    public function getAllLinksByMenu() {
        $returnValue = [];
        $allLinks = $this->getAllLinks();
        foreach ($allLinks as $link) {
            $returnValue[$link->getMenuId()][] = $link;
        }
        return $returnValue;
    }

    public function getLinksBySearch($keyword) {
        return $this->whereRaw("`link_url` LIKE :keyword OR `link_text` LIKE :keyword", [":keyword" => "%$keyword%"])->select();
    }

    /**
     * Returns the link with the given id, or null if not found.
     * @param int $id The id of the link.
     * @return Link|null The link, or null if it isn't found.
     */
    public function getLinkOrNull($id) {
        try {
            return $this->where($this->getPrimaryKey(), '=', $id)->selectOneOrFail();
        } catch (NotFoundException $e) {
            return null;
        }
    }
    
    /**
     * Returns the link with the given id.
     * @param int $linkId The id of the link.
     * @return Link The link.
     * @throws NotFoundException If no link exists with that id.
     * @throws PDOException When a database error occurs.
     */
    public function getLink($linkId) {
         return $this->where($this->getPrimaryKey(), '=', $linkId)
                 ->withAllFields()
                 ->selectOneOrFail();
    }

    /**
     * Gets the links as HTML (just some li and a tags)
     * @param Link[] $menu_array The menu array.
     * @param boolean $open_in_new_window Whether the links should
     *  open in a new window.
     * @param boolean $edit_links Whether edit and delete links should be 
     *  displayed. If true, those are displayed even if the user can't use them.
     * @return string
     */
    public function getAsHtml(array $menu_array, $open_in_new_window = false,
            $edit_links = false) {
        $returnValue = "";
        $website = $this->website;
        foreach ($menu_array as $link) {
            $returnValue.= '<li><a href="' . htmlSpecialChars($link->getUrl()) . '"';
            if ($open_in_new_window) {
                $returnValue.= ' target="_blank"';
            }
            $returnValue.= ">" . htmlSpecialChars($link->getText()) . "</a>";
            if ($edit_links) {
                $returnValue.=' <a class="arrow" href="' . $website->getUrlPage("edit_link", $link->getId()) . '">' . $website->t("main.edit") . "</a>";
                $returnValue.=' <a class="arrow" href="' . $website->getUrlPage("delete_link", $link->getId()) . '">' . $website->t("main.delete") . "</a>";
            }
            $returnValue.= "</li>\n";
        }
        return $returnValue;
    }

    /**
     * Adds a link to a menu. Returns whether successfull. Show an error when
     * there was an error.
     * @param int $menu_id Id of the menu. The id is not checked, only casted.
     * @param UriInterface $link_url Url of the link.
     * @param string $link_text Display text of the link.
     */
    public function addLink($menu_id, UriInterface $link_url, $link_text) {
        $link = Link::createSaveable(0, $menu_id, $link_url, $link_text);
        try {
            $this->saveEntity($link);
            return true;
        } catch (PDOException $e) {
            $this->website->getText()->logException("Failed to add link", $e);
            return false;
        }
    }
    
    /**
     * Saves a link to the database.
     * @param Link $link The link to save.
     * @throws PDOException When a database error occurs.
     */
    public function saveLink(Link $link) {
        $this->saveEntity($link);
    }
    
    protected function canBeSaved(Entity $link) {
        if (!($link instanceof Link)) {
            return false;
        }

        return parent::canBeSaved($link)
                && ($link->getMenuId() > 0 || $link->getId() > 0)
                && strLen($link->getUrl()) <= self::MAX_URL_LENGTH
                && strLen($link->getText()) <= self::MAX_LINK_TEXT_LENGTH;
    }

    public function updateLink($link_id, $link_url, $link_text) {
        $link = Link::createSaveable($link_id, 0, $link_url, $link_text);
        try {
            // Don't save menu id, we used a temporary id because this method
            // doesn't know the real id.
            $this->saveEntity($link, [$this->linkTextField, $this->linkUrlField]);
            return true;
        } catch (PDOException $e) {
            $this->website->getText()->logException("Failed to update link", $e);
            return false;
        }
    }

    /**
     * Removes the link with the given id. Returns whether successfull. Displays
     * an error on failure.
     * @param int $link_id The id of the link.
     * @return boolean Whether the link was removed.
     */
    public function deleteLink($link_id) {
        try {
            $this->where($this->linkIdField, '=', $link_id)->deleteOneOrFail();
            return true;
        } catch (NotFoundException $e) {
            $text = $this->website->getText();
            $text->addError($website->t("main.link") . " " . $website->t("errors.is_not_removed"));
            $text->logException("Error deleting link", $e);
            return false;
        }
    }

}
