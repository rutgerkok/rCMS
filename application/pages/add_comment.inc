<h2><?php echo $this->t("comments.add") ?></h2>
<?php
//OBJECTEN
$oAuth = new Authentication($this);
$oComments = new Comments($this, $oAuth);
$loggedIn = $oAuth->check(Authentication::$USER_RANK, false);

$display_page = true;
$saved = false;
$article_id = isSet($_REQUEST["id"]) ? (int) $_REQUEST["id"] : 0;
$comment = null;

// Check article id
if ($article_id == 0) {
    $this->addError($this->t("main.article") . " " . $this->t("errors.not_found"));
    $display_page = false;
} else {
    // Try to read and save comment
    if (isSet($_POST["comment"]) && $loggedIn) {
        // Logged in
        $comment_body = $_POST["comment"];
        $poster = $oAuth->getCurrentUser();
        $comment = $oComments->makeComment(true, 0, "", "", $comment_body, $poster->getId(), $article_id);
    } elseif (isSet($_POST["comment"]) && isSet($_POST["name"]) && isSet($_POST["email"])) {
        // Logged out, can still comment
        $comment_body = $_POST["comment"];
        $author_name = $_POST["name"];
        $author_email = $_POST["email"];
        $comment = $oComments->makeComment(true, 0, $author_name, $author_email, $comment_body, 0, $article_id);
    }
    if ($comment != null && $this->getErrorCount() == 0) {
        if ($oComments->save($comment)) {
            $saved = true;
        }
    }
}

//FOUTAFHANDELING
if (!$this->errorsDisplayed) {
    $this->echoErrors();
}

//EDITOR OF MELDING TONEN
if ($saved) {
    echo "<p>" . $this->t("comments.comment") . " " . $this->t("editor.is_created") . ".</p>\n\n";
    echo '<p><a href="' . $this->getUrlPage("article", $article_id) . '">' . $this->t("main.ok") . '</p>';
} elseif ($display_page) {
    echo <<<EOT
        <form action="{$this->getUrlMain()}" method="post">
EOT;
    $oComments->echoEditor($comment);
    echo <<<EOT
            <p>
                <input type="hidden" name="id" value="$article_id" />
                <input type="hidden" name="p" value="add_comment" />
                <input type="submit" name="submit" value="{$this->t('comments.add')}" class="button primary_button" />
                <a href="{$this->getUrlPage("article", $article_id)}" class="button">{$this->t("editor.quit")}</a>
            </p>
        </form>
EOT;
}
?>