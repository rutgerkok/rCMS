<?php

class Menus {

    const MAX_URL_LENGTH = 200;
    const MAX_LINK_TEXT_LENGTH = 50;
    const MAX_MENU_NAME_LENGTH = 50;

    protected $websiteObject;

    function __construct(Website $oWebsite) {
        $this->websiteObject = $oWebsite;
    }

    /**
     * Gets an array of links in the specified menu. Key of the array is the id
     * of the link, value is an array itself with the keys url and text.
     * @param int $id Id of the menu.
     * @return array All links in the menu.
     */
    public function getLinksByMenu($id) {
        $id = (int) $id;
        $oDB = $this->websiteObject->getDatabase();

        $sql = "SELECT `link_id`, `link_url`, `link_text` FROM `links` WHERE `menu_id` = $id ORDER BY `link_text`";

        $result = $oDB->query($sql);
        $links = array();
        while (list($id, $url, $text) = $oDB->fetchNumeric($result)) {
            $links[$id] = array("url" => $url, "text" => $text);
        }
        return $links;
    }

    public function getLinksBySearch($keyword) {
        $oDB = $this->websiteObject->getDatabase();

        // Escape keyword
        $keyword = $oDB->escapeData($keyword);

        $sql = "SELECT `link_id`,`link_url`,`link_text` FROM `links` WHERE `link_url` LIKE '%$keyword%' OR `link_text` LIKE '%$keyword%'";

        $result = $oDB->query($sql);
        $links = array();
        while (list($id, $url, $text) = $oDB->fetchNumeric($result)) {
            $links[$id] = array("url" => $url, "text" => $text);
        }
        return $links;
    }

    /**
     * Returns the link with the given id as an array with the keys url and text.
     * @param int $id The id of the link.
     * @return null|array The link, or null if it isn't found.
     */
    public function getLink($id) {
        $oDB = $this->websiteObject->getDatabase();
        $id = (int) $id;
        $sql = "SELECT `link_url`, `link_text` FROM `links` WHERE `link_id` = $id";
        $result = $oDB->query($sql);
        if ($result && $oDB->rows($result) == 1) {
            list($url, $text) = $oDB->fetchNumeric($result);
            return array("url" => $url, "text" => $text);
        }
        return null;
    }

    /**
     * Gets the menu that should be displayed at the top of the page.
     * Returns a <li> list (HTML) for now. Nu <ul>/<ol> tags included.
     * @param Categories $oCats The categories object.
     * @return string The HTML of the menu.
     */
    public function getMenuTop(Categories $oCats) {
        $oWebsite = $this->websiteObject;

        $categories = $oCats->getCategories();
        $links = array();

        // Add link to homepage
        $links[0] = array("url" => $oWebsite->getUrlMain(), "text" => $oWebsite->t("main.home"));

        if ($oWebsite->getDatabase()->isUpToDate()) {
            foreach ($categories as $id => $cat_name) {
                if ($id == 1) {
                    continue; // Don't display "No categories"
                }
                $links[$id] = array(
                    // Decode url, it will be encoded again by get_as_html
                    "url" => html_entity_decode($oWebsite->getUrlPage("category", $id)),
                    "text" => $cat_name
                );
            }
        } else {
            // No categories yet, so database is not installed
            $links[1] = array("url" => $oWebsite->getUrlPage("installing_database"), "text" => "Setup database");
        }

        return $links;
    }

    /**
     * Gets the links as HTML (just some li and a tags)
     * @param array $menu_array The menu array.
     * @param boolean $open_in_new_window Whether the links should
     *  open in a new window.
     * @param boolean $edit_links Whether edit and delete links should be 
     *  displayed. If true, those are displayed even if the user can't use them.
     * @return string
     */
    public function getAsHtml($menu_array, $open_in_new_window = false, $edit_links = false) {
        $returnValue = "";
        $oWebsite = $this->websiteObject;
        foreach ($menu_array as $id => $value) {
            $returnValue.= '<li><a href="' . htmlSpecialChars($value["url"]) . '"';
            if ($open_in_new_window) {
                $returnValue.= ' target="_blank"';
            }
            $returnValue.= ">" . htmlSpecialChars($value["text"]) . "</a>";
            if ($edit_links) {
                $returnValue.=' <a class="arrow" href="' . $oWebsite->getUrlPage("edit_link", $id) . '">' . $oWebsite->t("main.edit") . "</a>";
                $returnValue.=' <a class="arrow" href="' . $oWebsite->getUrlPage("delete_link", $id) . '">' . $oWebsite->t("main.delete") . "</a>";
            }
            $returnValue.= "</li>\n";
        }
        return $returnValue;
    }

    /**
     * Adds a link to a menu. Returns whether successfull. Show an error when
     * there was an error.
     * @param type $menu_id Id of the menu. The id is not checked, only casted.
     * @param type $link_url Url of the link.
     * @param type $link_text Display text of the link.
     */
    public function addLink($menu_id, $link_url, $link_text) {
        $oDB = $this->websiteObject->getDatabase();

        // Sanitize
        $menu_id = (int) $menu_id; // TODO check
        $link_url = $oDB->escapeData($link_url);
        $link_text = $oDB->escapeData($link_text);

        $sql = "INSERT INTO `links` (`menu_id`, `link_url`, `link_text`)";
        $sql.= "VALUES ($menu_id, \"$link_url\", \"$link_text\")";
        if ($oDB->query($sql)) {
            return true;
        } else {
            return false;
        }
    }

    public function updateLink($link_id, $link_url, $link_text) {
        $oDB = $this->websiteObject->getDatabase();

        // Sanitize
        $link_id = (int) $link_id;
        $link_url = $oDB->escapeData($link_url);
        $link_text = $oDB->escapeData($link_text);

        $sql = "UPDATE `links` SET `link_url` = \"$link_url\", `link_text` = \"$link_text\" ";
        $sql.= "WHERE `link_id` = $link_id";
        if ($oDB->query($sql)) {
            return true;
        } else {
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
        $oWebsite = $this->websiteObject;
        $oDB = $oWebsite->getDatabase();

        $link_id = (int) $link_id;
        if ($link_id <= 0) {
            $oWebsite->addError($oWebsite->t("main.link") . " " . $oWebsite->t("errors.is_not_removed"));
            return false;
        }
        $sql = "DELETE FROM `links` WHERE `link_id` = $link_id";
        if ($oDB->query($sql)) {
            return true;
        } else {
            $oWebsite->addError($oWebsite->t("main.link") . " " . $oWebsite->t("errors.is_not_removed"));
            return false;
        }
    }

    /**
     * Gets all menus. $id=>name. Names are parsed by htmlSpecialChars().
     * @return array All menus. $id=>name.
     */
    public function getMenus() {
        $oDB = $this->websiteObject->getDatabase();
        $sql = "SELECT `menu_id`, `menu_name` FROM `menus`";
        $result = $oDB->query($sql);

        $menus = array();
        while (list($id, $name) = $oDB->fetchNumeric($result)) {
            $menus[$id] = htmlSpecialChars($name);
        }
        return $menus;
    }

    /**
     * Gets the name of the menu with the given id. Name is parsed by htmlSpecialChars().
     * @param int $menu_id The id of the menu.
     * @return null|string The name of the menu, or null if the menu doesn't exist.
     */
    public function getMenuByName($menu_id) {
        $menu_id = (int) $menu_id;
        $oDB = $this->websiteObject->getDatabase();
        $sql = "SELECT `menu_name` FROM `menus` WHERE `menu_id` = $menu_id";
        $result = $oDB->query($sql);
        if ($oDB->rows($result) == 1) {
            $first_row = $oDB->fetchNumeric($result);
            return htmlSpecialChars($first_row[0]);
        } else {
            return null;
        }
    }

    /**
     * Adds a new menu with the given id.
     * @param string $name The name of the menu.
     * @return boolean Whether the menu was successfully added.
     */
    public function addMenu($name) {
        $oDB = $this->websiteObject->getDatabase();
        $name = $oDB->escapeData($name);
        $sql = 'INSERT INTO `menus` (`menu_name`) VALUES ("' . $name . '")';
        if ($oDB->query($sql)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Renames a menu to something else.
     * @param int $menu_id Current menu id.
     * @param string $new_name The new name.
     * @return boolean Whether the rename was successful.
     */
    public function renameMenu($menu_id, $new_name) {
        $oDB = $this->websiteObject->getDatabase();
        $menu_id = (int) $menu_id;
        $new_name = $oDB->escapeData($new_name);
        $sql = 'UPDATE `menus` SET `menu_name` = "' . $new_name . '" WHERE `menu_id` = ' . $menu_id;
        if ($oDB->query($sql)) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * Delets the menu with the given id. All links in the menu are also deleted.
     * @param int $menu_id The id of the menu.
     * @return boolean Whether the deletion was successful.
     */
    public function deleteMenu($menu_id) {
        $oDB = $this->websiteObject->getDatabase();
        $menu_id = (int) $menu_id;

        $sql = "DELETE FROM `menus` WHERE `menu_id` = $menu_id";
        if ($oDB->query($sql)) {
            $sql = "DELETE FROM `links` WHERE `menu_id` = $menu_id";
            if ($oDB->query($sql)) {
                return true;
            } else {
                return false;
            }
        } else {
            return false;
        }
    }

}

?>