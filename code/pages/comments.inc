<h2><?php echo $this->t("comments.comments") ?></h2>
<?php

$text_to_display = "";

// Check for login
$oAuth = new Authentication($this);
$logged_in = $oAuth->check(Authentication::$USER_RANK, true);

if ($logged_in) {
    // Load the latest comments
    $oComments = new Comments($this, $oAuth);
    $latest_comments = $oComments->get_comments_latest();
    $text_to_display.= "<p>" . $this->t("comments.view_latest_on_site") . "</p>";
    $displayed_a_comment = false;

    // Display commments
    foreach ($latest_comments as $comment) {
        $displayed_a_comment = true;
        $text_to_display.= $oComments->get_comment_html($comment, true);
        // Add a link to context
        $text_to_display.= '<p><a class="arrow" href="';
        $text_to_display.= $this->get_url_page("article", $oComments->get_article_id($comment));
        $text_to_display.= "#comment-" . $oComments->get_comment_id($comment);
        $text_to_display.= '">' . $this->t("comments.view_context") . "</a></p>";
    }

    // Display notice if no comments were found
    if (!$displayed_a_comment) {

        $text_to_display.= "<p><em>" . $this->t("comments.no_comments_found") . "</em></p>";
    }

    // Links
    $text_to_display.= '<p><a class="arrow" href="' . $this->get_url_page('account') . '">' . $this->t("main.account") . "</a>\n";
    if ($this->logged_in_staff(true)) {
        $text_to_display.= '<br /><a class="arrow" href="' . $this->get_url_page('admin') . '">' . $this->t("main.admin") . '</a>';
    }
    $text_to_display.="</p>\n";
}

// Display errors
if (!$this->errorsdisplayed) {
    $this->echo_errors();
}

// Display page
echo $text_to_display;
?>