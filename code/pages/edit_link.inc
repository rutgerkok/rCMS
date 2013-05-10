<h2><?php echo $this->t("links.edit") ?></h2>
<?php
$text_to_display = '';

// AUTHENTICATION
$oAuth = new Authentication($this);
$logged_in = $oAuth->check(Authentication::$ADMIN_RANK, true);
unset($oAuth);

// LOGIC
if ($logged_in) {
    $link_id = isset($_REQUEST["id"]) ? (int) $_REQUEST["id"] : 0;
    $oMenu = new Menus($this);
    $link = $oMenu->get_link($link_id);

    if ($link) {
        // Valid link id
        $link_url = $link["url"];
        $link_text = $link["text"];

        if (isset($_REQUEST["link_url"]) && isset($_REQUEST["link_text"])) {
            // Sent, validate
            $link_url = $_REQUEST["link_url"];
            $link_text = $_REQUEST["link_text"];

            $valid = true;

            if (!Validate::url($link_url)) {
                $valid = false;
                $this->add_error($this->t("links.url") . " " . Validate::get_last_error($this));
            }
            if (!Validate::link_text($link_text)) {
                $valid = false;
                $this->add_error($this->t("links.text") . " " . Validate::get_last_error($this));
            }
            if ($valid) {
                // Validated, update
                if ($oMenu->update_link($link_id, $link_url, $link_text)) {
                    $text_to_display.="<p><em>" . $this->t("main.link") . " " . $this->t("editor.is_edited") . "</em></p>";
                } else {
                    // System error
                    $text_to_display.="<p><em>" . $this->t("main.link") . " " . $this->t("errors.not_saved") . "</em></p>";
                }
            } else {
                // Invalid input
                $text_to_display.="<p><em>" . $this->t("main.link") . " " . $this->t("errors.not_saved") . "</em></p>";
            }
        }
        // Put constants in variables (required for heredoc)
        $max_url_length = Menus::MAX_URL_LENGTH;
        $max_link_text_length = Menus::MAX_LINK_TEXT_LENGTH;
        // Show a form
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
                                <input type="submit" class="button" name="submit" value="{$this->t("editor.save")}" />
                            </p>

                            <input type="hidden" name="p" value="edit_link" />
                            <input type="hidden" name="id" value="$link_id" />
                        </form>
EOT;
    } else {
        // Link not found
        $this->add_error($this->t("main.link") . " " . $this->t("errors.not_found"));
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