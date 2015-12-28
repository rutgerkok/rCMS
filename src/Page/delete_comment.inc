<?php
namespace Rcms\Core;

use Rcms\Page\View\CommentsTreeView;
?>

<h2><?php echo $this->t("comments.delete") ?></h2>
<?php
//OBJECTEN
$oAuth = new Authentication($this);
$oComments = new CommentRepository($this, $oAuth);
$loggedIn = $oAuth->check(Authentication::RANK_USER, true);
$is_moderator = $this->isLoggedInAsStaff();

//INHOUD VOOR PAGINA
$textToDisplay = '';

if ($loggedIn) {

    $comment_id = isSet($_REQUEST['id']) ? (int) $_REQUEST['id'] : 0;
    $comment = $oComments->getComment($comment_id);
    if ($comment == null) {
        // Comment not found
        $textToDisplay.= '<p><a class="arrow" href="' . $this->getUrlMain() . '">' . $this->t("main.home") . '</a></p>';
    } else {
        if ($is_moderator || $oComments->getUserId($comment) == $oAuth->getCurrentUser()->getId()) {
            // Enough rights
            if (isSet($_REQUEST['confirm'])) {
                // Confirmed
                if ($oComments->deleteComment($comment_id)) {
                    $textToDisplay.= '<p>' . $this->t("comments.comment") . ' ' . $this->t("editor.is_deleted") . '</p>';
                    $textToDisplay.= '<p><a class="arrow" href="' . $this->getUrlPage("article", $oComments->getArticleId($comment)) . '">' . $this->t("main.ok") . '</a></p>';
                } else {
                    $textToDisplay.= '<p>' . $this->t("comments.comment") . ' ' . $this->t("errors.is_not_removed") . '</p>';
                    $textToDisplay.= '<p><a class="arrow" href="' . $this->getUrlPage("article", $oComments->getArticleId($comment)) . '">' . $this->t("main.ok") . '</a></p>';
                }
            } else {
                // Show confirmation message
                $textToDisplay.= '<p>' . $this->t("comments.delete.confirm") . '</p>';

                //en laat daarbij de comment zien
                $commentView = new CommentsTreeView($this->getText(), array($comment), true);
                $comment_html = $commentView->getText();
                $textToDisplay.= $comment_html;
                $textToDisplay.= '<p><a class="arrow" href="' . $this->getUrlPage("delete_comment", $comment_id, array("confirm" => 1)) . '">' . $this->t("main.yes") . "</a> | \n";
                $textToDisplay.= '<a class="arrow" href="' . $this->getUrlMain() . '" onclick="history.back();return false">' . $this->t("main.no") . '</a></p>';
            }
        } else {
            // Not enough rights
            $textToDisplay.= '<p>' . $this->t("comments.comment") . ' ' . $this->t("errors.not_removeable") . '</p>';
            $textToDisplay.= '<p><a class="arrow" href="' . $this->getUrlPage("article", $oComments->getArticleId($comment)) . '">' . $this->t("main.ok") . '</a></p>';
        }
    }
}

unset($oComments);

//FOUTAFHANDELING
if (!$this->errorsDisplayed) {
    $this->echoErrors();
}

//WEERGEVEN
echo $textToDisplay;
