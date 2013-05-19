<?php

$this->register_widget(new WidgetRkokLinks());

class WidgetRkokLinks extends WidgetDefinition {

    const TITLE_MAX_LENGTH = 40;

    public function get_widget(Website $oWebsite, $id, $data) {
        if (!isset($data["menu_id"]) || !isset($data["title"])) {
            return;
        }

        $return_value = "";

        // Title
        if (strlen($data["title"]) > 0) {
            $return_value.= "<h2>" . htmlspecialchars($data["title"]) . "</h2>\n";
        }

        // Links
        $oMenu = new Menus($oWebsite);
        $return_value.= '<ul class="linklist">';
        $return_value.= $oMenu->get_as_html($oMenu->get_links_menu((int) $data["menu_id"]), true, $oWebsite->logged_in_staff(true));
        $return_value.= "</ul>";

        return $return_value;
    }

    public function get_editor(Website $oWebsite, $id, $data) {
        $title = isset($data["title"]) ? htmlspecialchars($data["title"]) : "";
        $menu_id = isset($data["menu_id"]) ? (int) $data["menu_id"] : 0;
        $return_value = "";
        $title_max_length = self::TITLE_MAX_LENGTH; // Herodoc doens't support constants
        // Build menu options
        $oMenu = new Menus($oWebsite);
        $menus = $oMenu->get_menus();
        $menu_options = "";
        if (count($menus) > 0) {
            $menu_options.= "<select name=\"menu_id_$id\" id=\"menu_id_$id\">\n";
            foreach ($menus as $available_menu_id => $menu_name) {
                $menu_options.= '<option value="' . $available_menu_id . '"';
                if ($available_menu_id == $menu_id) {
                    $menu_options.= ' selected="selected"';
                }
                $menu_options.= '>' . $menu_name . "</option>\n";
            }
            $menu_options.="</select>\n";
        } else {
            $menu_options.="<p><em>" . $oWebsite->t("errors.nothing_found") . "</em> ";
            $menu_options.='<a class="arrow" href="' . $oWebsite->get_url_page("links") . '">' . $oWebsite->t("links.menu.add") . "</a></p>\n";
        }

        unset($menus, $oMenu, $available_menu_id, $menu_name);

        // Return form
        $return_value.= <<<EOT
            <p>
                <label for="title_$id">{$oWebsite->t("widgets.title")}:</label><br />
                <input type="text" name="title_$id" id="title_$id" value="$title" maxlength="$title_max_length" />
            </p>
            <p>
                <label for="menu_id_$id">{$oWebsite->t("links.menu")}:</label><span class="required">*</span><br />
                
                    $menu_options
                
            </p>
EOT;

        return $return_value;
    }

    public function parse_data(Website $oWebsite, $id) {
        $data = array();
        $data["title"] = isset($_REQUEST["title_" . $id]) ? trim($_REQUEST["title_" . $id]) : "";
        if (strlen($data["title"]) > self::TITLE_MAX_LENGTH) {
            $oWebsite->add_error($oWebsite->t("widgets.title") . " " . $oWebsite->t_replaced("errors.too_long_num", self::TITLE_MAX_LENGTH));
            $data["valid"] = false;
        }
        $data["menu_id"] = isset($_REQUEST["menu_id_" . $id]) ? (int) $_REQUEST["menu_id_" . $id] : 0;
        $oMenu = new Menus($oWebsite);
        if ($oMenu->get_menu_name($data["menu_id"]) == null) {
            $oWebsite->add_error($oWebsite->t("widgets.menu") . " " . $oWebsite->t("errors.not_found"));
            $data["valid"] = false;
        }
        return $data;
    }

}

?>