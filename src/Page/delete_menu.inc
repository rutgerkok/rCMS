<?php namespace Rcms\Core; ?>

<h2><?php echo $this->t("links.delete") ?></h2>
<?php
$textToDisplay = '';


// AUTHENTICATION
$oAuth = new Authentication($this);
$loggedIn = $oAuth->check(Authentication::RANK_ADMIN, true);
unset($oAuth);

if ($loggedIn) {
    $oMenus = new LinkRepository($this);
    $menu_id = isSet($_REQUEST["id"]) ? (int) $_REQUEST["id"] : 0;
    $menu_name = $oMenus->getMenuName($menu_id);
    if ($menu_name != null) {
        if (isSet($_REQUEST["confirm"])) {
            // Deletion confirmed
            if($oMenus->deleteMenu($menu_id)) {
                // Deleted
                $textToDisplay.= "<p>" . $this->t("links.menu") . " " . $this->t("editor.is_deleted") . "</p>";
            } else {
                // Database error
                $textToDisplay.= "<p><em>" . $this->t("links.menu") . " " . $this->t("errors.is_not_removed") . "</em></p>";
            }
        } else {
            // Confirm deletion
            $linkCount = $oMenus->getLinkCountByMenu($menu_id);
            $textToDisplay.= <<<EOT
                <p>{$this->t("links.menu.delete.confirm")}</p>
                <p>
                    <a class="arrow" href="{$this->getUrlPage("delete_menu", $menu_id, array("confirm" => "1"))}">
                        {$this->t("main.yes")}
                    </a>
                    <a class="arrow" href="{$this->getUrlPage("links")}">
                        {$this->t("main.no")}
                    </a>
                </p>
                <table>
                    <tr>
                        <th>{$this->t("links.menu.name")}</th>
                        <td>$menu_name</td>
                    </tr><tr>
                        <th>{$this->t("main.links")}</th>
                        <td>$linkCount</td>
                    </tr>
                </table>
                
EOT;
        }
    } else {
        // Menu doens't exist
        $this->addError($this->t("links.menu") . " " . $this->t("errors.not_found"));
    }
    
    // Add some links
    $textToDisplay.= <<<EOT
        <hr />
        <p>
            <a class="arrow" href="{$this->getUrlPage("links")}">{$this->t("main.links")}</a><br />
            <a class="arrow" href="{$this->getUrlPage("admin")}">{$this->t("main.admin")}</a>
        </p>
EOT;
}

// ERROR HANDLING
if (!$this->errorsDisplayed) {
    $this->echoErrors();
}

// DISPLAY
echo $textToDisplay;
?>