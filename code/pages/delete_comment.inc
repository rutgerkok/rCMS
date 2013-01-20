<h2><?php echo $this->t("editor.comment.delete") ?></h2>
<?php
//OBJECTEN
$oAuth = new Authentication($this);
$oComments = new Comments($this, $oAuth);
$logged_in = $oAuth->check(Authentication::$USER_RANK, true);
$is_moderator = $oAuth->check(Authentication::$MODERATOR_RANK, false);

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
            if (isset($_REQUEST['confirm'])) {
                // Confirmed
                if ($oComments->delete_comment($comment_id)) {
                    $text_to_display.= '<p>' . $this->t("articles.comment") . ' ' . $this->t("editor.is_deleted") . '</p>';
                    $text_to_display.= '<p><a class="arrow" href="' . $this->get_url_page("article", $oComments->get_article_id($comment)) . '">' . $this->t("main.ok") . '</a></p>';
                } else {
                    $text_to_display.= '<p>' . $this->t("articles.comment") . ' ' . $this->t("errors.is_not_removed") . '</p>';
                    $text_to_display.= '<p><a class="arrow" href="' . $this->get_url_page("article", $oComments->get_article_id($comment)) . '">' . $this->t("main.ok") . '</a></p>';
                }
            } else {
                // Show confirmation message
                $text_to_display.= '<p>' . $this->t("editor.comment.delete.confirm") . '</p>';

                //en laat daarbij de comment zien
                $comment_html = $oComments->get_comment_html($comment, false);
                $text_to_display.= $comment_html;
                $text_to_display.= '<p><a class="arrow" href="' . $this->get_url_page("delete_comment", $comment_id, array("confirm" => 1)) . '">' . $this->t("main.yes") . "</a> | \n";
                $text_to_display.= '<a class="arrow" href="' . $this->get_url_main() . '" onclick="history.back();return false">' . $this->t("main.no") . '</a></p>';
            }
        } else {
            // Not enough rights
            $text_to_display.= '<p>' . $this->t("articles.comment") . ' ' . $this->t("errors.not_removeable") . '</p>';
            $text_to_display.= '<p><a class="arrow" href="' . $this->get_url_page("article", $oComments->get_article_id($comment)) . '">' . $this->t("main.ok") . '</a></p>';
        }
    }
}

unset($oComments);

//FOUTAFHANDELING
if (!$this->errorsdisplayed) {
    $this->echo_errors();
}

//WEERGEVEN
echo $text_to_display;
