<h2><?php echo $this->t("editor.comment.edit") ?></h2>
<?php
//OBJECTEN
$oAuth = new Authentication($this);
$oComments = new Comments($this, $oAuth);
$logged_in = $oAuth->check(Authentication::$USER_RANK, true);
$is_moderator = $oAuth->check(Authentication::$MODERATOR_RANK, false);
$display_form = false;

//INHOUD VOOR PAGINA
$text_to_display = '';

if ($logged_in) {
    
    $comment_id = isset($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
    $comment = $oComments->get_comment($comment_id);
    if ($comment == null) {
        // Comment not found
        $text_to_display.= '<p><a class="arrow" href="' . $this->get_url_main() . '">' . $this->t("main.home") . '</a></p>';
    } else {
        if ($is_moderator || $oComments->get_user_id($comment) == $oAuth->get_current_user()->get_id()) {
            // Enough rights
            $display_form = true;
            // Try to read and save comment
            if (isset($_POST["comment"])) {
                // Sent form
                $poster = $oAuth->get_current_user();
                $comment = $oComments->set_body($comment, $_POST["comment"]);
                if ($this->error_count() === 0 && $oComments->save($comment)) {
                    $display_form = false;
                    
                    $text_to_display.="<p>" . $this->t("comments.comment") . " " . $this->t("editor.is_edited") . "</p>\n";
                    $text_to_display.= '<p><a class="arrow" href="' . $this->get_url_page("article", $oComments->get_article_id($comment)) . '">' . $this->t("main.ok") . '</a></p>';
                }
            }
        } else {
            // Not enough rights
            $text_to_display.= '<p>' . $this->t("comments.comment") . ' ' . $this->t("errors.not_editable") . '</p>';
            $text_to_display.= '<p><a class="arrow" href="' . $this->get_url_page("article", $oComments->get_article_id($comment)) . '">' . $this->t("main.ok") . '</a></p>';
        }
    }
}

//FOUTAFHANDELING
if (!$this->errorsdisplayed) {
    $this->echo_errors();
}

//EDITOR EN/OF MELDING TONEN
if ($logged_in) {
    echo $text_to_display;
    if ($display_form) {
        echo <<<EOT
            <form action="{$this->get_url_main()}" method="post">
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