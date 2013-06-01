<?php

class Menus {

    const MAX_URL_LENGTH = 200;
    const MAX_LINK_TEXT_LENGTH = 50;
    const MAX_MENU_NAME_LENGTH = 50;
    
    protected $website_object;

    function __construct(Website $oWebsite) {
        $this->website_object = $oWebsite;
    }

    /**
     * Gets an array of links in the specified menu. Key of the array is the id
     * of the link, value is an array itself with the keys url and text.
     * @param int $id Id of the menu.
     * @return array All links in the menu.
     */
    public function get_links_menu($id) {
        $id = (int) $id;
        $oDB = $this->website_object->get_database();

        $sql = "SELECT `link_id`, `link_url`, `link_text` FROM `links` WHERE `menu_id` = $id ORDER BY `link_text`";

        $result = $oDB->query($sql);
        $links = array();
        while (list($id, $url, $text) = $oDB->fetch($result)) {
            $links[$id] = array("url" => $url, "text" => $text);
        }
        return $links;
    }

    public function get_links_search($keyword) {
        $oDB = $this->website_object->get_database();

        // Escape keyword
        $keyword = $oDB->escape_data($keyword);

        $sql = "SELECT `link_id`,`link_url`,`link_text` FROM `links` WHERE `link_url` LIKE '%$keyword%' OR `link_text` LIKE '%$keyword%'";

        $result = $oDB->query($sql);
        $links = array();
        while (list($id, $url, $text) = $oDB->fetch($result)) {
            $links[$id] = array("url" => $url, "text" => $text);
        }
        return $links;
    }

    /**
     * Returns the link with the given id as an array with the keys url and text.
     * @param int $id The id of the link.
     * @return null|array The link, or null if it isn't found.
     */
    public function get_link($id) {
        $oDB = $this->website_object->get_database();
        $id = (int) $id;
        $sql = "SELECT `link_url`, `link_text` FROM `links` WHERE `link_id` = $id";
        $result = $oDB->query($sql);
        if ($result && $oDB->rows($result) == 1) {
            list($url, $text) = $oDB->fetch($result);
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
    public function get_menu_top(Categories $oCats) {
        $oWebsite = $this->website_object;

        $categories = $oCats->get_categories();
        $links = array();

        // Add link to homepage
        $links[0] = array("url" => $oWebsite->get_url_main(), "text" => $oWebsite->t("main.home"));

        if ($categories && count($categories) > 0) {
            foreach ($categories as $id => $cat_name) {
                if ($id == 1) {
                    continue; // Don't display "No categories"
                }
                $links[$id] = array(
                    // Decode url, it will be encoded again by get_as_html
                    "url" => html_entity_decode($oWebsite->get_url_page("category", $id)),
                    "text" => $cat_name
                );
            }
        } else {
            // No categories yet, so database is not installed
            $links[1] = array("url" => $oWebsite->get_url_page("installing_database"), "text" => "Setup database");
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
    public function get_as_html($menu_array, $open_in_new_window = false, $edit_links = false) {
        $return_value = "";
        $oWebsite = $this->website_object;
        foreach ($menu_array as $id => $value) {
            $return_value.= '<li><a href="' . htmlspecialchars($value["url"]) . '"';
            if ($open_in_new_window) {
                $return_value.= ' target="_blank"';
            }
            $return_value.= ">" . htmlspecialchars($value["text"]) . "</a>";
            if($edit_links) {
                $return_value.=' <a class="arrow" href="' . $oWebsite->get_url_page("edit_link", $id) . '">' . $oWebsite->t("main.edit") . "</a>";
                $return_value.=' <a class="arrow" href="' . $oWebsite->get_url_page("delete_link", $id) . '">' . $oWebsite->t("main.delete") . "</a>";
            }
            $return_value.= "</li>\n";
        }
        return $return_value;
    }

    /**
     * Adds a link to a menu. Returns whether successfull. Show an error when
     * there was an error.
     * @param type $menu_id Id of the menu. The id is not checked, only casted.
     * @param type $link_url Url of the link.
     * @param type $link_text Display text of the link.
     */
    public function add_link($menu_id, $link_url, $link_text) {
        $oDB = $this->website_object->get_database();

        // Sanitize
        $menu_id = (int) $menu_id; // TODO check
        $link_url = $oDB->escape_data($link_url);
        $link_text = $oDB->escape_data($link_text);

        $sql = "INSERT INTO `links` (`menu_id`, `link_url`, `link_text`)";
        $sql.= "VALUES ($menu_id, \"$link_url\", \"$link_text\")";
        if ($oDB->query($sql)) {
            return true;
        } else {
            return false;
        }
    }

    public function update_link($link_id, $link_url, $link_text) {
        $oDB = $this->website_object->get_database();

        // Sanitize
        $link_id = (int) $link_id;
        $link_url = $oDB->escape_data($link_url);
        $link_text = $oDB->escape_data($link_text);

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
    public function delete_link($link_id) {
        $oWebsite = $this->website_object;
        $oDB = $oWebsite->get_database();

        $link_id = (int) $link_id;
        if ($link_id <= 0) {
            $oWebsite->add_error($oWebsite->t("main.link") . " " . $oWebsite->t("errors.is_not_removed"));
            return false;
        }
        $sql = "DELETE FROM `links` WHERE `link_id` = $link_id";
        if ($oDB->query($sql)) {
            return true;
        } else {
            $oWebsite->add_error($oWebsite->t("main.link") . " " . $oWebsite->t("errors.is_not_removed"));
            return false;
        }
    }

    /**
     * Gets all menus. $id=>name. Names are parsed by htmlspecialchars().
     * @return array All menus. $id=>name.
     */
    public function get_menus() {
        $oDB = $this->website_object->get_database();
        $sql = "SELECT `menu_id`, `menu_name` FROM `menus`";
        $result = $oDB->query($sql);

        $menus = array();
        while (list($id, $name) = $oDB->fetch($result)) {
            $menus[$id] = htmlspecialchars($name);
        }
        return $menus;
    }
    
    /**
     * Gets the name of the menu with the given id. Name is parsed by htmlspecialchars().
     * @param int $menu_id The id of the menu.
     * @return null|string The name of the menu, or null if the menu doesn't exist.
     */
    public function get_menu_name($menu_id) {
        $menu_id = (int) $menu_id;
        $oDB = $this->website_object->get_database();
        $sql = "SELECT `menu_name` FROM `menus` WHERE `menu_id` = $menu_id";
        $result = $oDB->query($sql);
        if($oDB->rows($result) == 1) {
            $first_row = $oDB->fetch($result);
            return htmlspecialchars($first_row[0]);
        } else {
            return null;
        }
    }
    
    /**
     * Adds a new menu with the given id.
     * @param string $name The name of the menu.
     * @return boolean Whether the menu was successfully added.
     */
    public function add_menu($name) {
        $oDB = $this->website_object->get_database();
        $name = $oDB->escape_data($name);
        $sql = 'INSERT INTO `menus` (`menu_name`) VALUES ("'.$name.'")';
        if($oDB->query($sql)) {
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
    public function rename_menu($menu_id, $new_name) {
        $oDB = $this->website_object->get_database();
        $menu_id = (int) $menu_id;
        $new_name = $oDB->escape_data($new_name);
        $sql = 'UPDATE `menus` SET `menu_name` = "' .$new_name.'" WHERE `menu_id` = ' . $menu_id;
        if($oDB->query($sql)) {
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
    public function delete_menu($menu_id) {
        $oDB = $this->website_object->get_database();
        $menu_id = (int) $menu_id;
        
        $sql = "DELETE FROM `menus` WHERE `menu_id` = $menu_id";
        if($oDB->query($sql)) {
            $sql = "DELETE FROM `links` WHERE `menu_id` = $menu_id";
            if($oDB->query($sql)) {
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