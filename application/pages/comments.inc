<h2><?php echo $this->t("comments.comments") ?></h2>
<?php

$textToDisplay = "";

// Check for login
$oAuth = new Authentication($this);
$loggedIn = $oAuth->check(Authentication::$USER_RANK, true);

if ($loggedIn) {
    // Load the latest comments
    $oComments = new Comments($this);
    $latest_comments = $oComments->getCommentsLatest();
    $textToDisplay.= "<p>" . $this->t("comments.view_latest_on_site") . "</p>";
    $displayed_a_comment = false;

    // Display commments
    foreach ($latest_comments as $comment) {
        $displayed_a_comment = true;
        $textToDisplay.= $oComments->getCommentHTML($comment, true);
        // Add a link to context
        $textToDisplay.= '<p><a class="arrow" href="';
        $textToDisplay.= $this->getUrlPage("article", $oComments->getArticleId($comment));
        $textToDisplay.= "#comment-" . $oComments->getCommentId($comment);
        $textToDisplay.= '">' . $this->t("comments.view_context") . "</a></p>";
    }

    // Display notice if no comments were found
    if (!$displayed_a_comment) {

        $textToDisplay.= "<p><em>" . $this->t("comments.no_comments_found") . "</em></p>";
    }

    // Links
    $textToDisplay.= '<p><a class="arrow" href="' . $this->getUrlPage('account') . '">' . $this->t("main.account_management") . "</a>\n";
    if ($this->isLoggedInAsStaff(true)) {
        $textToDisplay.= '<br /><a class="arrow" href="' . $this->getUrlPage('admin') . '">' . $this->t("main.admin") . '</a>';
    }
    $textToDisplay.="</p>\n";
}

// Display errors
if (!$this->errorsDisplayed) {
    $this->echoErrors();
}

// Display page
echo $textToDisplay;
?>