<?php

namespace Rcms\Core;

use InvalidArgumentException;
use PDO;
use PDOException;
use Rcms\Core\Repository\Entity;
use Rcms\Core\Repository\Field;
use Rcms\Core\Repository\Repository;

/**
 * Used to fetch and save menus.
 */
class MenuRepository extends Repository {

    const TABLE_NAME = "menus";
    
    const NAME_MAX_LENGTH = 50;

    private $menuIdField;
    private $menuNameField;

    public function __construct(PDO $database) {
        parent::__construct($database);

        $this->menuIdField = new Field(Field::TYPE_PRIMARY_KEY, "id", "menu_id");
        $this->menuNameField = new Field(Field::TYPE_STRING, "name", "menu_name");
    }

    public function getTableName() {
        return self::TABLE_NAME;
    }

    public function getPrimaryKey() {
        return $this->menuIdField;
    }

    public function getAllFields() {
        return [$this->menuIdField, $this->menuNameField];
    }

    public function createEmptyObject() {
        return new Menu();
    }

    /**
     * Saves a menu to the database.
     * @param $menu The menu.
     */
    public function saveMenu($menu) {
        $this->saveEntity($menu);
    }

    protected function canBeSaved(Entity $menu) {
        if (!($menu instanceof Menu)) {
            return false;
        }
        return parent::canBeSaved($menu)
                && strLen($menu->getName()) > 0
                && strLen($menu->getName()) <= self::NAME_MAX_LENGTH;
    }

    /**
     * Gets the menu with the given id.
     * @param int $menuId Id of the menu.
     * @return Menu The menu.
     * @throws NotFoundException If no menu exists with the given id.
     */
    public function getMenu($menuId) {
        return $this->where($this->menuIdField, '=', $menuId)->selectOneOrFail();
    }

    /**
     * Gets all menus, indexed by the menu id.
     * @return Menu[] All menus.
     */
    public function getAllMenus() {
        $menus = $this->all()->select();
        
        $returnValue = [];
        foreach ($menus as $menu) {
            $returnValue[$menu->getId()] = $menu;
        }
        return $returnValue;
    }

    /**
     * Deletes the menu with the given id. Note that the links in the menu are not deleted automatically.
     * @param int $menuId The id of the menu.
     * @throws NotFoundException If no such menu exists.
     * @throws PDOException If a database error occurs.
     */
    public function deleteMenu($menuId) {
        $this->where($this->menuIdField, '=', $menuId)->deleteOneOrFail();
    }

}
