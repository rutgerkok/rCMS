<?php

namespace Rcms\Extend\Widget;

use Rcms\Core\Menus;
use Rcms\Core\Website;
use Rcms\Core\WidgetDefinition;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class WidgetRkokLinks extends WidgetDefinition {

    const TITLE_MAX_LENGTH = 40;

    public function getWidget(Website $oWebsite, $id, $data) {
        if (!isSet($data["menu_id"]) || !isSet($data["title"])) {
            return;
        }

        $returnValue = "";
        $loggedInStaff = $oWebsite->isLoggedInAsStaff(true);
        $menu_id = (int) $data["menu_id"];

        // Title
        if (strLen($data["title"]) > 0) {
            $returnValue.= "<h2>" . htmlSpecialChars($data["title"]) . "</h2>\n";
        }

        // Links
        $oMenu = new Menus($oWebsite);
        $returnValue.= '<ul class="linklist">';
        $returnValue.= $oMenu->getAsHtml($oMenu->getLinksByMenu($menu_id), true, $loggedInStaff);
        $returnValue.= "</ul>";

        // Link to add link
        if ($loggedInStaff) {
            $returnValue.= '<p><a class="arrow" href="' . $oWebsite->getUrlPage("create_link", $menu_id);
            $returnValue.= '">' . $oWebsite->t("links.create") . '</a></p>';
        }

        return $returnValue;
    }

    public function getEditor(Website $oWebsite, $id, $data) {
        $title = isSet($data["title"]) ? htmlSpecialChars($data["title"]) : "";
        $menu_id = isSet($data["menu_id"]) ? (int) $data["menu_id"] : 0;
        $returnValue = "";
        $title_max_length = self::TITLE_MAX_LENGTH; // Herodoc doesn't support constants
        // Build menu options
        $oMenu = new Menus($oWebsite);
        $menus = $oMenu->getMenus();
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
            $menu_options.='<a class="arrow" href="' . $oWebsite->getUrlPage("links") . '">' . $oWebsite->t("links.menu.add") . "</a></p>\n";
        }

        unset($menus, $oMenu, $available_menu_id, $menu_name);

        // Return form
        $returnValue.= <<<EOT
            <p>
                <label for="title_$id">{$oWebsite->t("widgets.title")}:</label><br />
                <input type="text" name="title_$id" id="title_$id" value="$title" maxlength="$title_max_length" />
            </p>
            <p>
                <label for="menu_id_$id">{$oWebsite->t("links.menu")}:</label><span class="required">*</span><br />
                
                    $menu_options
                
            </p>
EOT;

        return $returnValue;
    }

    public function parseData(Website $oWebsite, $id) {
        $data = array();
        $data["title"] = isSet($_REQUEST["title_" . $id]) ? trim($_REQUEST["title_" . $id]) : "";
        if (strLen($data["title"]) > self::TITLE_MAX_LENGTH) {
            $oWebsite->addError($oWebsite->t("widgets.title") . " " . $oWebsite->tReplaced("errors.too_long_num", self::TITLE_MAX_LENGTH));
            $data["valid"] = false;
        }
        $data["menu_id"] = isSet($_REQUEST["menu_id_" . $id]) ? (int) $_REQUEST["menu_id_" . $id] : 0;
        $oMenu = new Menus($oWebsite);
        if ($oMenu->getMenuByName($data["menu_id"]) == null) {
            $oWebsite->addError($oWebsite->t("widgets.menu") . " " . $oWebsite->t("errors.not_found"));
            $data["valid"] = false;
        }
        return $data;
    }

}

// Register itself
$this->registerWidget(new WidgetRkokLinks());