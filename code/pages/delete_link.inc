<h2><?php echo $this->t("links.delete") ?></h2>
<?php
$text_to_display = '';


// AUTHENTICATION
$oAuth = new Authentication($this);
$logged_in = $oAuth->check(Authentication::$ADMIN_RANK, true);
unset($oAuth);

if ($logged_in) {
    $oMenu = new Menus($this);
    $id = isset($_REQUEST["id"]) ? (int) $_REQUEST["id"] : 0;
    $link = $oMenu->get_link($id);
    if ($link != null) {
        if (isset($_REQUEST["confirm"])) {
            // Deletion confirmed
            if ($oMenu->delete_link($id)) {
                // Deleted
                $text_to_display.= "<p>" . $this->t("main.link") . " " . $this->t("editor.is_deleted") . "</p>\n";
            } else {
                // Database error
                $text_to_display.= "<p>" . $this->t("main.link") . " " . $this->t("editor.is_not_removed") . "</p>\n";
            }
        } else {
            // Confirm deletion
            $text_to_display.= "<p>" . $this->t_replaced("links.delete.confirm", $link["text"]) . "</p>";

            $text_to_display.= '<p><a class="arrow" href="' . $this->get_url_page("delete_link", $id, array("confirm" => 1)) . '">';
            $text_to_display.= $this->t("main.yes") . "</a>\n";
            $text_to_display.= '<a class="arrow" href="' . $this->get_url_page("links") . '">';
            $text_to_display.= $this->t("main.no") . "</a></p>\n";

            $links = $oMenu->get_links_menu($id);
        }
    } else {
        // Menu doens't exist
        $this->add_error($this->t("main.link") . " " . $this->t("errors.not_found"));
    }

    $text_to_display.= <<<EOT
        <hr />
        <p>
            <a class="arrow" href="{$this->get_url_page("links")}">{$this->t("main.links")}</a><br />
            <a class="arrow" href="{$this->get_url_page("admin")}">{$this->t("main.admin")}</a>
        </p>
EOT;
}

// ERROR HANDLING
if (!$this->errorsdisplayed) {
    $this->echo_errors();
}

// DISPLAY
echo $text_to_display;
?>