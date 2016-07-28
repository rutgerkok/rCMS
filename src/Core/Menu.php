<?php

namespace Rcms\Core;

use Rcms\Core\Repository\Entity;

/**
 * A menu is something that holds zero or more links.
 */
class Menu extends Entity {

    protected $id = 0;
    protected $name;

    /**
     * Creates a new menu. Doesn't save to the database automatically.
     * @param string $menuName Name of the menu.
     * @return Menu The menu.
     */
    public static function createNew($menuName) {
        $menu = new Menu();
        $menu->setName($menuName);
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

    /**
     * Sets the name of the menu.
     * @param $name The new name of the menu.
     */
    public function setName($name) {
        $this->name = (string) $name;
    }

}
