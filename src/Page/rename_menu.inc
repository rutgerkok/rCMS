<?php namespace Rcms\Core; ?>

<h2><?php echo $this->t("links.menu.rename") ?></h2>
<?php
$textToDisplay = '';

// AUTHENTICATION
$oAuth = new Authentication($this);
$loggedIn = $oAuth->check(Authentication::RANK_ADMIN, true);
unset($oAuth);

// LOGIC
if ($loggedIn) {
    $menu_id = isSet($_REQUEST["id"]) ? (int) $_REQUEST["id"] : 0;
    $oMenus = new MenuRepository($this->getDatabase());
    $menu_name = $oMenus->getMenuName($menu_id);
    if ($menu_name !== null) {
        $show_form = true;
        if (isSet($_REQUEST["menu_name"])) {
            // Validate input
            $menu_name = trim($_REQUEST["menu_name"]);
            $valid = true;
            if (strLen($menu_name) < 1) {
                $this->addError($this->t("links.menu.name") . " " . $this->t("errors.not_entered"));
                $valid = false;
            } elseif (strLen($menu_name) > MenuRepository::MAX_MENU_NAME_LENGTH) {
                $this->addError($this->t("links.menu.name") . " " . $this->tReplaced("errors.too_long_num", MenuRepository::MAX_MENU_NAME_LENGTH));
                $valid = false;
            }
            if ($valid) {
                if ($oMenus->renameMenu($menu_id, $menu_name)) {
                    // Great success!
                    $show_form = false;
                    $textToDisplay.="<p>" . $this->t("links.menu") . " " . $this->t("editor.is_changed") . "</p>";
                } else {
                    // Database error
                    $textToDisplay.="<p><em>" . $this->t("links.menu") . " " . $this->t("errors.not_saved") . "</em></p>";
                }
            } else {
                // User error
                $textToDisplay.="<p><em>" . $this->t("links.menu") . " " . $this->t("errors.not_saved") . "</em></p>";
            }
        }

        if ($show_form) {
            $menu_name_maxlength = MenuRepository::MAX_MENU_NAME_LENGTH;
            $textToDisplay.=<<<EOT
                <form method="post" action="{$this->getUrlMain()}">
                    <p>
                        {$this->t("main.fields_required")}
                    </p>
                    <p>
                        <label for="menu_name">
                            {$this->t("links.menu.name")}: <span class="required">*</span>
                        </label> <br />
                        <input type="text" name="menu_name" id="menu_name" value="$menu_name" size="20" maxlength="$menu_name_maxlength" />
                    </p>
                    <p>
                        <input type="hidden" name="p" value="rename_menu" />
                        <input type="hidden" name="id" value="$menu_id" />
                        <input class="button" type="submit" value="{$this->t("editor.save")}" />
                    </p>
                </form>
EOT;
        }
    } else {
        // Menu not found
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

// DISPLAY ERRORS
if (!$this->errorsDisplayed) {
    $this->echoErrors();
}

// DISPLAY
echo $textToDisplay;
?>