<h2><?php echo $this->t("links.menu.rename") ?></h2>
<?php
$text_to_display = '';

// AUTHENTICATION
$oAuth = new Authentication($this);
$logged_in = $oAuth->check(Authentication::$ADMIN_RANK, true);
unset($oAuth);

// LOGIC
if ($logged_in) {
    $menu_id = isset($_REQUEST["id"]) ? (int) $_REQUEST["id"] : 0;
    $oMenu = new Menus($this);
    $menu_name = $oMenu->get_menu_name($menu_id);
    if ($menu_name !== null) {
        $show_form = true;
        if (isset($_REQUEST["menu_name"])) {
            // Validate input
            $menu_name = trim($_REQUEST["menu_name"]);
            $valid = true;
            if (strlen($menu_name) < 1) {
                $this->add_error($this->t("links.menu.name") . " " . $this->t("errors.not_entered"));
                $valid = false;
            } elseif (strlen($menu_name) > Menus::MAX_MENU_NAME_LENGTH) {
                $this->add_error($this->t("links.menu.name") . " " . $this->t_replaced("errors.too_long_num", Menus::MAX_MENU_NAME_LENGTH));
                $valid = false;
            }
            if ($valid) {
                if ($oMenu->rename_menu($menu_id, $menu_name)) {
                    // Great success!
                    $show_form = false;
                    $text_to_display.="<p>" . $this->t("links.menu") . " " . $this->t("editor.is_changed") . "</p>";
                } else {
                    // Database error
                    $text_to_display.="<p><em>" . $this->t("links.menu") . " " . $this->t("errors.not_saved") . "</em></p>";
                }
            } else {
                // User error
                $text_to_display.="<p><em>" . $this->t("links.menu") . " " . $this->t("errors.not_saved") . "</em></p>";
            }
        }

        if ($show_form) {
            $menu_name_maxlength = Menus::MAX_MENU_NAME_LENGTH;
            $text_to_display.=<<<EOT
                <form method="post" action="{$this->get_url_main()}">
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
        $this->add_error($this->t("links.menu") . " " . $this->t("errors.not_found"));
    }
    
    // Add some links
    $text_to_display.= <<<EOT
        <hr />
        <p>
            <a class="arrow" href="{$this->get_url_page("links")}">{$this->t("main.links")}</a><br />
            <a class="arrow" href="{$this->get_url_page("admin")}">{$this->t("main.admin")}</a>
        </p>
EOT;
}

// DISPLAY ERRORS
if (!$this->errorsdisplayed) {
    $this->echo_errors();
}

// DISPLAY
echo $text_to_display;
?>