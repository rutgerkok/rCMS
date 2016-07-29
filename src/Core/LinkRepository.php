<?php

namespace Rcms\Core;

use PDO;
use PDOException;
use Psr\Http\Message\UriInterface;
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

    public function __construct(PDO $database) {
        parent::__construct($database);

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
     * Saves a link to the database.
     * @param Link $link The link to save.
     * @throws PDOException When a database error occurs.
     */
    public function saveLink(Link $link) {
        $this->saveEntity($link);
    }

    /**
     * Moves all links in one menu to another menu.
     * @param Menu $from The menu to take all links from.
     * @param Menu $to The menu to move all links to.
     */
    public function moveLinks(Menu $from, Menu $to) {
        $this->pdo->prepare(<<<SQL
            UPDATE `{$this->getTableName()}`
            SET `{$this->menuIdField->getNameInDatabase()}` = :to
            WHERE `{$this->menuIdField->getNameInDatabase()}` = :from
SQL
        )->execute([":from" => $from->getId(), ":to" => $to->getId()]);
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

    /**
     * Removes the given link. Returns whether successfull. Displays
     * an error on failure.
     * @param Link $link The link to delete.
     * @return boolean Whether the link was removed.
     * @throws NotFoundException When no such link exists in the database.
     * @throws PDOException When a database error occurs.
     */
    public function deleteLink(Link $link) {
        $this->where($this->linkIdField, '=', $link->getId())->deleteOneOrFail();
    }

    /**
     * Deletes all links in the given menu. This method does nothing if the menu is empty.
     * @param Menu $menu The menu.
     * @throws PDOException If a database error occurs.
     */
    public function deleteLinksInMenu(Menu $menu) {
        $this->where($this->menuIdField, '=', $menu->getId())->delete();
    }

}
