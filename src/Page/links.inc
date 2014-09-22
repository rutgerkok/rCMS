<?php namespace Rcms\Core; ?>

<h2><?php echo $this->t("main.links") ?></h2>
<?php
$textToDisplay = '';

// AUTHENTICATION
$oAuth = new Authentication($this);
$loggedIn = $oAuth->check(Authentication::$ADMIN_RANK, true);
unset($oAuth);

// LOGIC
if ($loggedIn) {
    $oLinks = new LinkRepository($this);
    $oMenus = new MenuRepository($this->getDatabase());

    // Handle new menus
    if (isSet($_REQUEST["new_menu_name"]) && !empty($_REQUEST["new_menu_name"])) {
        $name = trim($_REQUEST["new_menu_name"]);
        if (strLen($name) > LinkRepository::MAX_MENU_NAME_LENGTH) {
            $this->addError($this->t("links.menu.name") . " " . $this->tReplaced("errors.too_long_num", LinkRepository::MAX_MENU_NAME_LENGTH));
            $textToDisplay.="<p><em>" . $this->t("links.menu") . " " . $this->t("errors.is_not_created") . "</em></p>";
        } else {
            if ($oMenus->addMenu($name)) {
                $textToDisplay.="<p><em>" . $this->t("links.menu") . " " . $this->t("editor.is_created") . "</em></p>";
            } else {
                $textToDisplay.="<p><em>" . $this->t("links.menu") . " " . $this->t("errors.is_not_created") . "</em></p>";
            }
        }
    }

    // Echo all menus
    $menus = $oMenus->getAllMenus();
    foreach ($menus as $menu) {
        $linksInMenu = $oLinks->getLinksByMenu($menu->getId());
        if (count($linksInMenu) > 0) {
            $links_html = $oLinks->getAsHtml($linksInMenu, false, true);
        } else {
            $links_html = "<li><em>" . $this->t("errors.nothing_found") . "</em></li>";
        }
        $menuName = htmlSpecialChars($menu->getName());
        $textToDisplay.= <<<EOT
        <h3 class="notable">$menuName</h3>
        <p>
            <a class="arrow" href="{$this->getUrlPage("create_link", $menu->getId())}">
                {$this->t("links.create")}
            </a>
            <a class="arrow" href="{$this->getUrlPage("rename_menu", $menu->getId())}">
                {$this->t("links.menu.rename")}
            </a>
            <a class="arrow" href="{$this->getUrlPage("delete_menu", $menu->getId())}">
                {$this->t("links.menu.delete")}
            </a>
        </p>
        <ul class="linklist">
            $links_html
        </ul>
EOT;
    }
    if (count($menus) == 0) {
        $textToDisplay.= "<p><em>" . $this->t("errors.nothing_found") . "</em></p>\n";
    }

    // Add box for new menu
    $menu_name_maxlength = LinkRepository::MAX_MENU_NAME_LENGTH;
    $textToDisplay.= <<<EOT
        <form method="post" action="{$this->getUrlMain()}">
            <h3 class="notable">{$this->t("links.menu.add")}</h3>
            <p>              
                <input type="text" name="new_menu_name" size="20" maxlength="$menu_name_maxlength" />
                <input type="hidden" name="p" value="links" />
                <input class="button" type="submit" value="{$this->t("editor.save")}" />
            </p>
        </form>
EOT;
    // Add link
    $textToDisplay.= <<<EOT
        <p>
            <a class="arrow" href="{$this->getUrlPage("admin")}">{$this->t("main.admin")}</a>
        </p>
EOT;
}

// DISPLAY ERRORS
if (!$this->errorsDisplayed) {
    $this->echoErrors();
}

// DISPLAY
echo $textToDisplay;
?>