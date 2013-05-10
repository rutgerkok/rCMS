<h2><?php echo $this->t("links.delete") ?></h2>
<?php
$text_to_display = '';


// AUTHENTICATION
$oAuth = new Authentication($this);
$logged_in = $oAuth->check(Authentication::$ADMIN_RANK, true);
unset($oAuth);

if ($logged_in) {
    $oMenu = new Menus($this);
    $menu_id = isset($_REQUEST["id"]) ? (int) $_REQUEST["id"] : 0;
    $menu_name = $oMenu->get_menu_name($menu_id);
    if ($menu_name != null) {
        if (isset($_REQUEST["confirm"])) {
            // Deletion confirmed
            if($oMenu->delete_menu($menu_id)) {
                // Deleted
                $text_to_display.= "<p>" . $this->t("links.menu") . " " . $this->t("editor.is_deleted") . "</p>";
            } else {
                // Database error
                $text_to_display.= "<p><em>" . $this->t("links.menu") . " " . $this->t("errors.is_not_removed") . "</em></p>";
            }
        } else {
            // Confirm deletion
            $link_count = count($oMenu->get_links_menu($menu_id));
            $text_to_display.= <<<EOT
                <p>{$this->t("links.menu.delete.confirm")}</p>
                <p>
                    <a class="arrow" href="{$this->get_url_page("delete_menu", $menu_id, array("confirm" => "1"))}">
                        {$this->t("main.yes")}
                    </a>
                    <a class="arrow" href="{$this->get_url_page("links")}">
                        {$this->t("main.no")}
                    </a>
                </p>
                <table>
                    <tr>
                        <th>{$this->t("links.menu.name")}</th>
                        <td>$menu_name</td>
                    </tr><tr>
                        <th>{$this->t("main.links")}</th>
                        <td>$link_count</td>
                    </tr>
                </table>
                
EOT;
        }
    } else {
        // Menu doens't exist
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

// ERROR HANDLING
if (!$this->errorsdisplayed) {
    $this->echo_errors();
}

// DISPLAY
echo $text_to_display;
?>