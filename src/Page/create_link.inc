<?php namespace Rcms\Core; ?>

<h2><?php echo $this->t("links.create") ?></h2>
<?php
$textToDisplay = '';

// AUTHENTICATION
$oAuth = new Authentication($this);
$loggedIn = $oAuth->check(Authentication::RANK_ADMIN, true);
unset($oAuth);

// LOGIC
if ($loggedIn) {
    $show_form = true;
    $max_link_text_length = LinkRepository::MAX_LINK_TEXT_LENGTH;
    $max_url_length = LinkRepository::MAX_URL_LENGTH;

    $oMenus = new LinkRepository($this);
    $menus = new MenuRepository($this->getDatabase());
    $link_url = isSet($_REQUEST["link_url"]) ? $_REQUEST["link_url"] : "http://";
    $menu_id = isSet($_REQUEST["id"]) ? (int) $_REQUEST["id"] : 0;
    $link_text = isSet($_REQUEST["link_text"]) ? $_REQUEST["link_text"] : "";
    if (isSet($_REQUEST["submit"])) {
        $valid = true;
        if (!Validate::url($link_url)) {
            $valid = false;
            $this->addError($this->t("links.url") . " " . Validate::getLastError($this));
        }
        if (!Validate::nameOfLink($link_text)) {
            $valid = false;
            $this->addError($this->t("links.text") . " " . Validate::getLastError($this));
        }
        if (!$menus->exists($menu_id)) {
            $valid = false;
            $this->addError($this->t("links.menu") . " " . $this->t("errors.not_found"));
        }
        if ($valid) {
            // Save
            if ($oMenus->addLink($menu_id, $link_url, $link_text)) {
                $show_form = false; // Don't show form on success

                $textToDisplay.= "<p>" . $this->t("main.link") . " " . $this->t("editor.is_created") . "</p>";
                $textToDisplay.= '<p><a class="arrow" href="' . $this->getUrlPage("create_link", $menu_id) . '">';
                $textToDisplay.= $this->t("links.create_another") . "</a></p>";
            } else {
                // System error
                $textToDisplay.= "<p><em>" . $this->t("main.link") . " " . $this->t("errors.is_not_created") . "</em></p>";
            }
        } else {
            // Invalid input
            $textToDisplay.= "<p><em>" . $this->t("main.link") . " " . $this->t("errors.is_not_created") . "</em></p>";
        }
    }
    if ($show_form) {
        // Show form
        $link_url = htmlSpecialChars($link_url);
        $link_text = htmlSpecialChars($link_text);
        $textToDisplay.= <<<EOT
            <p>
                {$this->t("main.fields_required")}
            </p>
            <form action="{$this->getUrlMain()}" method="post">
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
        foreach ($menus->getAllMenus() as $menu) {
            $textToDisplay.= '<option value="'.$menu->getId().'"';
            if($menu->getId() == $menu_id) {
                // Currently selected menu
                $textToDisplay.= ' selected="selected"';
            }
            $textToDisplay.= '>'. htmlSpecialChars($menu->getName()) . "</option>\n";
        }
        $textToDisplay .= <<<EOT
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
    $textToDisplay.= <<<EOT
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