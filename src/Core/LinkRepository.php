<?php

namespace Rcms\Core;

use PDOException;
use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\Repository\Field;
use Rcms\Core\Repository\Repository;

class LinkRepository extends Repository {

    const TABLE_NAME = "links";
    const MAX_URL_LENGTH = 200;
    const MAX_LINK_TEXT_LENGTH = 50;
    const MAX_MENU_NAME_LENGTH = 50;

    protected $websiteObject;
    protected $linkIdField;
    protected $linkTextField;
    protected $linkUrlField;
    protected $menuIdField;

    public function __construct(Website $oWebsite) {
        parent::__construct($oWebsite->getDatabase());
        $this->websiteObject = $oWebsite;

        $this->linkIdField = new Field(Field::TYPE_PRIMARY_KEY, "id", "link_id");
        $this->linkTextField = new Field(Field::TYPE_STRING, "text", "link_text");
        $this->linkUrlField = new Field(Field::TYPE_STRING, "url", "link_url");
        $this->menuIdField = new Field(Field::TYPE_INT, "menuId", "menu_id");
    }

    public function getTableName() {
        return self::TABLE_NAME;
    }

    public function getPrimaryKey() {
        return $this->linkIdField;
    }

    public function getStandardFields() {
        return array($this->linkIdField, $this->linkTextField, $this->linkUrlField);
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

    public function getLinksBySearch($keyword) {
        return $this->whereRaw("`link_url` LIKE :keyword OR `link_text` LIKE :keyword", array(":keyword" => "%$keyword%"))->select();
    }

    /**
     * Returns the link with the given id as an array with the keys url and text.
     * @param int $id The id of the link.
     * @return Link|null The link, or null if it isn't found.
     */
    public function getLink($id) {
        try {
            return $this->where($this->getPrimaryKey(), '=', $id)->selectOneOrFail();
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * Gets the menu that should be displayed at the top of the page.
     * Returns a <li> list (HTML) for now. No <ul>/<ol> tags included.
     * @param CategoryRepository $oCats The categories object.
     * @return string The HTML of the menu.
     */
    public function getMenuTop(CategoryRepository $oCats) {
        $oWebsite = $this->websiteObject;


        $links = array();

        // Add link to homepage
        $links[] = Link::createTemporary($oWebsite->getUrlMain(), $oWebsite->t("main.home"));

        if ($oWebsite->getDatabase()->isUpToDate() && $oWebsite->getDatabase()->isInstalled()) {
            $categories = $oCats->getCategories();
            foreach ($categories as $category) {
                if ($category->isStandardCategory()) {
                    continue; // Don't display "No categories"
                }
                $links[] = Link::createTemporary(
                                // Decode url, it will be encoded again by get_as_html
                                html_entity_decode($oWebsite->getUrlPage("category", $category->getId())), $category->getName()
                );
            }
        } else {
            // No categories yet, so database is not installed
            $links[] = Link::createTemporary($oWebsite->getUrlPage("installing_database"), "Setup database");
        }

        return $links;
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
        $oWebsite = $this->websiteObject;
        foreach ($menu_array as $link) {
            $returnValue.= '<li><a href="' . htmlSpecialChars($link->getUrl()) . '"';
            if ($open_in_new_window) {
                $returnValue.= ' target="_blank"';
            }
            $returnValue.= ">" . htmlSpecialChars($link->getText()) . "</a>";
            if ($edit_links) {
                $returnValue.=' <a class="arrow" href="' . $oWebsite->getUrlPage("edit_link", $link->getId()) . '">' . $oWebsite->t("main.edit") . "</a>";
                $returnValue.=' <a class="arrow" href="' . $oWebsite->getUrlPage("delete_link", $link->getId()) . '">' . $oWebsite->t("main.delete") . "</a>";
            }
            $returnValue.= "</li>\n";
        }
        return $returnValue;
    }

    /**
     * Adds a link to a menu. Returns whether successfull. Show an error when
     * there was an error.
     * @param int $menu_id Id of the menu. The id is not checked, only casted.
     * @param string $link_url Url of the link.
     * @param string $link_text Display text of the link.
     */
    public function addLink($menu_id, $link_url, $link_text) {
        $link = Link::createSaveable(0, $menu_id, $link_url, $link_text);
        try {
            $this->saveEntity($link);
            return true;
        } catch (PDOException $e) {
            $this->websiteObject->getText()->logException("Failed to add link", $e);
            return false;
        }
    }

    public function updateLink($link_id, $link_url, $link_text) {
        $link = Link::createSaveable($link_id, 0, $link_url, $link_text);
        try {
            // Don't save menu id, we used a temporary id because this method
            // doesn't know the real id.
            $this->saveEntity($link, array($this->linkTextField, $this->linkUrlField));
            return true;
        } catch (PDOException $e) {
            $this->websiteObject->getText()->logException("Failed to update link", $e);
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
            $text = $this->websiteObject->getText();
            $text->addError($oWebsite->t("main.link") . " " . $oWebsite->t("errors.is_not_removed"));
            $text->logException("Error deleting link", $e);
            return false;
        }
    }

}