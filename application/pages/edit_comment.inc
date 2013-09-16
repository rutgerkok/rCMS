<h2><?php echo $this->t("editor.comment.edit") ?></h2>
<?php
//OBJECTEN
$oAuth = new Authentication($this);
$oComments = new Comments($this, $oAuth);
$loggedIn = $oAuth->check(Authentication::$USER_RANK, true);
$is_moderator = $oAuth->check(Authentication::$MODERATOR_RANK, false);
$display_form = false;

//INHOUD VOOR PAGINA
$textToDisplay = '';

if ($loggedIn) {
    
    $comment_id = isSet($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
    $comment = $oComments->get_comment($comment_id);
    if ($comment == null) {
        // Comment not found
        $textToDisplay.= '<p><a class="arrow" href="' . $this->getUrlMain() . '">' . $this->t("main.home") . '</a></p>';
    } else {
        if ($is_moderator || $oComments->get_user_id($comment) == $oAuth->getCurrentUser()->getId()) {
            // Enough rights
            $display_form = true;
            // Try to read and save comment
            if (isSet($_POST["comment"])) {
                // Sent form
                $poster = $oAuth->getCurrentUser();
                $comment = $oComments->set_body($comment, $_POST["comment"]);
                if ($this->getErrorCount() === 0 && $oComments->save($comment)) {
                    $display_form = false;
                    
                    $textToDisplay.="<p>" . $this->t("comments.comment") . " " . $this->t("editor.is_edited") . "</p>\n";
                    $textToDisplay.= '<p><a class="arrow" href="' . $this->getUrlPage("article", $oComments->get_article_id($comment)) . '">' . $this->t("main.ok") . '</a></p>';
                }
            }
        } else {
            // Not enough rights
            $textToDisplay.= '<p>' . $this->t("comments.comment") . ' ' . $this->t("errors.not_editable") . '</p>';
            $textToDisplay.= '<p><a class="arrow" href="' . $this->getUrlPage("article", $oComments->get_article_id($comment)) . '">' . $this->t("main.ok") . '</a></p>';
        }
    }
}

//FOUTAFHANDELING
if (!$this->errorsDisplayed) {
    $this->echoErrors();
}

//EDITOR EN/OF MELDING TONEN
if ($loggedIn) {
    echo $textToDisplay;
    if ($display_form) {
        echo <<<EOT
            <form action="{$this->getUrlMain()}" method="post">
EOT;
        $oComments->echo_editor($comment);
        echo <<<EOT
                <p>
                    <input type="hidden" name="id" value="$comment_id" />
                    <input type="hidden" name="p" value="edit_comment" />
                    <input type="submit" name="submit" value="{$this->t('editor.save')}" class="button" />
                </p>
            </form>
EOT;
    }
}