<?php

namespace Rcms\Core;

use Rcms\Core\Repository\Entity;

/**
 * A menu is something that holds zero or more links.
 */
class Menu extends Entity {

    protected $id;
    protected $name;

    /**
     * Creates a new menu.
     * @param int $id Id of the menu. Use 0 for new menus.
     * @param string $name Name of the menu.
     */
    public static function createMenu($id, $name) {
        $menu = new Menu();
        $menu->id = (int) $id;
        $menu->name = (string) $name;
        return $menu;
    }

    /**
     * Gets the id of the menu. Returns 0 for new, unsaved menus.
     * @return int The id.
     */
    public function getId() {
        return $this->id;
    }

    /**
     * Gets the name of the menu.
     * @return string The name.
     */
    public function getName() {
        return $this->name;
    }

}
