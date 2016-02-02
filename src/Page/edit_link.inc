<?php namespace Rcms\Core;

use Zend\Diactoros\Uri;
?>

<h2><?php echo $this->t("links.edit") ?></h2>
<?php
$textToDisplay = '';

// AUTHENTICATION
$oAuth = new Authentication($this);
$loggedIn = $oAuth->check(Authentication::RANK_ADMIN, true);
unset($oAuth);

// LOGIC
if ($loggedIn) {
    $link_id = isSet($_REQUEST["id"]) ? (int) $_REQUEST["id"] : 0;
    $oMenus = new LinkRepository($this);
    $link = $oMenus->getLink($link_id);

    if ($link) {
        // Valid link id
        $link_url = $link->getUrl();
        $link_text = $link->getText();

        if (isSet($_REQUEST["link_url"]) && isSet($_REQUEST["link_text"])) {
            // Sent, validate
            $link_url = $_REQUEST["link_url"];
            $link_text = $_REQUEST["link_text"];

            $valid = true;

            if (!Validate::url($link_url)) {
                $valid = false;
                $this->addError($this->t("links.url") . " " . Validate::getLastError($this));
            }
            if (!Validate::nameOfLink($link_text)) {
                $valid = false;
                $this->addError($this->t("links.text") . " " . Validate::getLastError($this));
            }
            if ($valid) {
                // Validated, update
                if ($oMenus->updateLink($link_id, new Uri($link_url), $link_text)) {
                    $textToDisplay.="<p><em>" . $this->t("main.link") . " " . $this->t("editor.is_edited") . "</em></p>";
                } else {
                    // System error
                    $textToDisplay.="<p><em>" . $this->t("main.link") . " " . $this->t("errors.not_saved") . "</em></p>";
                }
            } else {
                // Invalid input
                $textToDisplay.="<p><em>" . $this->t("main.link") . " " . $this->t("errors.not_saved") . "</em></p>";
            }
        }
        // Put constants in variables (required for heredoc)
        $max_url_length = LinkRepository::MAX_URL_LENGTH;
        $max_link_text_length = LinkRepository::MAX_LINK_TEXT_LENGTH;
        // Show a form
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
                                <input type="submit" class="button" name="submit" value="{$this->t("editor.save")}" />
                            </p>

                            <input type="hidden" name="p" value="edit_link" />
                            <input type="hidden" name="id" value="$link_id" />
                        </form>
EOT;
    } else {
        // Link not found
        $this->addError($this->t("main.link") . " " . $this->t("errors.not_found"));
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