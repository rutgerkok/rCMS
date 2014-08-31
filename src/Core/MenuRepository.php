<?php

namespace Rcms\Core;

use InvalidArgumentException;
use PDOException;
use Rcms\Core\Exception\NotFoundException;
use Rcms\Core\Repository\Field;
use Rcms\Core\Repository\Repository;

/**
 * Used to fetch and save menus.
 */
class MenuRepository extends Repository {

    const TABLE_NAME = "menus";

    private $menuIdField;
    private $menuNameField;

    public function __construct(Database $database) {
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
        return array($this->menuIdField, $this->menuNameField);
    }

    public function createEmptyObject() {
        return new Menu();
    }

    /**
     * Adds a new menu.
     * @param string $menuName Name of the menu.
     * @throws PDOException If the menu could not be saved.
     */
    public function addMenu($menuName) {
        $menu = Menu::createMenu(0, $menuName);
        $this->saveEntity($menu);
        return true;
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
     * Gets all menus.
     * @return Menu[] All menus.
     */
    public function getAllMenus() {
        return $this->all()->select();
    }

    /**
     * Checks if the menu with the given id exists.
     * 
     * <p>Note: when you're interested in the contents of the menu but are not
     * sure if the menu exists, just call getMenu($menuId) and catch the
     * exception.
     * @param int $menuId Id of the menu.
     * @return boolean True if the menu exists, false otherwise.
     */
    public function exists($menuId) {
        try {
            $this->getMenu($menuId);
            return true;
        } catch (NotFoundException $e) {
            return false;
        }
    }

    /**
     * Gets the name of the menu with the given id. Name is parsed by htmlSpecialChars().
     * @param int $menu_id The id of the menu.
     * @return null|string The name of the menu, or null if the menu doesn't exist.
     */
    public function getMenuName($menu_id) {
        try {
            return $this->getMenu($menu_id)->getName();
        } catch (NotFoundException $e) {
            return null;
        }
    }

    /**
     * Renames a menu to something else.
     * @param int $menu_id Current menu id.
     * @param string $new_name The new name.
     * @throws NotFoundException If no menu exists with the given id.
     * @throws InvalidArgumentException If the menu id is not a number or is 0.
     */
    public function renameMenu($menu_id, $new_name) {
        if ((int) $menu_id === 0) {
            throw new InvalidArgumentException("Invalid menu id:" . $menu_id);
        }
        $menu = Menu::createMenu($menu_id, $new_name);
        $this->saveEntity($menu);
        return true;
    }

    /**
     * Delets the menu with the given id. All links in the menu are also deleted.
     * @param int $menu_id The id of the menu.
     * @throws NotFoundException If no such menu exists.
     * @throws PDOException If a database error occurs.
     */
    public function deleteMenu($menu_id) {
        $this->where($this->menuIdField, '=', $menu_id)->deleteOneOrFail();
        return true;
    }

}
