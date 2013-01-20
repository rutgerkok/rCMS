<h2><?php echo $this->t("articles.comment.add") ?></h2>
<?php
//OBJECTEN
$oAuth = new Authentication($this);
$oComments = new Comments($this, $oAuth);
$logged_in = $oAuth->check(Authentication::$USER_RANK, false);

$display_page = true;
$saved = false;
$article_id = isset($_REQUEST["id"]) ? (int) $_REQUEST["id"] : 0;
$comment = null;

// Check article id
if ($article_id == 0) {
    $this->add_error($this->t("main.article") . " " . $this->t("errors.not_found"));
    $display_page = false;
} else {
    // Try to read and save comment
    if (isset($_POST["comment"]) && $logged_in) {
        // Logged in
        $comment_body = $_POST["comment"];
        $poster = $oAuth->get_current_user();
        $comment = $oComments->make_comment(true, 0, "", "", $comment_body, $poster->get_id(), $article_id);
    } elseif (isset($_POST["comment"]) && isset($_POST["name"]) && isset($_POST["email"])) {
        // Logged out, can still comment
        $comment_body = $_POST["comment"];
        $author_name = $_POST["name"];
        $author_email = $_POST["email"];
        $comment = $oComments->make_comment(true, 0, $author_name, $author_email, $comment_body, 0, $article_id);
    }
    if ($comment != null && $this->error_count() == 0) {
        if ($oComments->save($comment)) {
            $saved = true;
        }
    }
}

//FOUTAFHANDELING
if (!$this->errorsdisplayed) {
    $this->echo_errors();
}

//EDITOR OF MELDING TONEN
if ($saved) {
    echo "<p>" . $this->t("articles.comment") . " " . $this->t("editor.is_created") . ".</p>\n\n";
    echo '<p><a href="' . $this->get_url_page("article", $article_id) . '">' . $this->t("main.ok") . '</p>';
} elseif ($display_page) {
    echo <<<EOT
        <form action="{$this->get_url_main()}" method="post">
EOT;
    $oComments->echo_editor($comment);
    echo <<<EOT
            <p>
                <input type="hidden" name="id" value="$article_id" />
                <input type="hidden" name="p" value="add_comment" />
                <input type="submit" name="submit" value="{$this->t('articles.comment.add')}" class="button" />
            </p>
        </form>
EOT;
}
?>