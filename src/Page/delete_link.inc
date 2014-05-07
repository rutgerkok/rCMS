<?php namespace Rcms\Core; ?>

<h2><?php echo $this->t("links.delete") ?></h2>
<?php
$textToDisplay = '';


// AUTHENTICATION
$oAuth = new Authentication($this);
$loggedIn = $oAuth->check(Authentication::$ADMIN_RANK, true);
unset($oAuth);

if ($loggedIn) {
    $oMenu = new Menus($this);
    $id = isSet($_REQUEST["id"]) ? (int) $_REQUEST["id"] : 0;
    $link = $oMenu->getLink($id);
    if ($link != null) {
        if (isSet($_REQUEST["confirm"])) {
            // Deletion confirmed
            if ($oMenu->deleteLink($id)) {
                // Deleted
                $textToDisplay.= "<p>" . $this->t("main.link") . " " . $this->t("editor.is_deleted") . "</p>\n";
            } else {
                // Database error
                $textToDisplay.= "<p>" . $this->t("main.link") . " " . $this->t("editor.is_not_removed") . "</p>\n";
            }
        } else {
            // Confirm deletion
            $textToDisplay.= "<p>" . $this->tReplaced("links.delete.confirm", $link["text"]) . "</p>";

            $textToDisplay.= '<p><a class="arrow" href="' . $this->getUrlPage("delete_link", $id, array("confirm" => 1)) . '">';
            $textToDisplay.= $this->t("main.yes") . "</a>\n";
            $textToDisplay.= '<a class="arrow" href="' . $this->getUrlPage("links") . '">';
            $textToDisplay.= $this->t("main.no") . "</a></p>\n";

            $links = $oMenu->getLinksByMenu($id);
        }
    } else {
        // Menu doens't exist
        $this->addError($this->t("main.link") . " " . $this->t("errors.not_found"));
    }

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