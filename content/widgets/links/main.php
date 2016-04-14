<?php

namespace Rcms\Extend\Widget;

use Psr\Http\Message\StreamInterface;
use Rcms\Core\LinkRepository;
use Rcms\Core\MenuRepository;
use Rcms\Core\Website;
use Rcms\Core\Widget\WidgetDefinition;

// Protect against calling this script directly
if (!defined("WEBSITE")) {
    die();
}

class WidgetRkokLinks extends WidgetDefinition {

    const TITLE_MAX_LENGTH = 40;

    public function writeText(StreamInterface $stream, Website $website, $id, $data) {
        if (!isSet($data["menu_id"]) || !isSet($data["title"])) {
            return;
        }

        $loggedInStaff = $website->isLoggedInAsStaff(true);
        $menu_id = (int) $data["menu_id"];

        // Title
        if (strLen($data["title"]) > 0) {
            $stream->write("<h2>" . htmlSpecialChars($data["title"]) . "</h2>\n");
        }

        // Links
        $oMenu = new LinkRepository($website);
        $stream->write('<ul class="linklist">');
        $stream->write($oMenu->getAsHtml($oMenu->getLinksByMenu($menu_id), true, $loggedInStaff));
        $stream->write("</ul>");

        // Link to add link
        if ($loggedInStaff) {
            $stream->write('<p><a class="arrow" href="' . $website->getUrlPage("create_link", $menu_id));
            $stream->write('">' . $website->t("links.create") . '</a></p>');
        }
    }

    public function getEditor(Website $website, $id, $data) {
        $title = isSet($data["title"]) ? htmlSpecialChars($data["title"]) : "";
        $menu_id = isSet($data["menu_id"]) ? (int) $data["menu_id"] : 0;
        $returnValue = "";
        $title_max_length = self::TITLE_MAX_LENGTH; // Herodoc doesn't support constants
        // Build menu options
        $oMenu = new MenuRepository($website->getDatabase());
        $menus = $oMenu->getAllMenus();
        $menu_options = "";
        if (count($menus) > 0) {
            $menu_options.= "<select name=\"menu_id_$id\" id=\"menu_id_$id\">\n";
            foreach ($menus as $menu) {
                $menu_options.= '<option value="' . $menu->getId() . '"';
                if ($menu->getId() == $menu_id) {
                    $menu_options.= ' selected="selected"';
                }
                $menu_options.= '>' . htmlSpecialChars($menu->getName()) . "</option>\n";
            }
            $menu_options.="</select>\n";
        } else {
            $menu_options.="<p><em>" . $website->t("errors.nothing_found") . "</em> ";
            $menu_options.='<a class="arrow" href="' . $website->getUrlPage("links") . '">' . $website->t("links.menu.add") . "</a></p>\n";
        }

        unset($menus, $oMenu, $available_menu_id, $menu_name);

        // Return form
        $returnValue.= <<<EOT
            <p>
                <label for="title_$id">{$website->t("widgets.title")}:</label><br />
                <input type="text" name="title_$id" id="title_$id" value="$title" maxlength="$title_max_length" />
            </p>
            <p>
                <label for="menu_id_$id">{$website->t("links.menu")}:</label><span class="required">*</span><br />
                
                    $menu_options
                
            </p>
EOT;

        return $returnValue;
    }

    public function parseData(Website $website, $id) {
        $data = array();
        $data["title"] = isSet($_REQUEST["title_" . $id]) ? trim($_REQUEST["title_" . $id]) : "";
        if (strLen($data["title"]) > self::TITLE_MAX_LENGTH) {
            $website->addError($website->t("widgets.title") . " " . $website->tReplaced("errors.too_long_num", self::TITLE_MAX_LENGTH));
            $data["valid"] = false;
        }
        $data["menu_id"] = isSet($_REQUEST["menu_id_" . $id]) ? (int) $_REQUEST["menu_id_" . $id] : 0;
        $oMenu = new MenuRepository($website->getDatabase());
        if ($oMenu->getMenuName($data["menu_id"]) == null) {
            $website->addError($website->t("widgets.menu") . " " . $website->t("errors.not_found"));
            $data["valid"] = false;
        }
        return $data;
    }

}

// Register itself
$this->registerWidget(new WidgetRkokLinks());