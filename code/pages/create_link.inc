<h2><?php echo $this->t("links.create") ?></h2>
<?php
$text_to_display = '';

// AUTHENTICATION
$oAuth = new Authentication($this);
$logged_in = $oAuth->check(Authentication::$ADMIN_RANK, true);
unset($oAuth);

// LOGIC
if ($logged_in) {
    $show_form = true;
    $max_link_text_length = Menus::MAX_LINK_TEXT_LENGTH;
    $max_url_length = Menus::MAX_URL_LENGTH;

    $oMenu = new Menus($this);
    $link_url = isset($_REQUEST["link_url"]) ? $_REQUEST["link_url"] : "http://";
    $menu_id = isset($_REQUEST["id"]) ? (int) $_REQUEST["id"] : 0;
    $link_text = isset($_REQUEST["link_text"]) ? $_REQUEST["link_text"] : "";
    if (isset($_REQUEST["submit"])) {
        $valid = true;
        if (!Validate::url($link_url)) {
            $valid = false;
            $this->add_error($this->t("links.url") . " " . Validate::get_last_error($this));
        }
        if (!Validate::link_text($link_text)) {
            $valid = false;
            $this->add_error($this->t("links.text") . " " . Validate::get_last_error($this));
        }
        if (!$oMenu->get_menu_name($menu_id)) {
            $valid = false;
            $this->add_error($this->t("links.menu") . " " . $this->t("errors.not_found"));
        }
        if ($valid) {
            // Save
            if ($oMenu->add_link($menu_id, $link_url, $link_text)) {
                $show_form = false; // Don't show form on success

                $text_to_display.= "<p>" . $this->t("main.link") . " " . $this->t("editor.is_created") . "</p>";
                $text_to_display.= '<p><a class="arrow" href="' . $this->get_url_page("create_link", $menu_id) . '">';
                $text_to_display.= $this->t("links.create_another") . "</a></p>";
            } else {
                // System error
                $text_to_display.= "<p><em>" . $this->t("main.link") . " " . $this->t("errors.is_not_created") . "</em></p>";
            }
        } else {
            // Invalid input
            $text_to_display.= "<p><em>" . $this->t("main.link") . " " . $this->t("errors.is_not_created") . "</em></p>";
        }
    }
    if ($show_form) {
        // Show form
        $link_url = htmlentities($link_url);
        $link_text = htmlentities($link_text);
        $text_to_display.= <<<EOT
            <p>
                {$this->t("main.fields_required")}
            </p>
            <form action="{$this->get_url_main()}" method="post">
                <p>
                    <label for="link_url">
                        {$this->t("links.url")}:<span class="required">*</span>
                    </label><br />
                    <input type="url" size="50" id="link_url" name="link_url" maxlength="$max_url_length" value="$link_url" />
                </p>
                    
                <p>
                    <label for="link_text">
                        {$this->t("links.text")}:<span class="required">*</span>
                    </label><br />
                    <input type="text" size="50" id="link_text" name="link_text" maxlength="$max_link_text_length" value="$link_text" />
                </p>
                
                <p>
                    <label for="menu_id">
                        {$this->t("links.menu")}:<span class="required">*</span>
                    </label><br />
                    <select name="id" id="menu_id">
EOT;
        foreach ($oMenu->get_menus() as $id=>$name) {
            $text_to_display.= '<option value="'.$id.'"';
            if($id == $menu_id) {
                // Currently selected menu
                $text_to_display.= ' selected="selected"';
            }
            $text_to_display.= '>'. $name . "</option>\n";
        }
        $text_to_display .= <<<EOT
                    </select>
                </p>
                
                <p>
                    <input type="submit" class="button" name="submit" value="{$this->t("editor.save")}" />
                </p>
                
                <input type="hidden" name="p" value="create_link" />
            </form>
EOT;
    }

    // Add some links
    $text_to_display.= <<<EOT
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